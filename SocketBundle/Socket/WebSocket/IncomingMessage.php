<?php


namespace MEF\SocketBundle\Socket\WebSocket;

/**
 * Message class.
 */
class IncomingMessage extends Message
{
    
    protected $buffer;
    
    
    public function __construct($buff)
    {
        $this->setBuffer($buff);
    }
    
    public function __toString()
    {
        return "$this->buffer";
    }
    
    public function setBuffer($buff)
    {
        $this->buffer = clone $buff;
        return $this;
    }
    
    public function getBuffer()
    {
        return $this->buffer;
    }
    
    public function add($buff)
    {   
        $this->buffer->add($buff);
        return $this;
    }
    
    public function length()
    {
        return $this->buffer->length();
    }
    
    public function setExpectedLength($len)
    {
        $this->expectedLength = $len;
        $this->isComplete();
        return $this;
    }
    
    public function getExpectedLength()
    {
        return $this->expectedLength;
    }
    
    public function getRemainingLength()
    {
        return $this->getExpectedLength() - $this->buffer->length();
    }
    
    public function isComplete()
    {
        $remaining = $this->getRemainingLength();
        
        if($remaining === 0){
            return true;
        }
        else if($remaining > 0){
            return false;
        }
        else{
            throw new \RuntimeException('IncomingMessage is overloaded with data, error has occurred stuffed past max by '. $remaining);
        }
    }


}