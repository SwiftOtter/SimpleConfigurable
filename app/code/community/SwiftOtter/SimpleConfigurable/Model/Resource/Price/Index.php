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
 * @copyright Swift Otter Studios, 4/23/15
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Resource_Price_Index extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('SwiftOtter_SimpleConfigurable/Price_Index', 'entity_id');
    }

    public function getPriceValues($productId, $websiteId, $customerGroupId = 0)
    {
        $read = $this->_getReadAdapter();
        $select = $read->select();

        $select->from($this->getMainTable(), array('low_price', 'high_price', 'low_final_price', 'high_final_price'))
            ->where('entity_id = ?', $productId)
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId);

        return $read->fetchRow($select);
    }

}