<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
namespace Respawnsive\Pga;

use Context;
use Db;
//use Respawnsive\Model\Pga;
use Shop;
use Symfony\Contracts\Translation\TranslatorInterface as Translator;


// Todo : old version Prestashop
/**
 * Class LegacyPgaRepository.
 */
class LegacyPgaRepository
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @var Shop
     */
    private $shop;

    /**
     * @var string
     */
    private $db_prefix;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param Db $db
     * @param Shop $shop
     * @param Translator $translator
     */
    public function __construct(Db $db, Shop $shop, Translator $translator)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
        $this->translator = $translator;
    }


    public function filterProducts($products,$defaultShowAll = true)
    {


        // get all currents groups of the user
        $groupsUser = Context::getContext()->customer->getGroups();

        $newProducts = [] ;
        foreach ($products as $product) {


            $groups = $this->getProductGroups($product['id_product']);

            foreach ($groups as $group)
            {
                if (in_array($group['id_group'],$groupsUser))
                {
                    $newProducts[] = $product ;
                }
            }
        }

        if (count($newProducts) == 0 && $defaultShowAll)
            $newProducts = $products;

        return $newProducts ;

    }

    public function getProductGroups($id_product)
    {
        $sql = "SELECT * FROM `{$this->db_prefix}product_group` WHERE id_product = $id_product";
        $result = $this->db->executeS($sql);
        return $result;

    }
    /**
     * @return bool
     */
    public function createTables()
    {
        $engine = _MYSQL_ENGINE_;
        $success = true;
        $this->dropTables();

        $queries = [] ;
//
        $queries = [
            "CREATE TABLE `{$this->db_prefix}product_group` (
              `id_product` int(10) unsigned NOT NULL,
              `id_group` int(10) unsigned NOT NULL,
              PRIMARY KEY (`id_product`,`id_group`) USING BTREE,
              KEY `id_group` (`id_group`) USING BTREE,
              KEY `id_product` (`id_product`) USING BTREE
            ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;"

        ];
        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return (bool) $success;
    }

    public function dropTables()
    {
        $sql = "DROP TABLE IF EXISTS
			`{$this->db_prefix}product_group`";
        return $this->db->execute($sql);
    }

}
