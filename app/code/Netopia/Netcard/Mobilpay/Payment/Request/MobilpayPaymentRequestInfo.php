<?php
namespace Netopia\Netcard\Mobilpay\Payment\Request;

use \Netopia\Netcard\Mobilpay\Payment\Product\MobilpayPaymentProductCode;
use \Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestAbstract;

class MobilpayPaymentRequestInfo extends MobilpayPaymentRequestAbstract
{
    const ERROR_LOAD_FROM_XML_PRODUCT_CODE_MISSING = 0x30000001;

    public $product = null;

    function __construct ()
    {

        parent::__construct();
        $this->type = self::PAYMENT_TYPE_INFO;
    }

    protected function _loadFromXml (\DOMElement $elem)
    {

        parent::_parseFromXml($elem);

        $elems = $elem->getElementsByTagName('product_code');
        if ($elems->length != 1)
        {
            throw new Exception('MobilpayPaymentRequestInfo::loadFromXml failed; product element is missing', self::ERROR_LOAD_FROM_XML_PRODUCT_CODE_MISSING);
        }

        $this->product = new MobilpayPaymentProductCode($elems->item(0));

        return $this;
    }

    protected function _prepare ()
    {

        if (is_null($this->signature) || ! ($this->product instanceof MobilpayPaymentProductCode) || ! $this->orderId)
        {
            throw new Exception('One or more mandatory properties are invalid!', self::ERROR_PREPARE_MANDATORY_PROPERTIES_UNSET);
        }

        $this->_xmlDoc = new DOMDocument('1.0', 'utf-8');
        $rootElem = $this->_xmlDoc->createElement('order');

        //set id attribute
        $xmlAttr = $this->_xmlDoc->createAttribute('id');
        $xmlAttr->nodeValue = $this->orderId;
        $rootElem->appendChild($xmlAttr);

        //set request type attribute
        $xmlAttr = $this->_xmlDoc->createAttribute('type');
        $xmlAttr->nodeValue = $this->type;
        $rootElem->appendChild($xmlAttr);

        //set timestamp attribute
        $xmlAttr = $this->_xmlDoc->createAttribute('timestamp');
        $xmlAttr->nodeValue = date('YmdHis');
        $rootElem->appendChild($xmlAttr);

        $xmlElem = $this->_xmlDoc->createElement('signature');
        $xmlElem->nodeValue = $this->signature;
        $rootElem->appendChild($xmlElem);

        $xmlElem = $this->product->createXmlElement($this->_xmlDoc);
        $rootElem->appendChild($xmlElem);

        $this->_xmlDoc->appendChild($rootElem);

        return $this;
    }

}
