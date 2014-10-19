<?php
define('SALE_CATEGORY_ID', 607);
define('NEW_CATEGORY_ID', 608);
define('DEFAULT_IMPORT_CATEGORY', '1C');
define('BRAND_CATEGORY', 'Бренды');

class Import {

    function imagesExists($images){

        $exists = false;
        foreach ($images as $_img) {
            $imageFile = PRODUCTS_DIR.$_img;
            if (file_exists($imageFile)) {
                $exists = true;
                break;
            }
        }
        return $exists;
    }

    function getAttributeId($arg_attribute) {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        return $attribute_code;
    }

    function getOptionId($arg_attribute, $arg_value) {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute = $attribute_model->load($attribute_code);
        $attribute_options_model->setAttribute($attribute);
        $options = $attribute_options_model->getAllOptions(false);
//var_dump($options);
//exit();
        foreach ($options as $option) {
            if ($option['label'] == $arg_value) {
                return $option['value'];
            }
        }
        return false;
    }

    function checkAndAddAtributeValue($arg_attribute, $arg_value, $arg_value_eng = null) {
        
        try {
            if (!$arg_value) $arg_value = 'Универсальный';
            if (!$arg_value_eng) $arg_value_eng = 'Uni';
    //        var_dump($arg_attribute);

            if (!$this->getOptionId($arg_attribute, $arg_value)) {

                $attr_model = Mage::getModel('catalog/resource_eav_attribute');
                $attr = $attr_model->loadByCode('catalog_product', $arg_attribute);
                $attr_id = $attr->getAttributeId();

                $option['attribute_id'] = $attr_id;
                $option['value']['any_option_name'][0] = $arg_value;
                $option['value']['any_option_name'][2] = $arg_value;
                if ($arg_value_eng) {
                    $option['value']['any_option_name'][1] = $arg_value_eng;
                }

                $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
                $setup->addAttributeOption($option);
            }

            return $this->getOptionId($arg_attribute, $arg_value);
        
        } catch (Exception $e){   
            $log = $arg_attribute ;
            $log.= $arg_value ;
            $log.= $arg_value_eng ;
            $log.=$e;
            
            echo $log;
            mail(EMAIL_TO, EMAIL_SUBJECT, $log, EMAIL_HEADERS);             
            exit();
//            return "exception:$e";
        }         

//        $options = $attr_model->getAllOptions(false);
//        var_dump($options);
    }
  
  
    function getCategoryIds($segment) {
        $parentCategory = Mage::getModel('catalog/category')->loadByAttribute('name', $segment);
        if (!$parentCategory) {
            $parentCategory = Mage::getModel('catalog/category')->loadByAttribute('name', DEFAULT_IMPORT_CATEGORY);
        }
        return array( $parentCategory->getId());
    }
    
    function getBrandCategoryIds($name) {
        if ($name == '') $name = 'NoName';
        $_category = Mage::getModel('catalog/category')->loadByAttribute('name', $name);
        if (!$_category) {
            $_category = new Mage_Catalog_Model_Category();
            $_category->setName($name);
            $_category->setUrlKey($name);
            $_category->setIsActive(1);
            $_category->setDisplayMode('PRODUCTS');
            $_category->setIsAnchor(0);
            $_category->setStoreId(0);
            
            // BRAND CATEGORY = 319
            $parentCategory = Mage::getModel('catalog/category')->loadByAttribute('name', BRAND_CATEGORY);
            $_category->setPath($parentCategory->getPath());               
            $_category->save();
        }
        return array( $_category->getId(), $_category->getParentId() );
    }
    
    
    

    function ImportProduct($_item) {
        try {

            $sku = $_item['Code'];
            
            $categoryIds = $this->getCategoryIds($_item['Segment']);
            $categoryBrandIds = $this->getBrandCategoryIds($_item['Brand']);
            $newCategory = Mage::getModel('catalog/category')->load(NEW_CATEGORY_ID)->getId();
            $saleCategory = Mage::getModel('catalog/category')->load(SALE_CATEGORY_ID)->getId();


            $advanced_color = $this->checkAndAddAtributeValue('advanced_color', $_item['color'], $_item['color']);
            $segment = $this->checkAndAddAtributeValue('segment', $_item['Segment']);
            $brand = $this->checkAndAddAtributeValue('brand', $_item['Brand'], $_item['Brand']);
            $color = $this->checkAndAddAtributeValue('color', $_item['ColorRus'], $_item['ColorEng']);
            $care = $this->checkAndAddAtributeValue('care', $_item['CareRus'], $_item['ColorEng']);
            $dresscode = $this->checkAndAddAtributeValue('dresscode', $_item['CategorySecond'], $_item['CategoryEngSecond']);
            $season = $this->checkAndAddAtributeValue('season', $_item['Season']);
            $manufacturer_country = $this->checkAndAddAtributeValue('manufacturer_country', $_item['ProdRegRus'], $_item['ProdRegEng']);
            $manufacturer = $this->checkAndAddAtributeValue('manufacturer', $_item['BrCountryRus']);
            $fabric = $this->checkAndAddAtributeValue('fabric', $_item['CompRus'], $_item['CompEng']);

            foreach (array("item_size") as $attrCode) {

                $super_attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attrCode);
                $configurableAtt = Mage::getModel('catalog/product_type_configurable_attribute')->setProductAttribute($super_attribute);

                $newAttributes[] = array(
                    'id' => $configurableAtt->getId(),
                    'label' => $configurableAtt->getLabel(),
                    'position' => $super_attribute->getPosition(),
                    'values' => array(),
    //               'values'         => $configurableAtt->getPrices() ? $configProduct->getPrices() : array(),
                    'attribute_id' => $super_attribute->getId(),
                    'attribute_code' => $super_attribute->getAttributeCode(),
                    'frontend_label' => $super_attribute->getFrontend()->getLabel(),
                );
            }

            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            if (!$product) {
                $product = Mage::getModel('catalog/product');
                $product
                        ->setStoreId(0)
                        ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                        ->setTaxClassId(2)
                        ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                        ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                        ->setConfigurableAttributesData($newAttributes)
                        ->setWebsiteIDs(array(1))
                        ->setAttributeSetId(9) // Одежда
                        ->setSku($sku)
                        ->setPrice(0)
                        ->setCreatedAt(strtotime('now'))
                        ->setStockData(
                                array(
                                    'is_in_stock' => 1,
                                    'qty' => 9999
                                )
                );
            }
            /*
             * CHILD PRODUCTS
             */ 
            $configurableData = array();
            $onsale = false;
            $isnew = false;
            if (strlen($_item['new_product'])>0) $isnew = date("d/m/Y h:i:s");

            foreach ($_item['size'] as $_size) {

                if (floatval($_size['DiscountPrice']) < floatval($_size['Price'])) $onsale = true;


                $simple = Mage::getModel('catalog/product')->loadByAttribute('sku', $_size['Barcode']);
                if (!$simple) {
                    $simple = Mage::getModel('catalog/product');
                    $simple
                            ->setStoreId(0)
                            ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
                            ->setTaxClassId(2)
                            ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
                            ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                            ->setWebsiteIDs(array(1))
                            ->setAttributeSetId(9) // Одежда
                            ->setSku($_size['Barcode'])
                            ->setCreatedAt(strtotime('now'));
                }

                $size = $this->checkAndAddAtributeValue('item_size', $_size['SizeRus'], $_size['SizeEng']);
                $simple
                        ->setStoreId(0)
                        ->setUrlKey($_size['Barcode'])
                        ->setCategoryIds(array(array_merge($categoryIds,$categoryBrandIds)))
                        ->setPrice(floatval($_size['Price']))
                        ->setSpecialPrice(floatval($_size['DiscountPrice']))
                        ->setData('name', $_item['NameRus'])
                        ->setData('segment', $segment)
                        ->setData('brand', $brand)
                        ->setData('short_description', $_item['DescrRus'])
                        ->setData('color', $color)
                        ->setData('advanced_color', $advanced_color)
                        ->setData('season', $season)
                        ->setData('manufacturer', $manufacturer)
                        ->setData('manufacturer_country', $manufacturer_country)
                        ->setData('care', $care)
                        ->setData('short_description', $_item['DescrRus'])
                        ->setData('item_size', $size)
                        ->setData('fabric', $fabric)
                ;
                $simple->save();

                $stockItem = Mage::getModel('cataloginventory/stock_item')
                        ->assignProduct($simple)
                        ->setData('is_in_stock', $_size['Remains'] > 0 ? 1 : 0)
                        ->setData('use_config_manage_stock', 0)
                        ->setData('manage_stock', 1)
                        ->setData('stock_id', 1)
                        ->setData('qty', $_size['Remains']);
                $stockItem->save();

                $configurableData[$simple->getId()][] = array(
                    'attribute_id' => $this->getAttributeId('item_size'),
                    'label' => '',
                    'value_index' => $size,
                    'is_percent' => 0,
                    'pricing_value' => ''
                );

            }

            $_categories = array_unique(array_merge($categoryIds,$categoryBrandIds,$product->getCategoryIds()));

            // SALE
            if ($onsale) {
                $_categories[] = SALE_CATEGORY_ID;
            } else {
                $_categories = array_diff($_categories, array(SALE_CATEGORY_ID));
            }

            // NEW
            if ($isnew) {
                $_categories[] = NEW_CATEGORY_ID;
            } else {
                $_categories = array_diff($_categories, array(NEW_CATEGORY_ID));
                $product->setData('news_from_date', null);
            }


            $product
                ->setCategoryIds(array($_categories))
                ->setUrlKey($sku)
                ->setStoreId(0)
                //                ->setData('save_rewrites_history', true)
                ->setData('name', $_item['NameRus'])
                ->setData('segment', $segment)
                ->setData('dresscode', $dresscode)
                ->setData('brand', $brand)
                ->setData('short_description', $_item['DescrRus'])
                ->setData('onsale', $onsale)
                ->setData('news_from_date', $isnew)
                ->setData('color', $color)
                ->setData('advanced_color', $advanced_color)
                ->setData('season', $season)
                ->setData('manufacturer', $manufacturer)
                ->setData('manufacturer_country', $manufacturer_country)
                ->setData('care', $care)
                ->setData('fabric', $fabric)
            ;


            $product->setConfigurableProductsData($configurableData);
            $product->save();
//            $product->getResource()->save($product);


            // Check if images exists then do image job
            if ($this->imagesExists($_item['img'])) {

                /*
                 * MEDIA GALLERY REMOVE IMAGES
                 */
                $mediaApi = Mage::getModel("catalog/product_attribute_media_api");
                $items = $mediaApi->items($product->getId());
                foreach($items as $item) {
                    ($mediaApi->remove($product->getId(), $item['file']));
                    $filename = Mage::getSingleton('catalog/product_media_config' )->getMediaPath($item['file']);
                    unlink($filename);
                }
//                $product->save();

                /*
                 * MEDIA GALLERY ASSIGN IMAGES
                 */
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
                $_product = $product->setStoreId(0);
                $_product->setMediaGallery (array('images'=>array (), 'values'=>array ()));
                $reversed_images = array_reverse($_item['img']);
                foreach ($reversed_images as $_img) {

                    $imageFile = PRODUCTS_DIR.$_img;
                    if (file_exists($imageFile)) {
//                        var_dump($imageFile);
                        $_product->addImageToMediaGallery($imageFile, null, false, false);
                    }
                }
                $_product->save();


//                 Set Primary Image
                $mediaGallery = $product->getMediaGallery();
                if (isset($mediaGallery['images'])){

                    $image = end($mediaGallery['images']);
                    Mage::getSingleton('catalog/product_action')
                        ->updateAttributes(array($_product->getId()),
                            array(
                                'image'=>$image['file'],
                                'small_image'=>$image['file'],
                                'thumbnail'=>$image['file']
                            )
                            , 0);
                }
            }

            // English section
//            $product->setStoreId('en')
//                ->setData('name', $_item['NameEng'])
//                ->setData('short_description', $_item['DescrEng']);

            $_enProduct = Mage::getModel('catalog/product')->setStoreId('en')->load($product->getId());

            $_enProduct
                ->setCategoryIds(array($_categories))
                ->setStoreId('en')
                ->setData('name', $_item['NameEng'])
                ->setData('segment', $segment)
                ->setData('brand', $brand)
                ->setData('short_description', $_item['DescrEng'])
                ->setData('onsale', $onsale)
                ->setData('news_from_date', $isnew)
                ->setData('color', $color)
                ->setData('advanced_color', $advanced_color)
                ->setData('season', $season)
                ->setData('manufacturer', $manufacturer)
                ->setData('manufacturer_country', $manufacturer_country)
                ->setData('care', $care)
                ->setData('fabric', $fabric)
            ;
            $_enProduct->save();


            $return = array('id'=>$product->getId(), 'code'=>$_item['Code']);
            return $return;

        } catch (Exception $e){   
            $log = 'FILE :'.IMPORT_FILE ;
            $log.= 'ITEM: '.$_item['Code'] ;
            $log.= 'TEXT :'. $e ;
            echo $log;
            unlink(IMPORT_LOCK);
            mail(EMAIL_TO, EMAIL_SUBJECT, $log, EMAIL_HEADERS);             
            
            exit();
//            return "exception:$e";
        } 
    }

}
//$filename = Mage::getSingleton('catalog/product_media_config' )->getMediaPath($item['file']);
//unlink($filename);
?>