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
 * @copyright Swift Otter Studios, 2/25/14
 * @package default
 **/


class SwiftOtter_SimpleConfigurable_Block_Frontend_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer_Configurable
{
    /**
     * Get product thumbnail image
     *
     * @return Mage_Catalog_Model_Product_Image
     */
    public function getProductThumbnail()
    {
        if (!is_null($this->_productThumbnail)) {
            return $this->_productThumbnail;
        }

        $children = $this->getItem()->getChildren();
        $product = $this->getProduct();

        if (count($children) >= 1) {
            /** @var Mage_Sales_Model_Quote_Item $childItem */
            $childItem = $children[0];

            if (is_object($childItem) && is_object($childItem->getProduct()) &&
                $childItem->getProduct()->getThumbnail() && $childItem->getProduct()->getThumbnail() !== 'no_selection') {
                $product = $childItem->getProduct();
            }
        }

        return $this->helper('catalog/image')->init($product, 'thumbnail');
    }

    /**
     * Get list of all otions for product
     *
     * @return array
     */
    public function getOptionList()
    {
        /* @var SwiftOtter_SimpleConfigurable_Helper_Configuration $helper */
        $helper = Mage::helper('SwiftOtter_SimpleConfigurable/Configuration');
        $options = $helper->getSimpleConfigurableOptions($this->getItem());
        return $options;
    }

    /**
     * Retrieve item messages
     * Return array with keys
     *
     * text => the message text
     * type => type of a message
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = array();
        $quoteItem = $this->getItem();

        // Add basic messages occuring during this page load
        $baseMessages = $quoteItem->getMessage(false);

        /** @var Mage_Sales_Model_Quote_Item $childItem */
        foreach ($quoteItem->getChildren() as $childItem) {
            $baseMessages = array_merge($baseMessages, $childItem->getMessage(false));
        }

        $baseMessages = array_unique($baseMessages);

        if ($baseMessages) {
            foreach ($baseMessages as $message) {
                $messages[] = array(
                    'text' => $message,
                    'type' => $quoteItem->getHasError() ? 'error' : 'notice'
                );
            }
        }

        // Add messages saved previously in checkout session
        $checkoutSession = $this->getCheckoutSession();
        if ($checkoutSession) {
            /* @var $collection Mage_Core_Model_Message_Collection */
            $collection = $checkoutSession->getQuoteItemMessages($quoteItem->getId(), true);
            if ($collection) {
                $additionalMessages = $collection->getItems();
                foreach ($additionalMessages as $message) {
                    /* @var $message Mage_Core_Model_Message_Abstract */
                    $messages[] = array(
                        'text' => $message->getCode(),
                        'type' => ($message->getType() == Mage_Core_Model_Message::ERROR) ? 'error' : 'notice'
                    );
                }
            }
        }

        return $messages;
    }
}