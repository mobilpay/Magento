<?php
namespace Netopia\Netcard\Controller\Adminhtml\Tools;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Jsonrequest extends Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;
    protected $_scopeConfig;
    
    protected $jsonData;
    protected $encData;

    private $outEnvKey  = null;
    private $outEncData = null;

    const ERROR_LOAD_X509_CERTIFICATE = 0x10000001;
    const ERROR_ENCRYPT_DATA          = 0x10000002;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $ntpDeclare = array (
                      'completeDescription' => (bool) ($this->getConfigData('conditions/complete_description')) ? true : false,
                      'priceCurrency' =>  (bool) ($this->getConfigData('conditions/price_currency')) ? true : false,
                      'contactInfo' =>  (bool) ($this->getConfigData('conditions/contact_info')) ? true : false,
                      'forbiddenBusiness' =>  (bool) ($this->getConfigData('forbidden_business')) ? true : false
                    );


        $ntpUrl = array(
                  'termsAndConditions' => $this->parsURL($this->getConfigData('terms_conditions_url')),
                  'privacyPolicy' => $this->parsURL($this->getConfigData('privacy_policy_url')),
                  'deliveryPolicy' => $this->parsURL($this->getConfigData('delivery_policy_url')),
                  'returnAndCancelPolicy' => $this->parsURL($this->getConfigData('return_cancel_policy_url')),
                  'gdprPolicy' => $this->parsURL($this->getConfigData('gdpr_policy_url'))
                  );

        $ntpImg = array(
                  'netopiaLogo' => (bool) ($this->getConfigData('netopia_logo')) ? true : false
                );
        
        $this->jsonData = $this->makeActivateJson($ntpDeclare, $ntpUrl, $ntpImg);
        

        $this->encrypt();
        
        $this->encData = array(
          'env_key' => $this->getEnvKey(),
          'data'    => $this->getEncData()
          );
        
        
        
        $result = json_decode($this->sendJsonCurl()); //  CURL as Json

        
        
        if($result->code == 200) {
          $response = array(
            'status' =>  true,
            'msg' => 'succesfully sent your request' );
        } else {
          $response = array(
            'status' =>  false,
            'msg' => 'Error, '.$result->message );
        }
        /*
        * Send response to JS
        */
        echo (json_encode($response));
           
    }

    public function getConfigData($field)
    {
        $str = 'payment/net_card/'.$field;
        return $this->_scopeConfig->getValue($str);
    }

    public function has_ssl() {
        $domain = 'https://'.$_SERVER['HTTP_HOST'];
        $stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
        if( $read = @fopen($domain, "rb", false, $stream)){
            $cont = stream_context_get_params($read);
            if(isset($cont["options"]["ssl"]["peer_certificate"])){
                $var = ($cont["options"]["ssl"]["peer_certificate"]);
                $result = (!is_null($var)) ? true : false;
            }else {
                $result = false;
            }            
        } else {
            $result = false;
        }
        
        return $result;
    }


    protected function _getUploadDir()
    {
        $certificateDir = getcwd().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'Netopia'.DIRECTORY_SEPARATOR.'Netcard'.DIRECTORY_SEPARATOR.'etc'.DIRECTORY_SEPARATOR.'certificates'.DIRECTORY_SEPARATOR;
        return $certificateDir;
    }


    function makeActivateJson($declareatins, $urls, $images) {
      $jsonData = array(
        "sac_key" => $this->getConfigData('api/signature'),
        "agreements" => array(
              "declare" => $declareatins,
              "urls"    => $urls,
              "images"  => $images,
              "ssl"     => $this->has_ssl()
            ),
        "lastUpdate" => date("c", strtotime(date("Y-m-d H:i:s"))), // To have Date & Time format on RFC3339
        "platform" => 'Magento 2'
      );
      
      $post_data = json_encode($jsonData, JSON_FORCE_OBJECT);
      return $post_data;
    }

    
    public function encrypt()
      {
        $x509FilePath = $this->_getUploadDir().$this->getConfigData('mode/live_public_key');
        $publicKey = openssl_pkey_get_public("file://{$x509FilePath}");
        if($publicKey === false)
          {
            $this->outEncData = null;
            $this->outEnvKey  = null;
            $errorMessage = "Error while loading X509 public key certificate! Reason:";
            while(($errorString = openssl_error_string()))
            {
              $errorMessage .= $errorString . "\n";
            }
            throw new \Exception($errorMessage, self::ERROR_LOAD_X509_CERTIFICATE);
          }
        $srcData = $this->jsonData;
        $publicKeys = array($publicKey);
        $encData  = null;
        $envKeys  = null;
        $cipher_algo = 'RC4';
        $result   = openssl_seal($srcData, $encData, $envKeys, $publicKeys, $cipher_algo);
        if($result === false)
          {
            $this->outEncData = null;
            $this->outEnvKey  = null;
            $errorMessage = "Error while encrypting data! Reason:";
            while(($errorString = openssl_error_string()))
            {
              $errorMessage .= $errorString . "\n";
            }
            throw new Exception($errorMessage, self::ERROR_ENCRYPT_DATA);
          }
        $this->outEncData = base64_encode($encData);
        $this->outEnvKey  = base64_encode($envKeys[0]);  
      }

      public function getEnvKey()
        {
          return $this->outEnvKey;
        }

      public function getEncData()
        {
          return $this->outEncData;
        }

      public function sendJsonCurl() {
        $url = 'https://netopia-payments-user-service-api-fqvtst6pfa-ew.a.run.app/financial/agreement/add2';
        $ch = curl_init($url);

        $payload = json_encode($this->encData);


        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result = curl_exec($ch);

        if (!curl_errno($ch)) {
              switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                  case 200:  # OK
                      $arr = array(
                          'code'    => $http_code,
                          'message' => "You send your request, successfully",
                          'data'    => json_decode($result)
                      );
                      break;
                  case 404:  # Not Found
                      $arr = array(
                          'code'    => $http_code,
                          'message' => "You send request to wrong URL"
                      );
                      break;
                  case 400:  # Bad Request
                      $arr = array(
                          'code'    => $http_code,
                          'message' => "You send Bad Request"
                      );
                      break;
                  case 405:  # Method Not Allowed
                      $arr = array(
                          'code'    => $http_code,
                          'message' => "Your method of sending data are Not Allowed"
                      );
                      break;
                  default:
                      $arr = array(
                          'code'    => $http_code,
                          'message' => "Opps! Something is wrong, verify how you send data & try again!!!"
                      );
              }
          } else {
              $arr = array(
                  'code'    => 0,
                  'message' => "Opps! There is some problem, you are not able to send data!!!"
              );
          }
        
        // Close cURL resource
        curl_close($ch);
        
        $finalResult = json_encode($arr, JSON_FORCE_OBJECT);
        return $finalResult;
      }

      // public function sendJsonCurlArray() {
      //   $fields_string = '';
      //   $url = 'https://netopia-payments-user-service-api-fqvtst6pfa-ew.a.run.app/financial/agreement/add2';
        
      //   $ch = curl_init();
      //   curl_setopt($ch,CURLOPT_URL, $url);
      //   curl_setopt($ch,CURLOPT_POST, count($this->encData));

      //   // Set the content type to multipart/form-data
      //   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data'));

      //   // Attach the array as a STRING  to the POST fields
      //   curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encData);

      //   // Return response instead of outputting
      //   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      //   // Execute the POST request
      //   $result = curl_exec($ch);

      //   if (!curl_errno($ch)) {
      //         switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
      //             case 200:  # OK
      //                 $arr = array(
      //                     'code'    => $http_code,
      //                     'message' => "You send your request, successfully",
      //                     'data'    => json_decode($result)
      //                 );
      //                 break;
      //             case 404:  # Not Found
      //                 $arr = array(
      //                     'code'    => $http_code,
      //                     'message' => "You send request to wrong URL"
      //                 );
      //                 break;
      //             case 400:  # Bad Request
      //                 $arr = array(
      //                     'code'    => $http_code,
      //                     'message' => "You send Bad Request"
      //                 );
      //                 break;
      //             case 405:  # Method Not Allowed
      //                 $arr = array(
      //                     'code'    => $http_code,
      //                     'message' => "Your method of sending data are Not Allowed"
      //                 );
      //                 break;
      //             default:
      //                 $arr = array(
      //                     'code'    => $http_code,
      //                     'message' => "Opps! Something happened, verify how you send data & try again!!!->".$http_code
      //                 );
      //         }
      //     } else {
      //         $arr = array(
      //             'code'    => 0,
      //             'message' => "Opps! There is some problem, you are not able to send data!!!"
      //         );
      //     }
        
      //   // Close cURL resource
      //   curl_close($ch);
        
      //   $finalResult = json_encode($arr, JSON_FORCE_OBJECT);
      //   return $finalResult;
      // }

      public function parsURL($pageUrl) {
        $hostName = parse_url($pageUrl, PHP_URL_HOST);
        if(!is_null($hostName)) {
         if($this->verifyHost($hostName))
            return $pageUrl;
          else {
            $tmpPageUrl = substr($pageUrl, strpos($pageUrl, $hostName) + strlen($hostName));
            return 'https://'.$_SERVER['HTTP_HOST'].$tmpPageUrl;
          }
        }else {
          return $this->generateURL($pageUrl);
        }
      }
  
      public function verifyHost($hostName) {
        if($hostName === $_SERVER['HTTP_HOST'])
          return true;
        else
          return false;
      }
  
      public function generateURL($pageUrl) {
          return 'https://'.$_SERVER['HTTP_HOST'].'/'.$pageUrl;
      }
}
