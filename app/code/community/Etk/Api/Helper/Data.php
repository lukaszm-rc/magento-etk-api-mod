<?php
/**
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class Etk_Api_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param string $attrSetName
     * @return string
     */
    public function getAttributeSetIdByName($attrSetName, $entityType = 'catalog_product')
    {
        return Mage::getSingleton('catalog/config')
            ->getAttributeSetId($entityType, $attrSetName);
    }
}