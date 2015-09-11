<?php
/**
 * 
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 10/25/2013
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Observer
{
    public function catalogModelProductDuplicate($observer)
    {
        $current = $observer->getCurrentProduct();
        $new = $observer->getNewProduct();

        if ($current->getTypeId() == SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE) {
            if (!$new->getId()) {
                $new->save();
                $stockData = Mage::getModel('cataloginventory/stock_item')->loadByProduct($new);
                $new->setStockData($stockData->getData());
            }

            Mage::getResourceModel('SwiftOtter_SimpleConfigurable/Product_Duplicator')->duplicate($current->getId(), $new->getId());
        }
    }

    public function catalogBlockProductListCollection($observer)
    {
        /** @var Mage_Eav_Model_Entity_Collection_Abstract $collection */
        $collection = $observer->getCollection();

        $collection->joinTable(array('extended_price' => $collection->getTable('SwiftOtter_SimpleConfigurable/Price_Index')),
            'entity_id = entity_id',
            array(
                'low_price' => 'MIN(low_price)',
                'high_price' => 'MAX(high_price)',
                'low_final_price' => 'MIN(low_final_price)',
                'high_final_price' => 'MAX(high_final_price)'
            ),
            'extended_price.website_id = price_index.website_id',
            'left'
        );

        $select = $collection->getSelect();
        if (!$select->getPart($select::GROUP)) {
            $select->group('e.entity_id');
        }
    }

    public function adminhtmlCatalogProductEditPrepareForm($observer)
    {
        if ($product = Mage::registry('current_product')) {
            /** @var Varien_Data_Form $form */
            $form = $observer->getForm();
            $formElements = $form->getElements();
            if (count($formElements) > 0) {
                $fieldset = $formElements[0];
            }
        }
    }

    public function productOptionRendererInit($observer)
    {
        /** @var Mage_Wishlist_Block_Customer_Wishlist_Item_Options $block */
        $block = $observer->getBlock();

        $block->addOptionsRenderCfg(SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE,
            'SwiftOtter_SimpleConfigurable/Configuration');
    }

    public function catalogProductNewAction($observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();
        $request = Mage::app()->getRequest();
        $simpleType = SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE;

        if ($product->getTypeId() == $simpleType &&
            !$request->getParam('attributes')) {
            $product->setData('type_id', Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE);
            $product->setSimpleConfigurable(true);
        }

        $attributes = $request->getParam('attributes');
        if ($attributes && $product->getTypeId() == $simpleType &&
            (!$product->getId() || !$product->getTypeInstance()->getUsedProductAttributeIds())) {
            $product->getTypeInstance()->setUsedProductAttributeIds(
                explode(",", base64_decode(urldecode($attributes)))
            );
        }
    }

    public function catalogProductLoadAfter($observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();

        if ($product->getTypeId() == SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE) {
            $customerGroupId = 0;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            }

            $customerGroups = explode(',', $customerGroupId);
            $prices = array();

            foreach ($customerGroups as $groupId) {
                $prices[$groupId] = Mage::getResourceModel('SwiftOtter_SimpleConfigurable/Price_Index')->getPriceValues(
                    $product->getId(), Mage::app()->getWebsite()->getId(), $groupId
                );
            }

            $lowest = PHP_INT_MAX;
            $winnerGroup = null;
            foreach ($prices as $groupId => $values) {
                if (is_array($values) && array_sum($values) < $lowest) {
                    $winnerGroup = $groupId;
                    $lowest = array_sum($values);
                }
            }

            if (is_array($winnerGroup)) {
                $product->addData($prices[$winnerGroup]);
            }
        }
    }

    public function catalogProductSaveCommitAfter($observer)
    {
        // This is occuring at Mage_Catalog_Model_Resource_Product_Indexer_Price::catalogProductSave()

//        /** @var Mage_Catalog_Model_Product $product */
//        $product = $observer->getProduct();
//
//        if (!Mage::helper('SwiftOtter_Base')->getProductIsConfigurable($product)) {
//            $parents = Mage::helper('SwiftOtter_SimpleConfigurable')->getParentProduct($product->getId());
//            $simpleType = SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE;
//
//            $reindex = array();
//            foreach ($parents as $productId => $typeId) {
//                if ($typeId === $simpleType) {
//                    $reindex[] = $productId;
//                }
//            }
//
//            if (count($reindex)) {
//                Mage::getResourceModel('SwiftOtter_SimpleConfigurable/Product_Indexer_Price_SimpleConfigurable')->setTypeId($simpleType)->reindexEntity($reindex);
//            }
//        }
    }

    public function catalogProductSaveBefore($observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE &&
            $product->getSimpleConfigurable()) {
            $product->setData('type_id', SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE);
        }
    }



    public function catalogProductCollectionLoadAfter($observer)
    {
        $controllerName = Mage::app()->getFrontController()->getRequest()->getControllerName();

        if ($controllerName == 'sales_order_create') {
            $collection = $observer->getCollection();

            /** @var Mage_Catalog_Model_Product $product */
            foreach ($collection as $product) {
                if ($product->getTypeId() == SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE) {
                    $product->setPrice(0);
                }
            }
        }
    }

    public function controllerActionLayoutLoadBefore($observer)
    {
        $type = SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE;

        /** @var Mage_Adminhtml_Controller_Action $controller */
        $controller = $observer->getAction();
        /** @var Mage_Core_Model_Layout $layout */
        $layout = $observer->getLayout();

        if ($controller->getFullActionName() == 'adminhtml_catalog_product_new' && $controller->getRequest()->getParam('type') == $type &&
            !$controller->getRequest()->getParam('attributes')) {
            $layout->getUpdate()->addHandle('adminhtml_catalog_product_' . $type . '_new');
        }
    }

}