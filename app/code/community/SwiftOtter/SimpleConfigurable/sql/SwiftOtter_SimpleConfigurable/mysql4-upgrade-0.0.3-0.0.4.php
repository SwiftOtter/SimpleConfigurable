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

$connection = $installer->getConnection();

$connection->dropColumn($installer->getTable('catalog/product_price_indexer_cfg_option_aggregate_tmp'), 'final_price');
$connection->dropColumn($installer->getTable('catalog/product_price_indexer_cfg_option_aggregate_idx'), 'final_price');

$table = $connection->createTableByDdl(
    $installer->getTable('catalog/product_price_indexer_cfg_option_aggregate_tmp'),
    $installer->getTable('SwiftOtter_SimpleConfigurable/Price_Temp')
);
$connection->createTable($table);

$connection->addColumn($installer->getTable('SwiftOtter_SimpleConfigurable/Price_Temp'), 'final_price', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'length'    => '12,4',
    'comment'   => 'Final price',
));

$installer->endSetup();