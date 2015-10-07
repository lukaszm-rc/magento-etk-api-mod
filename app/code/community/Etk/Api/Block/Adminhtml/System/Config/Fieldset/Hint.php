<?php

/**
 * @author Lukasz Mazurek <lukasz.mazurek@redcart.pl>
 */
class Etk_Api_Block_Adminhtml_System_Config_Fieldset_Hint extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	protected $_template = 'Etk/Api/system/config/fieldset/hint.phtml';

	public function render(Varien_Data_Form_Element_Abstract $element) {
		return $this->toHtml();
	}

}
