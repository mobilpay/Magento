<?php
// namespace Netopia\Netcard\Controller\Adminhtml\Tools;

// use Magento\Framework\App\Action\Context;
// use Magento\Framework\App\ResponseInterface;
// use Magento\Framework\View\Element\Template;
// use Magento\Framework\View\Result\PageFactory;
// use Magento\Framework\App\Action\Action;
// use Magento\Framework\App\Config\ScopeConfigInterface;

// class Xml extends Action
// {
//     /**
//      * @var PageFactory
//      */
//     private $pageFactory;
//     protected $_scopeConfig;

//     /**
//      * Index constructor.
//      * @param Context $context
//      * @param PageFactory $pageFactory
//      * @param ScopeConfigInterface $scopeConfig
//      */

//     public function __construct(
//         Context $context,
//         PageFactory $pageFactory,
//         ScopeConfigInterface $scopeConfig
//     )
//     {
//         parent::__construct($context);
//         $this->pageFactory = $pageFactory;
//         $this->_scopeConfig = $scopeConfig;
//     }

//     /**
//      * Execute action based on request and return result
//      *
//      * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
//      * @throws \Magento\Framework\Exception\NotFoundException
//      */
//     public function execute()
//     {
//         $ntpDeclare = array (
//                       'completeDescription' => $this->getConfigData('conditions/complete_description'),
//                       'priceCurrency' => $this->getConfigData('conditions/price_currency'),
//                       'contactInfo' => $this->getConfigData('conditions/contact_info'),
//                       'mandatoryPages' => $this->getConfigData('conditions/mandatory_pages'),
//                       'forbiddenBusiness' => $this->getConfigData('forbidden_business')
//                     );

//         $ntpUrl = array(
//                   'termsAndConditions' => $this->getConfigData('terms_conditions_url'),
//                   'privacyPolicy' => $this->getConfigData('privacy_policy_url'),
//                   'deliveryPolicy' => $this->getConfigData('delivery_policy_url'),
//                   'returnAndCancelPolicy' => $this->getConfigData('return_cancel_policy_url'),
//                   'gdprPolicy' => $this->getConfigData('gdpr_policy_url')
//                   );

//         $ntpImg = array(
//                   'netopiaLogo' => $this->getConfigData('netopia_logo')
//                 );

//         if($this->makeActivateXml($ntpDeclare, $ntpUrl, $ntpImg )) {
//           $xmlResponse = array(
//             'status' =>  true,
//             'msg' => 'Your Request is sent succefuly' );
//         } else {
//           $xmlResponse = array(
//             'status' =>  false,
//             'msg' => 'Your Request is Failed' );
//         }
//         /*
//         * Send response to JS
//         */
//         echo (json_encode($xmlResponse));
           
//     }

//     public function getConfigData($field)
//     {
//         $str = 'payment/net_card/'.$field;
//         return $this->_scopeConfig->getValue($str);
//     }

//     public function has_ssl() {
//         $domain = "https://netopia-payments.com";
//         // $domain = $_SERVER['HTTP_HOST'];
//         $stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
//         $read = fopen($domain, "rb", false, $stream);
//         $cont = stream_context_get_params($read);
//         $var = ($cont["options"]["ssl"]["peer_certificate"]);
//         $result = (!is_null($var)) ? true : false;
//         return $result;
//     }

//     function makeActivateXml($declareatins, $urls, $images) {
    
//     $domtree = new \DOMDocument('1.0', 'UTF-8');
//     $domtree->formatOutput = true;
//     $xmlRoot = $domtree->createElement("xml");
//     $xmlRoot = $domtree->appendChild($xmlRoot);

//     $sac_key = $domtree->createElement("sac_key", $this->getConfigData('api/signature'));
//     $xmlRoot->appendChild($sac_key);
    
//     $agr = $domtree->createElement("agrremnts");
//     $xmlRoot->appendChild($agr);

//     $declare = $domtree->createElement("declare");
//     $agr->appendChild($declare);

//     foreach ($declareatins as $key => $value) {
//         $declare->appendChild($domtree->createElement($key, $value ));
//     }

//     $url = $domtree->createElement("urls");
//     $agr->appendChild($url);

//     foreach ($urls as $key => $value) {
//         $url->appendChild($domtree->createElement($key, $value ));
//     }

//     $img = $domtree->createElement("images");
//     $agr->appendChild($img);

//     foreach ($images as $key => $value) {
//         $img->appendChild($domtree->createElement($key, $value ));
//     }

//     $ssl = $domtree->createElement("ssl", $this->has_ssl());
//     $agr->appendChild($ssl);

//     $last_update = $domtree->createElement("lastUpdate", date("Y/m/d H:i:s"));
//     $last_update = $xmlRoot->appendChild($last_update);

//     $last_update = $domtree->createElement("platform", 'Magento 2');
//     $last_update = $xmlRoot->appendChild($last_update);

//     $this->agreementExist();
//     $result = $domtree->save($this->_getUploadDir().$this->getConfigData('api/signature').'_agreements.xml') ? true : false;
//     return $result;
//     }

//     protected function _getUploadDir()
//     {
//         $xmlDir = getcwd().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'Netopia'.DIRECTORY_SEPARATOR.'Netcard'.DIRECTORY_SEPARATOR.'etc'.DIRECTORY_SEPARATOR.'certificates'.DIRECTORY_SEPARATOR;
//         return $xmlDir;
//     }

//     public function agreementExist(){
//       $agreemnetFile = $this->_getUploadDir().$this->getConfigData('api/signature').'_agreements.xml';
//       if (file_exists($agreemnetFile)) {
//          unlink($agreemnetFile);
//       }
//     }

//     function makeActivateJson($declareatins, $urls, $images) {
//       $jsonData = array(
//         "sac_key" => $this->getConfigData('api/signature'),
//         "agrremnts" => array(
//               "declare" => $declareatins,
//               "urls"    => $urls,
//               "images"  => $images,
//               "ssl", $this->has_ssl()
//             ),
//         "lastUpdate" => date("Y/m/d H:i:s"),
//         "platform" => 'Magento 2'
//       );
      
//       $post_data = json_encode($jsonData, JSON_FORCE_OBJECT);
//       return $post_data;
//     }
// }
