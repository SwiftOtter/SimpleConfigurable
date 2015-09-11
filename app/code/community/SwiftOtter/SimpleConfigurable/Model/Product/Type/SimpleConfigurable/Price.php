<?php
/**
 * 
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 10/21/2013
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable_Price extends Mage_Catalog_Model_Product_Type_Configurable_Price
{
    protected $_attributes = array();

    public function getMinimalPrice ($product)
    {
        return $this->getPrice($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function getMaxPossibleFinalPrice($product)
    {
        // Indexer calculates max_price, so if this value's been loaded, use it
        $price = $product->getMaxPrice();
        if ($price !== null) {
            return $price;
        }

        $childProduct = $this->getChildProductForRangeExtent($product, "finalPrice", false);
        // If there aren't any salable child products we return the highest price
        // of all child products, including any ones not currently salable.

        if (!$childProduct) {
            $childProduct = $this->getChildProductForRangeExtent($product, "finalPrice", false, false);
        }

        if ($childProduct) {
            return $childProduct->getFinalPrice();
        }
        return false;
    }

    public function getFinalPrice($qty=null, $product)
    {
        $childProduct = $this->getConfigurableItem($product);
        if (!$childProduct) {
            $childProduct = $this->getChildProductForRangeExtent($product, "finalPrice", true, false);
        }

        if ($childProduct) {
            $price = $childProduct->getFinalPrice($qty);
        } else {
            return false;
        }

        $product->setFinalPrice($price);
        return $price;
    }

    /**
     * Get Total price for configurable items
     *
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    public function getConfigurableItem($product)
    {
        $product->getTypeInstance(true)
            ->setStoreFilter($product->getStore(), $product);

        $selectedAttributes = array();
        if ($product->getCustomOption('attributes')) {
            $selectedAttributes = unserialize($product->getCustomOption('attributes')->getValue());
        }

        if (is_array($selectedAttributes) && count($selectedAttributes) && $this->_attributeExists($selectedAttributes)) {
            /** @var Mage_Catalog_Model_Product $selectedProduct */
            $selectedProduct = $product->getTypeInstance(true)->getProductByAttributes($selectedAttributes, $product);
            if ($selectedProduct && $selectedProduct->getId()) {
                $selectedProduct = Mage::getModel('catalog/product')->load($selectedProduct->getId());
            }

            return $selectedProduct;
        } else {
            return 0;
        }
    }

    protected function _attributeExists($attributeId)
    {
        if (is_array($attributeId)) {
            foreach ($attributeId as $id => $value) {
                if (!$this->_attributeExists($id)) {
                    return false;
                }
            }

            return true;
        }

        if (!isset($this->_attributes[$attributeId])) {
            $this->_attributes[$attributeId] = count(Mage::getResourceModel('eav/entity_attribute_collection')
                ->addFieldToFilter('attribute_id', array('eq' => $attributeId))) > 0;
        }

        return $this->_attributes[$attributeId];
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return decimal
     */
    public function getPrice($product)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return 0;
        }

        // Just return indexed_price, if it's been fetched already
        // (which it will have been for collections, but not on product page)
        $price = $product->getIndexedPrice();
        if ($price !== null) {
            return $price;
        }

        $childProduct = $this->getChildProductForRangeExtent($product, "finalPrice");
        // If there aren't any salable child products we return the lowest price
        // of all child products, including any ones not currently salable.
        if (!$childProduct) {
            $childProduct = $this->getChildProductForRangeExtent($product, "finalPrice", true, false);
        }

        if ($childProduct) {
            return $childProduct->getPrice();
        }

        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $priceType
     * @param bool $lowRange
     * @param bool $checkSalable
     * @return Mage_Catalog_Model_Product
     */
    public function getChildProductForRangeExtent($product, $priceType, $lowRange = true, $checkSalable = true)
    {
        $childProducts = $product->getTypeInstance(true)->getChildProducts($product, $checkSalable);
        if (count($childProducts) == 0) { // If config product has no children
            return false;
        }

        $rangePrice = 0;
        if ($lowRange) {
            $rangePrice = PHP_INT_MAX;
        }
        $rangeProduct = false;
        foreach($childProducts as $childProduct) {
            if ($priceType == "finalPrice") {
                $thisPrice = $childProduct->getFinalPrice();
            } else {
                $thisPrice = $childProduct->getPrice();
            }


            if (($lowRange && $thisPrice < $rangePrice) || (!$lowRange && $thisPrice > $rangePrice)) {
                $rangePrice = $thisPrice;
                $rangeProduct = $childProduct;
            }
        }
        return $rangeProduct;
    }
}