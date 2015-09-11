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
 * @copyright Swift Otter Studios, 4/11/15
 * @package default
 **/

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE `{$installer->getTable('SwiftOtter_SimpleConfigurable/Price_Index')}` (
        `entity_id` INT UNSIGNED,
        `customer_group_id` SMALLINT(5) UNSIGNED NOT NULL,
        `website_id` SMALLINT(5) UNSIGNED NOT NULL,
        `low_price` DECIMAL(12,4) DEFAULT NULL,
        `high_price` DECIMAL(12,4) DEFAULT NULL,
        `low_final_price` DECIMAL(12,4) DEFAULT NULL,
        `high_final_price` DECIMAL(12,4) DEFAULT NULL,
        PRIMARY KEY(`entity_id`, `customer_group_id`, `website_id`),
        KEY `IDX_SIMPLE_CONFIG_INDEX_PRICE_CUSTOMER_GROUP_ID` (`customer_group_id`),
        KEY `IDX_SIMPLE_CONFIG_INDEX_PRICE_WEBSITE_ID` (`website_id`),

        CONSTRAINT `FK_SMP_CNF_IDX_PRICE_CSTR_GROUP_ID_CSTR_GROUP_CSTR_GROUP_ID` FOREIGN KEY (`customer_group_id`) REFERENCES `{$installer->getTable('customer/customer_group')}` (`customer_group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_SMP_CNF_IDX_PRICE_ENTT_ID_CAT_PRD_ENTT_ENTT_ID` FOREIGN KEY (`entity_id`) REFERENCES `{$installer->getTable('catalog/product')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_SMP_CNF_IDX_PRICE_WS_ID_CORE_WS_WS_ID` FOREIGN KEY (`website_id`) REFERENCES `{$installer->getTable('core/website')}` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE
    );
");

$installer->endSetup();