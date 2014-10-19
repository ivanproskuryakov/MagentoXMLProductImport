<?php
/*
 * Mage class
 *
 */

set_time_limit(0);
ini_set('memory_limit', '1024M');
include_once "../app/Mage.php";
$app = Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);



function updateProductStores($products){

    $attrArray=array('thumbnail','small_image','image',
                    'onsale',
                    'url_key'
                    );
//    $products = array(170,171,172);
    $stores = array(1,2);

    $productsAsString = implode(',', $products);
    $storesAsString = implode(',', $stores);
//get access to the resource
    $resource = Mage::getSingleton('core/resource');
//get access to the db write connection
    $connection = $resource->getConnection('core_write');
//model for retrieving attributes
    $eavConfig = Mage::getModel('eav/config');
    $tables = array();
//get the association between attribute ids and the tables where their values are stored
//group them by table name so you will run a single query for each table
    foreach ($attrArray as $attributeCode){
        $attribute = $eavConfig->getAttribute('catalog_product', $attributeCode);
        if ($attribute){
            $tableName = $resource->getTableName('catalog/product') . '_' . $attribute->getBackendType();
            $tables[$tableName][] = $attribute->getId();
        }
    }
//for each table delete the attribute values in the specified store for the specified products
    foreach ($tables as $tableName => $attributeIds){
        $attributeIdsAsString = implode(',', $attributeIds);
        $q = "DELETE FROM {$tableName}
                WHERE
                    attribute_id IN ({$attributeIdsAsString}) AND
                    entity_id IN ({$productsAsString}) AND
                    store_id IN ({$storesAsString})";
        $connection->query($q);
    }

}


?>