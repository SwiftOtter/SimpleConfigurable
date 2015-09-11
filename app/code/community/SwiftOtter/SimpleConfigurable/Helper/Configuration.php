<?php
/**
 * SwiftOtter_SimpleConfigurable is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_SimpleConfigurable is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_SimpleConfigurable. If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright: 2013 (c) SwiftOtter Studios
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 2/25/14
 * @package default
 **/


class SwiftOtter_SimpleConfigurable_Helper_Configuration extends Mage_Catalog_Helper_Product_Configuration
{
    /**
     * Retrieves configuration options for configurable product
     *
     * @param Mage_Catalog_Model_Product_Configuration_Item_Interface $item
     * @return array
     */
    public function getSimpleConfigurableOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
    {
        $product = $item->getProduct();
        $typeId = $product->getTypeId();
        if ($typeId != SwiftOtter_SimpleConfigurable_Model_Product_Type_SimpleConfigurable::SIMPLE_TYPE) {
            Mage::throwException($this->__('Wrong product type to extract configurable options.'));
        }
        $attributes = $product->getTypeInstance(true)
            ->getSelectedAttributesInfo($product);
        return array_merge($attributes, $this->getCustomOptions($item));
    }

    public function getOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
    {
        return $this->getSimpleConfigurableOptions($item);
    }
}