<?
/**
 * This class can be used for interpreting Mobilpay.Ro payment confirmation
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 * 
 * This class uses  OpenSSL
 * In order to use the OpenSSL functions you need to install the OpenSSL package.
 * See PHP documentation for installing OpenSSL package
 */
class Mobilpay_Payment_Confirmation
{
	/**
	 * Encrypted data as you received it from Mobilpay.Ro along with payment confirmation
	 *
	 * @var string
	 */
	protected $m_data		= null;

	/**
	 * Envelope key as you received it from Mobilpay.Ro along with payment confirmation
	 *
	 * @var string
	 */
	protected $m_env_key	= null;

	/**
	 * List of parameters obtained from data decryption
	 *
	 * @var array
	 */
	public $m_params		= array();

	/**
	 * MobilpayPaymentConfirmation
	 * You should pass data and env_key POST parameters the you receive along with payment confirmation
	 * Do not alter before passing them to MobilpayPaymentConfirmation
	 *
	 * @param string $src_data
	 * @param string $src_env_key
	 * @return MobilpayPaymentConfirmation
	 */
	public function __construct($src_data, $src_env_key)
	{
		$this->m_data		= $src_data;
		$this->m_env_key	= $src_env_key;
	}

	/**
	 * The private key that you received from Mobilpay.Ro when your merchant account was created
	 *
	 * @param resource $private_key
	 */
	function processData($private_key)
	{
		if(is_null($this->m_data) || is_null($this->m_env_key))
			return false;

		$src_data		= base64_decode($this->m_data);
		if($src_data === false)
			return false;
		$src_env_key	= base64_decode($this->m_env_key);
		if($src_env_key === false)
			return false;

		$query_string = '';
		$result = openssl_open($src_data, $query_string, $src_env_key, $private_key);
		if($result === false)
			return false;

		$params = array();	
		$pairs = explode('&', $query_string);
		foreach ($pairs as $item)
		{
			list($key, $value) = explode('=', $item);
			$params[$key] = $value;
		}
		$crc = Mobilpay_Global::buildCRC($params);
		if($crc != $params['crc'])
		{
			return false;
		}
		foreach ($params as $key=>$value)	
			$this->m_params[$key] = urldecode($value);

		return true;
	}
}
