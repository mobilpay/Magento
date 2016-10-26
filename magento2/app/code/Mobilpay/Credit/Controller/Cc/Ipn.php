<?php
namespace Mobilpay\Credit\Controller\Cc;
use Magento\Framework\App\Action\Context;
use Mobilpay_Payment_Request_Abstract;
use Mobilpay_Payment_Request_Info;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;

class Ipn extends \Magento\Framework\App\Action\Action
{
    protected $resultPateFactory;
    protected $_orderFactory;
    protected $_moduleDirReader;
    protected $_scopeConfig;
    protected $_order;
    protected $_objPmReq;
    protected $_builderInterface;
    public function __construct(Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Framework\Module\Dir\Reader $reader,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface,
                                \Magento\Sales\Model\Order $orderFactory
    )
    {
        $this->_orderFactory = $orderFactory;
        $this->resultPateFactory = $resultPageFactory;
        $this->_moduleDirReader = $reader;
        $this->_scopeConfig = $scopeConfig;
        $this->_builderInterface = $builderInterface;

        parent::__construct($context);
    }
    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $objPmReq = $this->_processRequest();
        if(!$objPmReq)
            die('Please post your data');
        try
        {
            if ($objPmReq instanceof Mobilpay_Payment_Request_Info)
            {
                $this->_processRequestProduct($objPmReq);
            } else
            {
                $this->_processNotification($objPmReq);
            }
        } catch (\Exception $e)
        {

            $this->_sendResponse(Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode()+100, $e->getMessage());
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

        $this->_order = $this->_orderFactory->loadByIncrementId((int)$order_id);

        $this->_newOrderStatus = 'pending';

    }

    protected function _processNotification($objPmReq)
    {

        $errorCode = 0;
        $errorType = Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
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
                    $this->_handleAuthorization();
                }
                break;

            case 'paid':
                if ($objPmReq->objPmNotify->errorCode != 0) {
                    $this->_handlePaymentDenial();
                } else {

                    $this->_handleAuthorization(0);
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
                $errorType = Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                $errorCode = Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
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
                'Error in creating an invoice', $notified = true);
        }

        $this->_order->getPayment()->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . $ap);
        $this->_order->getPayment()->place();
        $this->_order->save();
    }

    protected function _handleCancel()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId );
        $payment->registerVoidNotification();
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->_objPmReq->objPmNotify->purchaseId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $this->_objPmReq->objPmNotify->errorMessage
        );
        $this->_order->setStatus(Order::STATE_CANCELED);
        $this->_order->save();
    }

    protected function _handleAuthorization($underVerification = true)
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId );
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->_objPmReq->objPmNotify->purchaseId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $this->_objPmReq->objPmNotify->errorMessage
        );
        $payment->setIsTransactionClosed(0);

        if (!$underVerification) {
            $payment->setIsTransactionPending(false);
            $this->_createInvoice(":p");
            $payment->registerAuthorizationNotification($this->_objPmReq->objPmNotify->processedAmount);
            $this->_order->setStatus(Order::STATE_PROCESSING);
        } else {
            $payment->setIsTransactionPending(true);
            $this->_order->setStatus(Order::STATE_PAYMENT_REVIEW);
            $this->_order->sendNewOrderEmail();
        }
        $this->_order->save();
    }

    protected function _handlePaymentDenial()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setLastTransId($this->_objPmReq->objPmNotify->purchaseId);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId);
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
        );
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->_objPmReq->objPmNotify->purchaseId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder(
            $transaction,
            $this->_objPmReq->objPmNotify->errorMessage
        );
        //$payment->setNotificationResult(true);
        $payment->setIsTransactionClosed(true);
        $payment->save();
        //$payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
        //$this->_order->setStatus(Order::STATE_CANCELED);
        $this->_order->addStatusToHistory(Order::STATE_CANCELED, $this->_objPmReq->objPmNotify->errorMessage);
        $this->_order->save();
    }

    protected function _handleRefund()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId );
        $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId );
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->_objPmReq->objPmNotify->purchaseId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);

        $payment->addTransactionCommentsToOrder(
            $transaction,
            $this->_objPmReq->objPmNotify->errorMessage
        );
        $payment->setIsTransactionClosed(true);

        $this->_order->getBaseTotalRefunded(-1 * $this->_objPmReq->objPmNotify->processedAmount);
        $payment->registerRefundNotification(-1 * $this->_objPmReq->objPmNotify->processedAmount);
        $this->_order->setTotalRefunded($this->_order->getTotalRefunded() - $this->_objPmReq->objPmNotify->processedAmount);

        //$payment->setAmount(- 1 * $this->_objPmReq->objPmNotify->processedAmount);
        /*
         //nu prea functioneaza bine prb legate de magento invoices
        $creditMemo = Mage::getModel('sales/order_creditmemo');
        $creditMemo->setOrder($this->_order);

        $creditMemo->setBaseGrandTotal(- 1 * $this->_objPmReq->objPmNotify->processedAmount);
        $creditMemo->register();
        $creditMemo->save();
*/

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
            ->setTransactionId($this->_objPmReq->objPmNotify->purchaseId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $this->_objPmReq->objPmNotify->errorMessage
        );
        $this->_createInvoice(":c");
        $this->_order->save();

    }

    protected function _handleCapture()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId );
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(false);
        if ($this->_order->getStatus() == Order::STATE_PROCESSING) {
            $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId  );
            $payment->registerCaptureNotification($this->_objPmReq->objPmNotify->processedAmount);
            $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);

        } else {

            $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);
            $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId);
            $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId);
            $payment->registerCaptureNotification($this->_objPmReq->objPmNotify->processedAmount);
            $this->_order->sendNewOrderEmail(":f");

        }
        $trans = $this->_builderInterface;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($this->_objPmReq->objPmNotify->purchaseId)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array('id'=>$this->_objPmReq->objPmNotify->purchaseId)]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $this->_objPmReq->objPmNotify->errorMessage
        );
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
                $filePath = $this->_moduleDirReader->getModuleDir('etc', 'Mobilpay_Credit');
                $path = $filePath . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR;

                if ($this->getConfigData('debug') == 1) {
                    $privateKeyFilePath = $path . "sandbox.".$this->getConfigData('signature')."private.key";
                }
                else {
                    $privateKeyFilePath = $path . "live.".$this->getConfigData('signature')."private.key";
                }

                try
                {
                    $objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($envKey, $envData, $privateKeyFilePath);
                } catch (\Exception $e)
                {

                    $this->_sendResponse(Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode(), $e->getMessage());
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

        $path = 'payment/cardcc/' . $field;
        return $this->_scopeConfig->getValue($path);
    }
}
