<?php


namespace MEF\SocketBundle\Socket\WebSocket;

/**
 * Message class.
 */
class Message implements \Serializable
{

    const CONT_FRAME = 0;
    
    const TEXT_FRAME = 1;
    
    const BINARY_FRAME = 2;
    
    const CLOSE_FRAME = 8;
    
    const PING_FRAME = 9;

    const PONG_FRAME = 10;
    
    protected $length = 0;
    
    protected static $openFrames = array(self::CONT_FRAME, 
                                         self::TEXT_FRAME, 
                                         self::BINARY_FRAME, 
                                         self::CLOSE_FRAME, 
                                         self::PONG_FRAME, 
                                         self::PING_FRAME);
                                         
    protected $buffer;
    
    protected $tumor;
    
    protected $masked = false;
    
    protected $payload;
    
    protected $mask;
    
    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $msg
     * @return void
     */
    public function __construct($msg = null)
    {
        
                                
        if(is_string($msg)){
            $this->setMessage($msg);
        } elseif($msg instanceof Message) {
            $this->setMessage("$msg");
        } elseif ($msg instanceof ByteBuffer) {
            $this->setBuffer($msg);
        } else{
            $this->buffer = ByteBuffer::create();
            $this->mask = ByteBuffer::create();
            $this->payload = ByteBuffer::create();
        }
        
        
    }
    
    public static function create($msg = null)
    {
        
        return new Message($msg);
            
    }
    
    public function setMessage($message)
    {
        $this->setOpcode(self::TEXT_FRAME);
        $len = strlen($message);
        $this->setLength($len);
        $this->setPayload(ByteBuffer::create($message));
         
    }
    
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }
    
    public function getPayload()
    {
        return $this->payload;
    }
    
    public function setBuffer($buff)
    {
        
        $this->buffer = $buff;
        $control = $buff->first();
        $fin = $control >= 128;
        $opcode = self::chopTopBit($control);
        $len = $buff->get(1);
        $mask = $len >= 128;
        $length = self::chopTopBit($len);
        $offset = 2;
        if($length < 126 && $length > 0){
            //validation
        }
        else if($length == 126){
            $length = $buff->slice(2, 2)->sum();
            $offset = 4;
        }
        else if($length == 127){
            $length = $buff->slice(2, 8)->sum();
            $offset = 10;
        }
        else{
            throw new \ErrorException('Buffer sent to ' . __CLASS__ . ' had incompatible length of '. $length);
        }
        
                
        $this->setMasked($mask);
        if($mask){
            $mask = $buff->slice($offset, 4);
            $offset += 4;
            $this->setMask($mask);
        }
                
        $payload = $buff->slice($offset, $length);
        $offset += $length;
        
        if($buff->length() > $offset){
            $tumor = $buff->slice($offset);
            $this->setTumor($tumor);
        }

        $this->setLength($length);
        
        if($mask){
            $decoded = $payload->unmask($mask);
            $this->setPayload($decoded);
        }
        else{
            $this->setPayload($payload);
        }
        
        $this->setOpcode($opcode);

    }
    
    public function setTumor($tumor)
    {
        $this->tumor = $tumor;
        
        return $this;
    }
    
    public function hasTumor()
    {
        return $this->tumor && $this->tumor->length() > 0;
    }
    
    public function getTumor()
    {
        return $this->tumor;
    }
    
    public function getBuffer()
    {
        return $this->buffer;
    }
    
    public function setMasked($bool)
    {
        if(false === $bool){
            $this->mask = false;
        } else {
            $this->mask = $this->generateMask();
        }
        
        return $this;
    }
    
    public function isMasked()
    {
        return $this->mask && $this->mask->length() > 0;
    }
    
    public function setMask($mask)
    {
        $this->mask = $mask;
        
        return $this;
    }
    
    public function getMask()
    {
        return $this->mask;
    }
    
    public function add($buffer)
    {
        $len = $buffer->length();
        
        if($len + $this->payload->length() > $this->length){
            $dif = $this->length - $this->payload->length();
            $chunk = $buffer->slice(0, $dif);
            $this->payload->add($chunk);
            $tumor = $buffer->slice($dif);
            $this->setTumor($tumor);
        } else {
            $this->payload->add($buffer);
        }
                
    }
    
    
    public function addEncoded($buffer)
    {
        if(!$this->isMasked()){
            throw new \ErrorException('Message is not masked, cannot add encoded information');
        }
        
        $len = $buffer->length();
        
        if($len + $this->payload->length() > $this->length){
            $dif = $this->length - $this->payload->length();
            $chunk = $buffer->slice(0, $dif);
            $this->payload->add($chunk->unmask($this->mask));//the only deviation between this and add really
            $tumor = $buffer->slice($dif);
            $this->setTumor($tumor);//do not unmask the tumor, its a new message with a new mask
        } else {
            $this->payload->add($buffer->unmask($this->mask));
        }
        
        
    }
    
    /**
     * __toString function.
     * 
     * @access public
     * @return string
     */
    public function __toString()
    {   
        
        return "$this->payload";
        
    }
        
    /**
     * isText function.
     * 
     * @access public
     * @return boolean
     */
    public function isText()
    {
        return $this->opcode == self::TEXT_FRAME;
    }
    
    /**
     * isPing function.
     * 
     * @access public
     * @return boolean
     */
    public function isPing()
    {
        return $this->opcode === self::PING_FRAME;
    }
    
    /**
     * isPong function.
     * 
     * @access public
     * @return boolean
     */
    public function isPong()
    {
        return $this->opcode === self::PONG_FRAME;
    }

    /**
     * isClose function.
     * 
     * @access public
     * @return boolean
     */
    public function isClose()
    {
        return $this->opcode === self::CLOSE_FRAME;
    }
    
    public static function isControlFrame($byte)
    {
        $byte = self::chopTopBit($byte);
        return in_array($byte, self::$openFrames);
    }
    

    public static function chopTopBit($byte)
    {
        return ($byte > 128) ? $byte - 128 : $byte;
    }
        

    public function setOpcode($code)
    {
        $code = self::chopTopBit($code);
        if(!(in_array($code, self::$openFrames))){
            throw new \InvalidArgumentException(sprintf('Opcode sent to ' . __CLASS__ . ' is invalid, %d not found in list of valid op codes', $code));
        }
        $this->opcode = $code;
        return $this;
    }
    
    public function getOpcode()
    {
        return chr($this->opcode + 128);
    }
    
    public function setLength($len)
    {
        $this->length = $len;        
    }
    
    public function getLength()
    {
        return ByteBuffer::parseNumberToCountBuffer($this->length, $this->isMasked());
    }
    
    public function isComplete()
    {
        if($this->payload && $this->payload->length() == $this->length && $this->payload->length() > 0){
            return true;
        } elseif ($this->payload && $this->payload->length() > $this->length) {
            throw new \RuntimeException(sprintf('Message overflow, message payload has exceeded the calculated length by %d payload len = %d and message length = %d', 
                                                $this->payload->length() - $this->length, $this->payload->length(), $this->length));
        }
        else{
            return false;
        }
    }
    
    public function serialize()
    {
        return $this->getOpCode() . $this->getLength() . (($this->isMasked()) 
                                                         ? $this->getMask() . $this->getPayload()->mask($this->getMask())
                                                         : $this->getPayload());
    }
    
    public function unserialize($str)
    {
        $this->setMessage($str);
    }
    
    protected function generateMask()
    {
        return ByteBuffer::create(array(rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255)));
    }
    
}