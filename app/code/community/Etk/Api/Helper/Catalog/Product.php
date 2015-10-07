<?php

class Etk_Api_Helper_Catalog_Product extends Mage_Core_Helper_Abstract {

	const CATEGORIES_SEPARATOR_PATH_XML = 'Etk_Api/config/categories_separator';

	private $configurableAttributesType; // int/string

	/**
	 * @param Mage_Catalog_Model_Product $product
	 * @param array $simpleSkus
	 * @param array $priceChanges
	 * @return Etk_Api_Helper_Catalog_Product
	 */

	public function associateProducts(Mage_Catalog_Model_Product $product, $simpleSkus, $priceChanges = array (), $configurableAttributes = array ()) {
		if (!empty($simpleSkus)) {
			$newProductIds = Mage::getModel('catalog/product')->getCollection()
					->addFieldToFilter('sku', array ('in' => (array) $simpleSkus))
					->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
					->getAllIds();

			$oldProductIds = Mage::getModel('catalog/product_type_configurable')->setProduct($product)->getUsedProductCollection()
					->addAttributeToSelect('*')
					->addFilterByRequiredOptions()
					->getAllIds();

			$usedProductIds = array_diff($newProductIds, $oldProductIds);

			if (!empty($newProductIds) && $product->isConfigurable()) {
				$this->_initConfigurableAttributesData($product, $newProductIds, $priceChanges, $configurableAttributes);
			}

			if (!empty($usedProductIds) && $product->isGrouped()) {
				$relations = array_fill_keys($usedProductIds, array ('qty' => 0, 'position' => 0));
				$product->setGroupedLinkData($relations);
			}
		}

		return $this;
	}

	/**
	 * @param array $categoryNames
	 * @return array
	 */
	public function getCategoryIdsByNames($categoryNames) {
		$categories = array ();
		$separator = $this->_getCatagoriesSeparator();
		foreach ($categoryNames as $category) {
			if (is_string($category) && !is_numeric($category)) {
				$pieces = explode($separator, $category);
				$addCategories = array ();
				$parentIds = array ();
				foreach ($pieces as $level => $name) {
					$collection = Mage::getModel('catalog/category')->getCollection()
							->setStoreId(0)
							->addFieldToFilter('level', $level + 2)
							->addAttributeToFilter('name', $name);
					if (!empty($parentIds)) {
						$collection->getSelect()->where('parent_id IN (?)', $parentIds);
					}
					$parentIds = array ();
					if ($collection->count()) {
						foreach ($collection as $category) {
							$addCategories[] = (int) $category->getId();
							if ($level > 0) {
								$addCategories[] = (int) $category->getParentId();
							}
							$parentIds[] = $category->getId();
						}
					}
				}
				if (!empty($addCategories)) {
					$categories = array_merge($categories, $addCategories);
				}
			}
		}

		return !empty($categories)?$categories:$categoryNames;
	}

	public function getAttributeKeyByCode($attributeCode) {
		$attribute = Mage::getModel('catalog/product')->getResource()->getAttribute($attributeCode);
		if ($attribute) {
			return $attribute->getId();
		}
	}

	/**
	 * @param string $attributeCode
	 * @param string $label
	 * @return mixed
	 */
	public function getOptionKeyByLabel($attributeCode, $label) {
		$attribute = Mage::getModel('catalog/product')->getResource()
				->getAttribute($attributeCode);
		if ($attribute && $attribute->getId() && $attribute->usesSource()) {
			foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
				if ($label == $option['label']) {
					return $option['value'];
				}
			}
		}

		return $label;
	}

	protected function _getCatagoriesSeparator() {
		return Mage::getStoreConfig(self::CATEGORIES_SEPARATOR_PATH_XML);
	}

	/**
	 * @param Mage_Catalog_Model_Product $mainProduct
	 * @param array $simpleProductIds
	 * @param array $priceChanges
	 * @return Etk_Api_Helper_Catalog_Product
	 */
	protected function _initConfigurableAttributesData(Mage_Catalog_Model_Product $mainProduct, $simpleProductIds, $priceChanges = array (), $configurableAttributes = array ()) {
		if (!$mainProduct->isConfigurable() || empty($simpleProductIds)) {
			return $this;
		}

		$mainProduct->setConfigurableProductsData(array_flip($simpleProductIds));
		$productType = $mainProduct->getTypeInstance(true);
		$productType->setProduct($mainProduct);
		$attributesData = $productType->getConfigurableAttributesAsArray();

		if (empty($attributesData)) {
			// Auto generation if configurable product has no attribute
			$attributeIds = array ();
			foreach ($productType->getSetAttributes() as $attribute) {
				if ($productType->canUseAttribute($attribute)) {
					$attributeIds[] = $attribute->getAttributeId();
				}
			}
			$productType->setUsedProductAttributeIds($attributeIds);
			$attributesData = $productType->getConfigurableAttributesAsArray();
		}
		if (!empty($configurableAttributes)) {
			$_before = $configurableAttributes;
			$this->_chechAttributesType($configurableAttributes);
			$_after = $configurableAttributes;
			$log = file_put_contents('magento-api-mod.log', print_r(['before' => $_before, 'after' => $_after, 'attributesData' => $attributesData], true), FILE_APPEND);

			foreach ($attributesData as $idx => $val) {
				if ($this->configurableAttributesType == 'string') {
					if (!in_array($val['label'], $configurableAttributes)) {
						unset($attributesData[$idx]);
					}
				}
				else {
					if (!in_array($val['attribute_id'], $configurableAttributes)) {
						unset($attributesData[$idx]);
					}
				}
			}
		}

		$products = Mage::getModel('catalog/product')->getCollection()
				->addIdFilter($simpleProductIds);

		if (count($products)) {
			foreach ($attributesData as &$attribute) {
				$attribute['label'] = $attribute['frontend_label'];
				$attributeCode = $attribute['attribute_code'];
				foreach ($products as $product) {
					$product->load($product->getId());
					$optionId = $product->getData($attributeCode);
					$isPercent = 0;
					$priceChange = 0;
					if (!empty($priceChanges) && isset($priceChanges[$attributeCode])) {
						$optionText = $product->getResource()
								->getAttribute($attribute['attribute_code'])
								->getSource()
								->getOptionText($optionId);
						if (isset($priceChanges[$attributeCode][$optionText])) {
							if (false !== strpos($priceChanges[$attributeCode][$optionText], '%')) {
								$isPercent = 1;
							}
							$priceChange = preg_replace('/[^0-9\.,-]/', '', $priceChanges[$attributeCode][$optionText]);
							$priceChange = (float) str_replace(',', '.', $priceChange);
						}
					}
					$attribute['values'][$optionId] = array (
						'value_index' => $optionId,
						'is_percent' => $isPercent,
						'pricing_value' => $priceChange,
					);
				}
			}
			$mainProduct->setConfigurableAttributesData($attributesData);
		}

		return $this;
	}

	protected function _chechAttributesType(&$configurableAttributes) {
		foreach ($configurableAttributes as $key => $_attr) {
			if (!is_numeric($_attr)) {
				$configurableAttributes[$key] = $this->getAttributeKeyByCode($_attr);
				$this->configurableAttributesType = 'string';
			}
		}
		$this->configurableAttributesType = 'int';
		return $this->configurableAttributesType;
	}
}
