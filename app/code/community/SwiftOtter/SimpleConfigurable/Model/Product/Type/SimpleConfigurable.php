<?php
/**
 * 
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 10/21/2013
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable extends Mage_Catalog_Model_Product_Type_Configurable
{
    const SIMPLE_TYPE = 'simpleconfigurable';

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param bool $checkSalable
     * @return array
     */
    public function getChildProducts($product, $checkSalable=true)
    {
        static $childrenCache = array();
        $cacheKey = $product->getId() . ':' . ($checkSalable?"1":"0");

        if (isset($childrenCache[$cacheKey])) {
            return $childrenCache[$cacheKey];
        }

        $childProducts = $product->getTypeInstance(true)->getUsedProductCollection($product);
        $childProducts->addAttributeToSelect(array('price', 'special_price', 'status', 'special_from_date', 'special_to_date'));

        if ($checkSalable) {
            $salableChildProducts = array();
            foreach($childProducts as $childProduct) {
                if($childProduct->isSalable()) {
                    $salableChildProducts[] = $childProduct;
                }
            }
            $childProducts = $salableChildProducts;
        }

        $childrenCache[$cacheKey] = $childProducts;
        return $childProducts;
    }

    /**
     * Check is product available for sale
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function isSalable($product = null)
    {
        $salable = $this->getProduct($product)->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        if ($salable && $this->getProduct($product)->hasData('is_salable')) {
            $salable = $this->getProduct($product)->getData('is_salable');
        }
        elseif ($salable && $this->isComposite()) {
            $salable = null;
        }

        if ($salable !== false) {
            $salable = false;

            /** @var Mage_Catalog_Model_Product $child */
            foreach ($this->getChildProducts($product) as $child) {
                if ($child->isSalable()) {
                    $salable = true;
                    break;
                }
            }
        }

        return $salable;
    }

    public function canConfigure($product = null)
    {
        return true;
    }

    /**
     * Retrieve related products collection
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable_Product_Collection
     */
    public function getUsedProductCollection($product = null)
    {
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        if ((int)$websiteId === 0) {
            $websiteId = 1;
        }

        $collection = Mage::getResourceModel('catalog/product_type_configurable_product_collection')
            ->setFlag('require_stock_items', true)
            ->setFlag('product_children', true)
            ->setProductFilter($this->getProduct($product))
            ->addPriceData(null, $websiteId);


        if (!is_null($this->getStoreFilter($product))) {
            $collection->addStoreFilter($this->getStoreFilter($product));
        }

        return $collection;
    }

    /**
     * Default action to get weight of product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return decimal
     */
    public function getWeight($product = null)
    {
        if ($this->getProduct($product)->hasCustomOptions() &&
            ($simpleProductOption = $this->getProduct($product)->getCustomOption('simple_product'))
        ) {
            $simpleProduct = $simpleProductOption->getProduct($product);
            if ($simpleProduct) {
                return $simpleProduct->getWeight();
            }

            if ($simpleProductOption->getProductId()) {
                return Mage::getResourceModel('catalog/product')->getAttributeRawValue(
                    $simpleProductOption->getProductId(), 'weight', 0
                );
            }
        }


        return $this->getProduct($product)->getData('weight');
    }

    /**
     * Retrieve configurable attribute collection
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable_Attribute_Collection
     */
    public function getConfigurableAttributeCollection($product = null)
    {
        return Mage::getResourceModel('SwiftOtter_SimpleConfigurable/Product_Type_SimpleConfigurable_Attribute_Collection')
            ->setProductFilter($this->getProduct($product));
    }

}