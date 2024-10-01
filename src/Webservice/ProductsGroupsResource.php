<?php

namespace Respawnsive\Pga\Webservice ;

use Db;

class ProductsGroupsResource extends \ObjectModel
{
    public int $id_product = 0 ;
    public array $groups = [] ;

    /**
     * Get getWsProductsGroup
     * (for webservice).
     *
     * @param integer $id
     *
     * @return array id_group (all groups)
     */
    public static function getWsProductsGroup($classthis)
    {

        $infos = \Db::getInstance()->executeS("SELECT * FROM ps_product_group WHERE id_product = " . $classthis->id_product); ;
        $return = [] ;

        foreach ($infos as $i)
        {
            $return[] =   [  'field' => 'group' ,  'id' => $i['id_group'] ] ;
        }

        return $return ;

    }


    public function add($auto_date = true, $null_values = false)
    {
        $debug = 1 ;

        // delete old groups
        Db::getInstance()->execute("DELETE FROM ps_product_group WHERE id_product = " . $this->id_product) ;

        $dataOriginal = $this->getFields() ;

        foreach ($this->groups as $group) {
            $data = $dataOriginal ;
            $data['id_group'] = $group ;

            if (!$result = Db::getInstance()->insert($this->def['table'], $data, $null_values)) {
                return false;
            }
        }

        return $result ;


//        return parent::add($auto_date, $null_values); // TODO: Change the autogenerated stub

    }


    /**
     * Set setWsProductsGroup
     * (for webservice).
     *
     * @param integer $id
     *
     * @return bool Indictes id_group is set
     */
    public function setWsProductsGroup($groups)
    {

        $groups = explode(',',$groups);

        foreach ($groups as $group) {
            $this->groups[] = $group;
        }

    }

    protected $webserviceParameters = array(
        'objectNodeName' => 'products_group',
        'hidden_fields' => ['id'],
        'objectsNodeName' => 'products_groups',
        'associations' => [
            'groups' => [
                'resource' => 'groups',
                'getter' =>  ['Respawnsive\Pga\Webservice\ProductsGroupsResource','getWsProductsGroup'],
                'setter' => 'setWsProductsGroup'
            ]
        ],
        'fields' => array(
            'id_product' => array('required' => true),
            'groups' => array('required' => true, 'setter' => 'setWsProductsGroup'), // group is a string separated by comma
        )
    );
    public static $definition = [
        'table' => 'product_group',
        'primary' => 'id_product',
        'associations' => [
            'groups' => [
                'type' => self::HAS_MANY,
                'resource' => 'groups',
                'fields' => [
                    'id' => [],
                ],
                'getter' => 'getWsProductsGroup',
                'setter' => 'setWsProductsGroup'
            ],
        ],
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT],
        ],
    ];
}
