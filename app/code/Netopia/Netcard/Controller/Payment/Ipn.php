<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;


use \Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestInfo;
use \Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestAbstract;



class Ipn extends Action implements CsrfAwareActionInterface {
    
    
    protected $resultPateFactory;
    protected $_orderFactory;
    protected $_moduleDirReader;
    protected $_scopeConfig;
    protected $_order;
    protected $_objPmReq;
    protected $_builderInterface;
    protected $_newOrderStatus;

    /**
     * Ipn constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Reader $reader
     * @param ScopeConfigInterface $scopeConfig
     * @param BuilderInterface $builderInterface
     * @param Order $orderFactory
     */

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Reader $reader,
        ScopeConfigInterface $scopeConfig,
        BuilderInterface $builderInterface,
        Order $orderFactory
    )
    {
        parent::__construct($context);
        $this->_orderFactory = $orderFactory;
        $this->resultPateFactory = $resultPageFactory;
        $this->_moduleDirReader = $reader;
        $this->_scopeConfig = $scopeConfig;
        $this->_builderInterface = $builderInterface;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $objPmReq = $this->_processRequest();

        if(!$objPmReq) { 
           die('Nu au fost trimise date');
        }

        try
        {
           if ($objPmReq instanceof MobilpayPaymentRequestInfo)
            {
                $this->_processRequestProduct($objPmReq);
            } else
            {
                $this->_processNotification($objPmReq);
            }
        } catch (\Exception $e)
        {
            $this->_sendResponse(MobilpayPaymentRequestAbstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode()+100, $e->getMessage());
        }
    }

    
    protected function _processRequestProduct($objPmReq)
    {
        header('Content-type: application/xml');
        echo $objPmReq->getXml()->saveXML();
        die();
    }

    protected function _initData($objPmReq)
    {

        $this->_objPmReq = $objPmReq;
        $order_id = $objPmReq->orderId;
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $this->_order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($order_id);
        $this->_newOrderStatus = 'pending';

    }

    protected function _processNotification($objPmReq)
    {
        $errorCode = 0;
        $errorType = MobilpayPaymentRequestAbstract::CONFIRM_ERROR_TYPE_NONE;
        $errorMessage = '';
        $this->_initData($objPmReq);

        switch ($objPmReq->objPmNotify->action) {
            case 'confirmed':
                if ($objPmReq->objPmNotify->errorCode == 0) {
                    $this->_handleCapture();
                }
                break;

            case 'confirmed_pending':
                if ($objPmReq->objPmNotify->errorCode != 0) {
                    $this->_handlePaymentDenial();
                } else {
                    $this->_handleCapturePending();
                }
                break;

            case 'paid_pending':
                if ($objPmReq->objPmNotify->errorCode != 0) {
                    $this->_handlePaymentDenial();
                } else {
                    $this->_handleCapturePending();
                }
                break;

            case 'paid':
                if($objPmReq->objPmNotify->errorCode == 0) {
                    $this->_handleAuthorization(0);
                } elseif ($objPmReq->objPmNotify->errorCode == 56) {
                    // Nothing
                }  else {
                    $this->_handlePaymentDenial();
                }
                break;

            case 'canceled':
                if ($objPmReq->objPmNotify->errorCode == 0) {

                    $this->_handleCancel();
                }
                break;

            case 'credit':
                if ($objPmReq->objPmNotify->errorCode == 0) {

                    $this->_handleRefund();
                }
                break;

            default:
                $errorType = MobilpayPaymentRequestAbstract::CONFIRM_ERROR_TYPE_PERMANENT;
                $errorCode = MobilpayPaymentRequestAbstract::ERROR_CONFIRM_INVALID_ACTION;
                $errorMessage = 'mobilpay_refference_action paramaters is invalid';
                break;
        }

        return $this->_sendResponse($errorType, $errorCode, $errorMessage);

    }

    protected function _createInvoice($ap)
    {

        if (!$this->_order->canInvoice()) {
            //when order cannot create invoice, need to have some logic to take care
            $this->_order->addStatusToHistory($this->_order->getStatus(), // keep order status/state
                'Eroare la crearea facturii', $notified = true);
        }

        $this->_order->getPayment()->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId) . $ap);
        $this->_order->getPayment()->place();
        $this->_order->save();
    }

    protected function _handleCancel()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setParentTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $payment->registerVoidNotification();
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder($transaction, $this->_objPmReq->objPmNotify->errorMessage. " - Tranzactie anulata - ");
        $this->_order->setStatus(Order::STATE_CANCELED); // Order status Can be even set as STATE_PENDING_PAYMENT
        $this->_order->save();
    }

    protected function _handleAuthorization($underVerification = true)
    {
        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder($transaction, $this->_objPmReq->objPmNotify->errorMessage . " | ");
        $payment->setIsTransactionClosed(0);

        if (!$underVerification) {
            $payment->setIsTransactionPending(false);
            $this->_createInvoice(":p");
            $payment->registerAuthorizationNotification($this->_objPmReq->objPmNotify->processedAmount);
            $this->_order->setStatus(Order::STATE_PAYMENT_REVIEW);
        } else {
            $payment->setIsTransactionPending(true);
            $this->_order->setStatus(Order::STATE_PAYMENT_REVIEW);
            $this->_order->sendNewOrderEmail();
        }
        $this->_order->save();
    }

    /**
     * Handeling such case like:
     * Card Expire
     * Cod CVV2/CCV incorect
     */
    protected function _handlePaymentDenial()
    {
        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setLastTransId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $payment->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
        );
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder($transaction, $this->_objPmReq->objPmNotify->errorMessage);

        $payment->setIsTransactionClosed(0);
        $payment->save();
        $this->_order->save();
    }

    protected function _handleRefund()
    {
        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $payment->setParentTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);

        $payment->addTransactionCommentsToOrder($transaction, $this->_objPmReq->objPmNotify->errorMessage);
        $payment->setIsTransactionClosed(true);

        $this->_order->getBaseTotalRefunded(-1 * $this->_objPmReq->objPmNotify->processedAmount);
        $payment->registerRefundNotification(-1 * $this->_objPmReq->objPmNotify->processedAmount);
        $this->_order->setTotalRefunded($this->_order->getTotalRefunded() - $this->_objPmReq->objPmNotify->processedAmount);

        $this->_order->setStatus(Order::STATE_CLOSED);
        $this->_order->save();
    }

    protected function _handleCapturePending()
    {
        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(true);

        $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);

        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder($transaction, $this->_objPmReq->objPmNotify->errorMessage . " - tranzactie in curs de verificare - ");
        $this->_order->setStatus(Order::STATE_PAYMENT_REVIEW);
        $this->_order->save();

    }

    protected function _handleCapture()
    {
        $payment = $this->_order->getPayment();  
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(false);
        if ($this->_order->getStatus() == Order::STATE_PROCESSING) {
            $payment->setParentTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
            $payment->registerCaptureNotification($this->_objPmReq->objPmNotify->processedAmount);
            $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);

        } else {
            $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);
            $payment->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
            $payment->setParentTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId));
            $payment->registerCaptureNotification($this->_objPmReq->objPmNotify->processedAmount);
        }
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->getRealOrderId($this->_objPmReq->objPmNotify->purchaseId))]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
        $payment->addTransactionCommentsToOrder($transaction, $this->_objPmReq->objPmNotify->errorMessage . " - ");
        $this->_order->setStatus(Order::STATE_COMPLETE);
        $this->_order->save();
    }

    private function _processRequest ()
    {
        
        error_reporting(E_ALL);
        $objPmReq = false;
        $request = $this->getRequest();
        if ($request->isPost())
        {
            $envKey = $request->getParam('env_key', false);
            $envData = $request->getParam('data', false);
       

            if ($envKey && $envData)
            {
                $filePath = $this->_moduleDirReader->getModuleDir('etc', 'Netopia_Netcard');
                $path = $filePath . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR;

                if ($this->getConfigData('mode/is_live') == 1) {
                    $livePrivateKey = $this->getConfigData('mode/live_private_key');
                    if(!is_null($livePrivateKey) && file_exists($path.$livePrivateKey)) {
                        $privateKeyFilePath = $path . $livePrivateKey;
                    }else {
                        $privateKeyFilePath = $path . "live.".$this->getConfigData('auth/signature')."private.key";
                    }
                } else {
                    $sandboxPrivateKey = $this->getConfigData('mode/sandbox_private_key');
                    if(!is_null($sandboxPrivateKey) && file_exists($path.$sandboxPrivateKey)){
                        $privateKeyFilePath = $path . $sandboxPrivateKey;
                    }else{
                        $privateKeyFilePath = $path . "sandbox.".$this->getConfigData('auth/signature')."private.key";
                    }
                }

                try
                {
                    $objPmReq = MobilpayPaymentRequestAbstract::factoryFromEncrypted($envKey, $envData, $privateKeyFilePath);
                } catch (\Exception $e)
                {

                    $this->_sendResponse(MobilpayPaymentRequestAbstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode(), $e->getMessage());
                }

            }
        }

        return $objPmReq;
    }

    private function _sendResponse ($errorType, $errorCode, $errorMessage)
    {

        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        if ($errorCode == 0)
        {
            echo "<crc>{$errorMessage}</crc>";
        } else
        {
            echo "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
        }
        exit();
    }

    public function getConfigData($field)
    {
        $str = 'payment/net_card/'.$field;
        return $this->_scopeConfig->getValue($str);
    }

    public function getRealOrderId($ntpTransactionId) {
        $expArr = explode('_T_', $ntpTransactionId);
        return $expArr[0];
    }

}
