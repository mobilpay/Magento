<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 */
class Mobilpay_Cc_Block_Redirect extends Mage_Core_Block_Abstract
{
	protected function _toHtml()
	{
		$cc = Mage::getModel('cc/cc');
			
		$form = new Varien_Data_Form();

		$paymentUrl = ($cc->getConfigData('debug') == 1) ? 'http://sandboxsecure.mobilpay.ro' : 'https://secure.mobilpay.ro';

		$form->setAction($paymentUrl)
		->setId('cc')
		->setName('cc')
		->setMethod('POST')
		->setUseContainer(true);		
		
		$form->addField('data', 'hidden', array('name'=>'data', 'value'=>$cc->getFormData()));
		$form->addField('env_key', 'hidden', array('name'=>'env_key', 'value'=>$cc->getFormKey()));
		$form->addField('cipher', 'hidden', array('name'=>'cipher', 'value'=>$cc->getFormCipher()));
		$form->addField('iv', 'hidden', array('name'=>'iv', 'value'=>$cc->getFormIv()));
		$html = '<html><body>';
		$html.= $this->__('You will be redirected to MobilPay in a few seconds.');
		$html.= $form->toHtml();
		$html.= '<script type="text/javascript">document.getElementById("cc").submit();</script>';
		$html.= '</body></html>';

		return $html;
	}
}
