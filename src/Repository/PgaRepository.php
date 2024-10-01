<?php

namespace Respawnsive\Pga\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PgaRepository
 */
class PgaRepository
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $isMultiStoreUsed;

    /**
     * @var Context
     */
    private $multiStoreContext;

    /**
//     * @var ObjectModelHandler
//     */
//    private $objectModelHandler;

    /**
     * PgaRepository constructor.
     *
     * @param Connection $connection
     * @param string $dbPrefix
     * @param array $languages
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Connection $connection,
                   $dbPrefix,
        array $languages,
        TranslatorInterface $translator,
        bool $isMultiStoreUsed,
        Context $multiStoreContext,
//        ObjectModelHandler $objectModelHandler
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->languages = $languages;
        $this->translator = $translator;
        $this->isMultiStoreUsed = $isMultiStoreUsed;
//        $this->objectModelHandler = $objectModelHandler;
        $this->multiStoreContext = $multiStoreContext;
    }


    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createTables()
    {

        $errors = [];
        $engine = _MYSQL_ENGINE_;
        $this->dropTables();

        $queries = [
            "CREATE TABLE `{$this->dbPrefix}product_group` (
              `id_product` int(10) unsigned NOT NULL,
              `id_group` int(10) unsigned NOT NULL,
              PRIMARY KEY (`id_product`,`id_group`) USING BTREE,
              KEY `id_group` (`id_group`) USING BTREE,
              KEY `id_product` (`id_product`) USING BTREE
            ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $query) {
            $statement = $this->connection->executeQuery($query);

            if ($statement instanceof Statement && 0 != (int) $statement->errorCode()) {
                $errors[] = [
                    'key' => json_encode($statement->errorInfo()),
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        return $errors;


    }


    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function dropTables()
    {

        $errors = [];
        $tableNames = [
            'product_group',
        ];
        foreach ($tableNames as $tableName) {
            $sql = 'DROP TABLE IF EXISTS ' . $this->dbPrefix . $tableName;
            $statement = $this->connection->executeQuery($sql);
            if ($statement instanceof Statement && 0 != (int) $statement->errorCode()) {
                $errors[] = [
                    'key' => json_encode($statement->errorInfo()),
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        return $errors;
    }

    public function saveGroupAssociation($id_product, mixed $group_association)
    {

        $this->connection->delete($this->dbPrefix . 'product_group', ['id_product' => $id_product]);
        foreach ($group_association as $id_group) {
            $this->connection->insert($this->dbPrefix . 'product_group', [
                'id_product' => $id_product,
                'id_group' => $id_group,
            ]);
        }

    }


//    public function filterProduct($products)
//    {
//        var_dump($products);
//        die();
//
//    }

    public function getGroupAssociations($id_product)
    {
        $sql = 'SELECT id_group FROM ' . $this->dbPrefix . 'product_group WHERE id_product = :id_product';
        $statement = $this->connection->executeQuery($sql, ['id_product' => $id_product]);
        $group_associations = [];
        while ($row = $statement->fetch()) {
            $group_associations[] = (int) $row['id_group'];
        }

        return $group_associations;
    }

}
