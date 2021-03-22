<?php
/**
 * Class Mobilpay_Payment_Request_Ntp_Sms
 * This class can be used for accessing mobilpay.ro payment interface for your configured online services
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 * 
 */

// require_once(__DIR__.'/../Sms.php'); // SMS is not supported for magento plugin any more

class Mobilpay_Payment_Request_Ntp_Sms extends Mobilpay_Payment_Request_Sms
{
	function __construct()
	{
		parent::__construct();
	}
}
