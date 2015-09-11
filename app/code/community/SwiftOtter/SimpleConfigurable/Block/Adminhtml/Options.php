<?php
/**
 * 
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 10/25/2013
 * @package default
 **/

/**
 * Adminhtml block for fieldset of configurable product
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class SwiftOtter_SimpleConfigurable_Block_Adminhtml_Options extends SwiftOtter_SimpleConfigurable_Block_Frontend_Options
{
    /**
     * Retrieve product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', Mage::registry('product'));
        }
        $product = $this->getData('product');
        if (is_null($product->getTypeInstance(true)->getStoreFilter($product))) {
            $product->getTypeInstance(true)->setStoreFilter(Mage::app()->getStore($product->getStoreId()), $product);
        }

        return $product;
    }

    /**
     * Retrieve current store
     *
     * @return Mage_Core_Model_Store
     */
    public function getCurrentStore()
    {
        return Mage::app()->getStore($this->getProduct()->getStoreId());
    }

    /**
     * Returns additional values for js config, con be overriden by descedants
     *
     * @return array
     */
    protected function _getAdditionalConfig()
    {
        $result = parent::_getAdditionalConfig();
        $result['disablePriceReload'] = true; // There's no field for price at popup
        $result['stablePrices'] = true; // We don't want to recalc prices displayed in OPTIONs of SELECT
        $result['showPrices'] = true;
        return $result;
    }
}
