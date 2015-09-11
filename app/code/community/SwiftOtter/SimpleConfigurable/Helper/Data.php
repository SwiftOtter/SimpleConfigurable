<?php
/**
 * 
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 10/21/2013
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Helper_Data extends Mage_Core_Helper_Data
{
    public function getParentProduct($productId)
    {
        $resource = Mage::getResourceModel('core/resource');
        $connection = $resource->getReadConnection();

        $select = $connection->select();
        $select->from(array('link' => $resource->getTable('catalog/product_super_link')), array('parent_id'))
            ->joinInner(array('product' => $resource->getTable('catalog/product')), 'product.entity_id = link.parent_id', array('type_id'))
            ->where('product_id = ?', $productId);

        return $connection->fetchPairs($select);
    }
}