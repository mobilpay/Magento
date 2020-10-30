<?php

class Mobilpay_Payment_AppEncode
{
    const CHR_LIST_VISIBLE = "0123456789abcdefghijkmnpqrstuvwxyz";
    const CHR_LIST_10 = "0123456789";
    const CHR_LIST_ALL = "0123456789abcdefghijklmnopqrstuvwxyz";
    
    const NOT_NUMERIC_CODE = 0x40;
    const PREORDER = 0x10;
    const INVOICE = 0x20;
    
    protected $_mId = '1';
    protected $_maxLenght = 100;
    protected $_type = 1;

    public function baseConvert ($numstring, $frombase = CHE_LIST_10, $tobase = CHR_LIST_VISIBLE)
    {

        $from_count = strlen($frombase);
        $to_count = strlen($tobase);
        $length = strlen($numstring);
        $result = '';
        for ($i = 0; $i < $length; $i ++)
        {
            $number[$i] = strpos($frombase, $numstring[$i]);
        }
        
        do
        {
            $divide = 0;
            $newlen = 0;
            for ($i = 0; $i < $length; $i ++)
            {
                $divide = $divide * $from_count + $number[$i];
                if ($divide >= $to_count)
                {
                    $number[$newlen ++] = (int) ($divide / $to_count);
                    $divide = $divide % $to_count;
                } elseif ($newlen > 0)
                {
                    $number[$newlen ++] = 0;
                }
            }
            $length = $newlen;
            $result = $tobase[$divide] . $result;
        } while ($newlen != 0);
        return $result;
    }

    public function getCode1 ($code, $options)
    {

        if (is_array($options))
        {
            if (array_key_exists('sellerCode', $options))
            {
                $this->_mId = $options['sellerCode'];
            }
            if (isset($options['maxLength']))
            {
                $this->_maxLenght = $options['maxLength'];
            }
            if (isset($options['type']))
            {
                $this->_type = $options['type'];
                if ($this->_type < 10)
                {
                    $this->_type = "0{$this->_type}";
                }
            }
        }
        
        $code = substr($code . "", 0, $this->_maxLenght);
        $code = (is_null($this->_mId) ? '' : $this->_mId) . $code . (is_null($this->_mId) ? '0' : strlen($this->_mId)) . $this->_type;
        
        return $this->baseConvert($code, self::CHR_LIST_10, self::CHR_LIST_VISIBLE);
    }

    public function getCode ($code, $options)
    {

        if (is_array($options))
        {
            if (array_key_exists('sellerCode', $options))
            {
                $this->_mId = $options['sellerCode'];
            }
            if (isset($options['maxLength']))
            {
                $this->_maxLenght = $options['maxLength'];
            }
            if (isset($options['type']))
            {
                $this->_type = $options['type'];
            
            }
        }
        
        $code = substr($code . "", 0, $this->_maxLenght);
        if (! is_numeric($code))
        {
            $this->_type |= self::NOT_NUMERIC_CODE;
        }
        if ($this->_type < 10)
        {
            $this->_type = "0{$this->_type}";
        }
        
        //$codeConv = ! is_numeric($code) ? $code : $this->baseConvert($code, self::CHR_LIST_10, self::CHR_LIST_VISIBLE);
        if (is_numeric($code))
        {
            $midConv = "";
            $codeConv = $this->baseConvert("{$this->_mId}{$code}", self::CHR_LIST_10, self::CHR_LIST_VISIBLE);
            $hash = $this->baseConvert(strlen($this->_mId) . $this->_type, self::CHR_LIST_10, self::CHR_LIST_VISIBLE);
        
        } else
        {
            $midConv = $this->baseConvert(is_null($this->_mId) ? '' : $this->_mId, self::CHR_LIST_10, self::CHR_LIST_VISIBLE);
            $hash = $this->baseConvert(strlen($midConv) . $this->_type, self::CHR_LIST_10, self::CHR_LIST_VISIBLE);
            $codeConv = $code;
        
        }
        
        /*         print_r(array(
			$midConv , 
			$codeConv , 
			$hash));*/
        return "{$midConv}{$codeConv}{$hash}";
    }

    public function revertCode ($code)
    {

        $hash = $this->baseConvert(substr($code, - 2), self::CHR_LIST_VISIBLE, self::CHR_LIST_10);
        if (! isset($hash[1]) || ! isset($hash[2]))
        {
            return array(
                'code' => - 1 , 
                'type' => 1 , 
                'sellerCode' => - 1);
        }
        $type = "{$hash[1]}{$hash[2]}";
        
        if (($type & self::NOT_NUMERIC_CODE) == 0)
        {
            $codeR = $this->baseConvert(substr($code, 0, strlen($code) - 2), self::CHR_LIST_VISIBLE, self::CHR_LIST_10);
            $mid = substr($codeR, 0, $hash[0]);
            $codeR = substr($codeR, $hash[0], strlen($codeR) - $hash[0]);
        
        } else
        {
            $codeR = substr($code, $hash[0], strlen($code) - 2 - $hash[0]);
            $mid = $this->baseConvert(substr($code, 0, $hash[0]), self::CHR_LIST_VISIBLE, self::CHR_LIST_10);
        }
        
        return array(
            'code' => $codeR ,  // $this->baseConvert($code, self::CHR_LIST_10, self::CHR_LIST_ALL) , 
            'type' => ("{$hash[1]}{$hash[2]}" | self::NOT_NUMERIC_CODE) ^ self::NOT_NUMERIC_CODE , 
            'sellerCode' => $mid);
    }

    public function revertCode1 ($code)
    {

        $ret = $this->baseConvert($code . '', self::CHR_LIST_VISIBLE, self::CHR_LIST_10);
        
        $mIdLen = substr($ret, - 3, 1);
        $mId = substr($ret, 0, $mIdLen);
        $code = substr($ret, $mIdLen, strlen($ret) - $mIdLen - 3);
        $type = substr($ret, - 2);
        
        return array(
            
            'code' => $code , 
            'type' => $type , 
            'sellerCode' => $mId);
    }
}