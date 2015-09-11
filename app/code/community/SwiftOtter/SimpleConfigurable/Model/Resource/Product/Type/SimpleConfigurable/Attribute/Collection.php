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
 * @copyright Swift Otter Studios, 7/30/15
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Resource_Product_Type_SimpleConfigurable_Attribute_Collection extends Mage_Catalog_Model_Resource_Product_Type_Configurable_Attribute_Collection
{
    protected function _loadPrices()
    {
        if ($this->count()) {
            $pricings = array(
                0 => array()
            );

            if ($this->getHelper()->isPriceGlobal()) {
                $websiteId = 0;
            } else {
                $websiteId = (int)Mage::app()->getStore($this->getStoreId())->getWebsiteId();
                $pricing[$websiteId] = array();
            }

            $select = $this->getConnection()->select()
                ->from(array('price' => $this->_priceTable))
                ->where('price.product_super_attribute_id IN (?)', array_keys($this->_items));

            if ($websiteId > 0) {
                $select->where('price.website_id IN(?)', array(0, $websiteId));
            } else {
                $select->where('price.website_id = ?', 0);
            }

            $query = $this->getConnection()->query($select);

            while ($row = $query->fetch()) {
                $pricings[(int)$row['website_id']][] = $row;
            }

            $values = array();

            foreach ($this->_items as $item) {
                $productAttribute = $item->getProductAttribute();
                if (!($productAttribute instanceof Mage_Eav_Model_Entity_Attribute_Abstract)) {
                    continue;
                }
                $options = $productAttribute->getFrontend()->getSelectOptions();

                $optionsByValue = array();
                foreach ($options as $option) {
                    $optionsByValue[$option['value']] = $option['label'];
                }

                $optionOrder = array();
                $i = 0;
                foreach($options as $option) {
                    $optionOrder[$option['value']] = $i++;
                }

                foreach ($this->getProduct()->getTypeInstance(true)
                             ->getUsedProducts(array($productAttribute->getAttributeCode()), $this->getProduct())
                         as $associatedProduct) {

                    $optionValue = $associatedProduct->getData($productAttribute->getAttributeCode());

                    $optionSort = 1;

                    if (isset($optionOrder[$optionValue])) {
                        $optionSort = $optionOrder[$optionValue];
                    }

                    if (array_key_exists($optionValue, $optionsByValue)) {
                        // If option available in associated product
                        if (!isset($values[$optionSort . ':' . $item->getId() . ':' . $optionValue])) {
                            // If option not added, we will add it.
                            $values[$optionSort . ':' . $item->getId() . ':' . $optionValue] = array(
                                'product_super_attribute_id' => $item->getId(),
                                'value_index'                => $optionValue,
                                'label'                      => $optionsByValue[$optionValue],
                                'default_label'              => $optionsByValue[$optionValue],
                                'store_label'                => $optionsByValue[$optionValue],
                                'is_percent'                 => 0,
                                'pricing_value'              => null,
                                'use_default_value'          => true
                            );
                        }
                    }
                }
            }

            foreach ($pricings[0] as $pricing) {
                // Addding pricing to options
                $valueKey = $pricing['product_super_attribute_id'] . ':' . $pricing['value_index'];
                if (isset($values[$valueKey])) {
                    $values[$valueKey]['pricing_value']     = $pricing['pricing_value'];
                    $values[$valueKey]['is_percent']        = $pricing['is_percent'];
                    $values[$valueKey]['value_id']          = $pricing['value_id'];
                    $values[$valueKey]['use_default_value'] = true;
                }
            }

            if ($websiteId && isset($pricings[$websiteId])) {
                foreach ($pricings[$websiteId] as $pricing) {
                    $valueKey = $pricing['product_super_attribute_id'] . ':' . $pricing['value_index'];
                    if (isset($values[$valueKey])) {
                        $values[$valueKey]['pricing_value']     = $pricing['pricing_value'];
                        $values[$valueKey]['is_percent']        = $pricing['is_percent'];
                        $values[$valueKey]['value_id']          = $pricing['value_id'];
                        $values[$valueKey]['use_default_value'] = false;
                    }
                }
            }


            uksort($values, function($a, $b) {
                list($a) = explode(':', $a);
                list($b) = explode(':', $b);
                return ((int)$a > (int)$b) ? 1 : -1;
            });

            foreach ($values as $key => $data) {
                $this->getItemById($data['product_super_attribute_id'])->addPrice($data);
            }
        }
        return $this;
    }
}