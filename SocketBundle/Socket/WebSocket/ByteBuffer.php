<?php

namespace MEF\SocketBundle\Socket\WebSocket;


class ByteBuffer implements \Iterator, \Serializable
{

    protected $internal = array();
    
    const BYTE_LENGTH = 255;
    
    const DOUBLE_BYTE_LENGTH = 65535;
    
    const QUAD_BYTE_LENGTH = 4294967295;
    
    const PONG_BYTE = 136;
    
    const PING_BYTE = 137;
    
    const TEXT_BYTE = 129;
    
    const OPEN_BYTE = 129;
    
    const CLOSE_BYTE = 136;
    
    const QUAD_BYTE = 255;
    
    const DOUBLE_BYTE = 254;
    
    public function __construct($arg)
    {
        if(is_string($arg)){
            $this->internal = self::parseStringToArray($arg);
        }
        else if($arg instanceof Message){
            $this->internal = self::parseStringToArray($arg->getMessage());
        }
        else if(is_array($arg)) {
            $this->internal = $arg;
        }
        else if($arg instanceof ByteBuffer) {
            $this->internal = $arg->getInternal();
        }
        else{
            throw new \ErrorException(__CLASS__ ." constructor given bad parameter, expected string or array, received ". $arg . " which is of type " . gettype($arg) . " " . get_class($arg));
        }        
    }
    
    public function get($index)
    {
        return (isset($this->internal[$index]) ? $this->internal[$index] : NULL);
    }
    
    public function push($byte)
    {
        if(!is_int($byte)){
            $byte = self::parseByteToInt($byte);
        }        
        $this->internal[] = $byte;
        
    }
    
    public static function isOpenFrame($frame)
    {
        
        $bit = ($frame > 128) ? $frame - 128 : $frame;
        
        if($bit == 1){
            return true;
        }        
        else{
            return false;
        }
        
    }
    
    public static function create($arg=array())
    {
        return new ByteBuffer($arg);
    }
    /**
     * parseStringToBuffer function.
     * 
     * @access protected
     * @param mixed $msg
     * @return void
     */
    public static function parseStringToArray($msg)
    {
        $buffer = array();
        $msgBuffer = unpack('H*', $msg);
        $msgBuffer = $msgBuffer[1];
        $msgBuffer = str_split($msgBuffer, 2);
        
        foreach($msgBuffer as $byte) {
            $deciByte = base_convert($byte, 16 , 10);
            $buffer[] = $deciByte;
        }
        
        return $buffer;
        
    }
    
    public static function parseByteToInt($char)
    {
        $charPack = unpack('H*', $char);
        $char = $charPack[1];
        $ret = (int)base_convert($char, 16, 10);
        return $ret;
    }
    
    /**
     * parseNumberToCountBuffer function.
     * 
     * @access public
     * @static
     * @param mixed $num
     * @return void
     */
    public static function parseNumberToCountBuffer($count, $mask=true)
    {
        if($count < 125) {
            if($mask){
                $count += 128;
            }  
            $count = ByteBuffer::create(array((int)$count));
        }
        else if($count > 125 && $count < self::DOUBLE_BYTE_LENGTH) {
            $count = ByteBuffer::parseNumberToBuffer($count);
            $extraByte = ($mask) ? self::DOUBLE_BYTE : self::DOUBLE_BYTE - 128;
            $count->unshift($extraByte);
        }
        else{
            $count = ByteBuffer::parseNumberToBuffer($count);
            $extraByte = ($mask) ? self::QUAD_BYTE : self::QUAD_BYTE - 128;
            $count->unshift($extraByte);
        }
        
        return $count;

    }
    /**
     * parseBufferToString function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    public static function parseArrayToString($buffer)
    {
        $str = '';
        foreach($buffer as $index => $val){
        
            $val = chr((int)$val);
    
            $str .= $val;
        }
        return $str;
    }
    
    /**
     * parseNumberToBuffer function.
     * 
     * @access public
     * @static
     * @param mixed $num
     * @return void
     */
    public static function parseNumberToBuffer($num)
    {

        if($num < self::BYTE_LENGTH){
            return self::create(array($num));
        }
        else if($num < self::DOUBLE_BYTE_LENGTH){//max value for a 16 bit system.
            $packed = pack('n', $num);
            $packedArr = self::parseStringToArray($packed);
        }
        else if($num < self::QUAD_BYTE_LENGTH){//maximum value in a 32 bit/4byte system.
            $packed = pack('N', $num);
            $packedArr = self::parseStringToArray($packed);
            $packedArr = array_pad($packedArr, -8, 0);
        }
        else if($num > self::QUAD_BYTE_LENGTH){
            throw new \ErrorException('Unable to handle messages over ' . self::QUAD_BYTE_LENGTH);
            $packed = pack('N', $num);
            $packedArr = self::parseStringToArray($packed);
            //write custom pack method, base_convert number to binary, chunk string by 8, evaluate each byte.
            //this might need to throw an exception until i can figure out another solution.
            //websockets need to support bigger messages but i'm not sure how to handle that
            //in php while keeping OS interoperability in touch.
        }
        
        return self::create($packedArr);
        
    }
    
    /**
     * add function.
     * 
     * @access public
     * @param mixed $arr
     * @return void
     */
    public function add($arr)
    {
        if($arr instanceof ByteBuffer){
            $arr = $arr->getInternal();
        }
        $this->internal = array_merge($this->internal, $arr);
        
        return $this;
    }
    
    public function prepend($arr)
    {
        if($arr instanceof ByteBuffer){
            $arr = $arr->getInternal();
        }
        
        $this->internal = array_merge($arr, $this->internal);
        
        return $this;
    }
    
    /**
     * reset function.
     * 
     * @access public
     * @param array $arr (default: array())
     * @return void
     */
    public function reset($arr = array())
    {
        if($arr instanceof Buffer){
            $arr = $arr->getInternal();
        }
        $this->internal = $arr;
        
        return $this;
    }
    
    /**
     * mask function.
     * 
     * @access public
     * @param mixed $mask
     * @return void
     */
    public function mask($mask)
    {
        $decoded = array();
        $toggle = is_array($mask);
        
        if($toggle){
            $maskLen = count($mask);
        }
        else{
            $maskLen = $mask->length();
        }
        
        if($maskLen == 0){
            throw new \Exception('Sent empty mask');
        }
        
        foreach($this->internal as $index => $value){
            if($toggle){
                $result = (int)$mask[$index % $maskLen] ^ (int)$value;
            }
            else{//assume another byte buffer object
                $result = $mask->get($index % $maskLen) ^ (int)$value;
            }
            
            $decoded[] = $result;
        }
        
        return self::create($decoded);
    }
    
    /**
     * unmask function.
     * 
     * @access public
     * @param mixed $mask
     * @return void
     */
    public function unmask($mask)
    {
        return $this->mask($mask);
    }
    
    /**
     * addMasked function.
     * 
     * @access public
     * @param mixed $mask
     * @param mixed $arr
     * @return void
     */
    public function addMasked($mask, $arr)
    {
        $masked = self::create($arr);
        $unmasked = $masked->unmask($mask);
        $this->add($unmasked);
        return $this;
    }
    

    /**
     * sum function.
     * 
     * @access public
     * @return void
     */
    public function sum()
    {
        $multiplier = 0;
        $total = 0;
        $buffer = array_reverse($this->internal); //flip it to LE.
                
        foreach($buffer as $index => $value){
            
            $multiplier = pow(256, $index);
            
            $total += (int)$multiplier * (int)$value;
            
        }
                
        return $total;
        
    }
    
    
    /**
     * serialize function.
     * 
     * @access public
     * @return void
     */
    public function serialize()
    {
        return self::parseArrayToString($this);
    }
    
    /**
     * unserialize function.
     * 
     * @access public
     * @param mixed $str
     * @return void
     */
    public function unserialize($str)
    {
        return  self::create($str);
    }
    
    /**
     * __toString function.
     * 
     * @access public
     * @return void
     */
    public function __toString()
    {
        return $this->serialize();
    }
    
    /**
     * current function.
     * 
     * @access public
     * @return void
     */
    public function current()
    {
        return current($this->internal);
    }
    
    /**
     * key function.
     * 
     * @access public
     * @return void
     */
    public function key()
    {
        return key($this->internal);
    }
    
    /**
     * next function.
     * 
     * @access public
     * @return void
     */
    public function next()
    {
        return next($this->internal);
    }
    
    /**
     * rewind function.
     * 
     * @access public
     * @return void
     */
    public function rewind()
    {
        return reset($this->internal);
    }
    
    /**
     * valid function.
     * 
     * @access public
     * @return void
     */
    public function valid()
    {
        return current($this->internal) !== FALSE;
    }
    
    
    /**
     * getInternal function.
     * 
     * @access public
     * @return void
     */
    public function getInternal()
    {
        return $this->internal;
    }
    
    /**
     * last function.
     * 
     * @access public
     * @return void
     */
    public function last()
    {
        return $this->internal[count($this->internal) - 1];
    }
    
    /**
     * first function.
     * 
     * @access public
     * @return void
     */
    public function first()
    {
        return (isset($this->internal[0]) ? $this->internal[0] : NULL);
    }
    
    /**
     * slice function.
     * 
     * @access public
     * @param mixed $start
     * @param mixed $length (default: NULL)
     * @return void
     */
    public function slice($start, $length=NULL)
    {
        return self::create(array_slice($this->internal, $start, $length));
    }
    
    /**
     * length function.
     * 
     * @access public
     * @return void
     */
    public function length()
    {
        return count($this->internal);
    }
    
    /**
     * pop function.
     * 
     * @access public
     * @return void
     */
    public function pop()
    {
        return array_pop($this->internal);
    }
    
    /**
     * shift function.
     * 
     * @access public
     * @param mixed $byte
     * @return void
     */
    public function shift($byte)
    {
        return array_shift($this->internal, $byte);
    }
    
    /**
     * unshift function.
     * 
     * @access public
     * @param mixed $arg
     * @return void
     */
    public function unshift($arg)
    {
        return array_unshift($this->internal, $arg);
    }


}
