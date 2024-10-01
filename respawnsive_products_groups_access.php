<?php

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Respawnsive\Pga\Form\Modifier\ProductFormModifier;
use Respawnsive\Pga\Repository\PgaRepository;
use Respawnsive\Pga\LegacyPgaRepository;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!defined('_PS_VERSION_')) {
    exit;
}

class respawnsive_products_groups_access extends Module
{
    /**
     * @var PgaRepository|null
     */
    private $repository;

    /**
     * @var LegacyPgaRepository
     */
    private $legacyPgaRepository;


    const MODULE_NAME = 'respawnsive_products_groups_access';

    public function __construct()
    {
        $this->name = self::MODULE_NAME;
        $this->version = '1.0.0';
        $this->author = 'Respawnsive SAS';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->getTranslator()->trans(
            'Products Groups Access',
            [],
            'Modules.RespawnsiveEpicuria.Admin'
        );

        $this->legacyPgaRepository =  new LegacyPgaRepository(Db::getInstance(), $this->context->shop, $this->context->getTranslator());


    }

    protected function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Paramètres'),
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Afficher tout les produits lorsqu\'aucun groupe n\'est sélectionné'),
                    'name' => 'loadAllProductsIfNoGroupsSelected',
                    'required' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Oui')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Non')
                        )
                    ),
                )
            ),
            'submit' => array(
                'title' => $this->l('Enregistrer'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Propriétés du formulaire
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit_pga';
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Enregistrer'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Retour à la liste')
            )
        );

        // Charger la valeur actuelle
        $helper->fields_value['loadAllProductsIfNoGroupsSelected'] = Tools::getValue('loadAllProductsIfNoGroupsSelected', Configuration::get('loadAllProductsIfNoGroupsSelected'));

        return $helper->generateForm($fields_form);
    }

    public function getContent()
    {

        $output = '';

        if (Tools::isSubmit('submit_pga')) {
            $mon_valeur = (string)Tools::getValue('loadAllProductsIfNoGroupsSelected');

            if (!Validate::isGenericName($mon_valeur)) {
                $output .= $this->displayError($this->l('Valeur de configuration invalide'));
            } else {
                Configuration::updateValue('loadAllProductsIfNoGroupsSelected', $mon_valeur);
                $output .= $this->displayConfirmation($this->l('Paramètres mis à jour'));
            }
        }

        $output .= $this->renderForm();

        return $output;
    }
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() && Configuration::updateValue('loadAllProductsIfNoGroupsSelected', true)) {
            return false;
        }

        $tablesInstalledWithSuccess = $this->createTables();
        if (!$tablesInstalledWithSuccess) {
            $this->uninstall();
            return false;
        }

        if (!$this->registerHook([
            'addWebserviceResources',
            'actionProductSearchProviderRunQueryAfter',
            'actionProductFormBuilderModifier',
            'actionPresentProduct',
            'actionAdminControllerSetMedia',
            'actionProductUpdate']))
            return false ;

        Tools::clearSf2Cache();

        return true ;

    }

    public function hookAddWebserviceResources($extra_resources)
    {
        $cacheKey = 'objectmodel_def_' . \Respawnsive\Pga\Webservice\ProductsGroupsResource::class ;

        Cache::clean($cacheKey);

//        $extra_resources['products']['class'] = 'MyCustomClassProduct';
        $extra_resources['products_groups'] = array('description' => 'Manager products groups', 'class' => 'Respawnsive\Pga\Webservice\ProductsGroupsResource');
        return $extra_resources;
    }
    public function hookActionPresentProduct($params)
    {

        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray $product */
        $product = &$params['presentedProduct'] ;
        $newProducts = $this->getRepository()->filterProducts([ ['id_product' => $product->getId()] ] ,Tools::getValue('loadAllProductsIfNoGroupsSelected', Configuration::get('loadAllProductsIfNoGroupsSelected'))) ;
        if (count($newProducts) == 0)
        {
            Tools::redirect('index.php?controller=404');
        }

    }

    public function hookActionProductSearchProviderRunQueryAfter($params)
    {
        $query = $params['query'];
        /** @var \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult $result */
        $result = $params['result'];
        $products = $result->getProducts() ;
        $newProducts = $this->getRepository()->filterProducts($products,Tools::getValue('loadAllProductsIfNoGroupsSelected', Configuration::get('loadAllProductsIfNoGroupsSelected'))) ;
        $result->setProducts($newProducts);
        $result->setTotalProductsCount(count($newProducts));
    }

    public function hookActionProductUpdate($params)
    {
        $group_association = $_POST['product']['description']['group_association'] ?? null;
        if (!$group_association)
            $group_association = [] ;

        if (isset($params['id_product']))
            $this->saveGroupAssociation($params['id_product'],$group_association) ;

    }
    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addCSS($this->_path.'views/css/override.css');
    }

    /**
     * Modify product form builder
     *
     * @param array $params
     */
    public function hookActionProductFormBuilderModifier(array $params): void
    {
        /** @var ProductFormModifier $productFormModifier */
        $productFormModifier = $this->get(ProductFormModifier::class);
        $productId = (int) $params['id'];

        $group_associations = $this->getRepository()->getGroupAssociations($productId);
        $productFormModifier->modify($productId, $params['form_builder'],$group_associations);
    }



    /**
     * @param array $errors
     */
    private function addModuleErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->_errors[] = $this->trans($error['key'], $error['parameters'], $error['domain']);
        }
    }

    /**
     * @return PgaRepository
     */
    private function getRepository()
    {
        if (null === $this->repository) {
            try {
                $this->repository = $this->get('prestashop.module.pga.repository');
            } catch (Throwable $e) {
                try {
                    $container = SymfonyContainer::getInstance();
                    if (null !== $container) {
                        $this->repository = $container->get('prestashop.module.pga.repository');
                    }
                } catch (Throwable $e) {
                }
            }
        }

        // Container is not available so we use legacy repository as fallback
        if (!$this->repository) {
            $this->repository = $this->legacyPgaRepository;
        }


        return $this->repository;
    }

    /**
     * @return bool
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTables()
    {

        $result = $this->getRepository()->createTables();
        if (false === $result || (is_array($result) && !empty($result))) {
            if (is_array($result)) {
                $this->addModuleErrors($result);
            }

            return false;
        }

        return true;
    }


    public function uninstall()
    {
        $uninstalled = true;

        $result = $this->getRepository()->dropTables();
        if (false === $result || (is_array($result) && !empty($result))) {
            if (is_array($result)) {
                $this->addModuleErrors($result);
            }
            $uninstalled = false;
        }


        $result= ($uninstalled && parent::uninstall()  && Configuration::deleteByName('loadAllProductsIfNoGroupsSelected'));
        Tools::clearSf2Cache();
        return $result ;

    }

    private function saveGroupAssociation($id_product, mixed $group_association)
    {
        $this->getRepository()->saveGroupAssociation($id_product, $group_association);
    }

}
