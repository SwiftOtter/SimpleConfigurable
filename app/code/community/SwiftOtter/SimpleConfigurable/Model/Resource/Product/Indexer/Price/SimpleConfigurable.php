<?php
/**
 * 
 *
 * @author Joseph Maxwell
 * @copyright Swift Otter Studios, 10/21/2013
 * @package default
 **/

class SwiftOtter_SimpleConfigurable_Model_Resource_Product_Indexer_Price_SimpleConfigurable extends Mage_Catalog_Model_Resource_Product_Indexer_Price_Configurable
{
    /**
     * Calculate minimal and maximal prices for configurable product options
     * and apply it to final price
     *
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price_Configurable
     */
    protected function _applyConfigurableOption()
    {
        $write      = $this->_getWriteAdapter();
        $coaTable   = $this->getTable('SwiftOtter_SimpleConfigurable/Price_Temp');
        $copTable   = $this->_getConfigurableOptionPriceTable();

        $this->_prepareConfigurableOptionAggregateTable();
        $this->_prepareConfigurableOptionPriceTable();

        $price = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', 'price');
		$specialPrice = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', 'special_price');

        $select = $write->select()->distinct(true)
            ->from(array('i' => $this->_getDefaultFinalPriceTable()), array())
            ->join(
                array('l' => $this->getTable('catalog/product_super_link')),
                'l.parent_id = i.entity_id',
                array('parent_id', 'product_id'))
            ->columns(array('customer_group_id', 'website_id'), 'i')
            ->join(
                array('a' => $this->getTable('catalog/product_super_attribute')),
                'l.parent_id = a.product_id',
                array())
			->join(
				array('ip' => $this->getTable('catalog/product_index_price')),
				'ip.entity_id = l.product_id',
				array(
					'price' => 'ip.price',
					'tier_price' => 'ip.tier_price',
					'group_price' => 'ip.group_price',
                    'final_price' => 'ip.final_price'
				)
			)
//            ->join(
//                array('cp' => $this->getValueTable('catalog/product', 'int')),
//                'l.product_id = cp.entity_id AND cp.attribute_id = a.attribute_id AND cp.store_id = 0',
//                array())
//            ->join(
//                array('pr' => $price->getBackendTable()),
//                'pr.entity_id = l.product_id AND pr.attribute_id = ' . $price->getAttributeId(),
//                array('price' => 'value', 'tier_price' => 'value', 'group_price' => 'value')
//            )
            ->join(
                array('le' => $this->getTable('catalog/product')),
                'le.entity_id = l.product_id',
                array())

            ->where('le.required_options=0')
            ->group(array('l.parent_id', 'i.customer_group_id', 'i.website_id', 'l.product_id'));

        $query = $select->insertFromSelect($coaTable);
        $write->query($query);

        $select = $write->select()
            ->from(
                array($coaTable),
                array(
                    'parent_id', 'customer_group_id', 'website_id',
                    'MIN(price)', 'MAX(price)', 'MIN(tier_price)', 'MIN(group_price)'
                ))
            ->group(array('parent_id', 'customer_group_id', 'website_id'));

        $query = $select->insertFromSelect($copTable);
        $write->query($query);

        $table  = array('i' => $this->_getDefaultFinalPriceTable());
        $select = $write->select()
            ->join(
                array('io' => $copTable),
                'i.entity_id = io.entity_id AND i.customer_group_id = io.customer_group_id'
                .' AND i.website_id = io.website_id',
                array());

        /*
         * The final index table is being updated with values from the other table. In the array below,
         * the columns map to the final price index table.
         */

        $select->columns(array(
            'orig_price'  => new Zend_Db_Expr('io.min_price'),
            'price'       => new Zend_Db_Expr('io.min_price'),
            'min_price'   => new Zend_Db_Expr('io.min_price'),
            'max_price'   => new Zend_Db_Expr('io.max_price'),
            'tier_price'  => $write->getCheckSql('i.tier_price IS NOT NULL', 'i.tier_price + io.tier_price', 'NULL'),
            'group_price' => $write->getCheckSql(
                    'i.group_price IS NOT NULL',
                    'i.group_price + io.group_price', 'NULL'
                ),
        ));

        $query = $select->crossUpdateFromSelect($table);
        $write->query($query);

        $this->_buildConfigurableDataSet();

        $write->delete($coaTable);
        $write->delete($copTable);

        return $this;
    }

    protected function _buildConfigurableDataSet()
    {
        $write = $this->_getWriteAdapter();
        $table = $this->getTable('SwiftOtter_SimpleConfigurable/Price_Index');
        $coaTable = $this->getTable('SwiftOtter_SimpleConfigurable/Price_Temp');

        $toDelete = $write->select()->from($coaTable);
        $write->beginTransaction();

        foreach ($write->fetchAll($toDelete) as $data) {
            $write->delete($table, array(
                'entity_id' => $data['parent_id'],
                'customer_group_id' => $data['customer_group_id'],
                'website_id' => $data['website_id']
            ));
        }
        $write->commit();

        $select = $write->select()
            ->from(
                array($coaTable),
                array(
                    'parent_id', 'customer_group_id', 'website_id',
                    'MIN(price)', 'MAX(price)', 'MIN(final_price)', 'MAX(final_price)'
                ))
            ->group(array('parent_id', 'customer_group_id', 'website_id'));

        $query = $select->insertFromSelect($table);
        $write->query($query);
    }
}