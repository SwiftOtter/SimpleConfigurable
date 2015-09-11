<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright: 2013 (c) SwiftOtter Studios
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 5/8/15
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Resource_Product_Duplicator extends Mage_Core_Model_Resource_Db_Abstract
{
    const TABLE_SUPER_ATTRIBUTE = 'catalog/product_super_attribute';
    const TABLE_SUPER_ATTRIBUTE_LABEL = 'catalog/product_super_attribute_label';
    const TABLE_SUPER_LINK = 'catalog/product_super_link';

    protected function _construct()
    {
        return $this->_init('catalog/product', 'entity_id');
    }

    public function duplicate($oldId, $newId)
    {
        $attributeData = $this->_getAttributeData($oldId);
        $this->_writeAttributeData($newId, $attributeData);

        $productData = $this->_getProductData($oldId);
        $this->_writeProductData($newId, $productData);

    }

    protected function _getAttributeData($oldId)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable(self::TABLE_SUPER_ATTRIBUTE), array('product_super_attribute_id', 'attribute_id', 'position'))
            ->where('product_id = ?', $oldId);

        $attributes = $this->_getReadAdapter()->fetchAll($select);
        foreach ($attributes as $key => $attribute) {
            $labelSelect = $this->_getReadAdapter()->select();
            $labelSelect->from($this->getTable(self::TABLE_SUPER_ATTRIBUTE_LABEL), array('store_id', 'use_default', 'value'))
                ->where('product_super_attribute_id = ?', $attribute['product_super_attribute_id']);

            $attributes[$key]['labels'] = $this->_getReadAdapter()->fetchAll($labelSelect);
        }

        return $attributes;
    }

    protected function _writeAttributeData($newId, $attributeData)
    {
        foreach ($attributeData as $attribute) {
            $labels = $attribute['labels'];
            unset($attribute['labels']);

            unset($attribute['product_super_attribute_id']);
            $attribute['product_id'] = $newId;

            $this->_getWriteAdapter()->insert($this->getTable(self::TABLE_SUPER_ATTRIBUTE), $attribute);
            $lastInsertSelect = $this->_getWriteAdapter()->select()
                ->from($this->getTable(self::TABLE_SUPER_ATTRIBUTE), array('MAX(product_super_attribute_id)'));
            $lastInsertId = $this->_getWriteAdapter()->fetchOne($lastInsertSelect);

            foreach($labels as $label) {
                $label['product_super_attribute_id'] = $lastInsertId;
                $this->_getWriteAdapter()->insert($this->getTable(self::TABLE_SUPER_ATTRIBUTE_LABEL), $label);
            }
        }
    }

    protected function _getProductData($oldId)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable(self::TABLE_SUPER_LINK), array('product_id'))
            ->where('parent_id = ?', $oldId);

        return $this->_getReadAdapter()->fetchAll($select);
    }

    protected function _writeProductData($newId, $productData)
    {
        try {
            $this->_getWriteAdapter()->beginTransaction();

            foreach ($productData as $product) {
                $product['parent_id'] = $newId;
                $this->_getWriteAdapter()->insert($this->getTable(self::TABLE_SUPER_LINK), $product);
            }

            $this->_getWriteAdapter()->commit();
        } catch (Exception $ex) {
            $this->_getWriteAdapter()->rollBack();
            throw $ex;
        }
    }
}