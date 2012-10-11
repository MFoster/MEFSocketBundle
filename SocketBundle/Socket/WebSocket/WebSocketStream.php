<?php

namespace MEF\SocketBundle\Socket\WebSocket;

use MEF\SocketBundle\Socket\SocketStream;
use Symfony\Component\HttpFoundation\Response;


/**
 * WebSocketServer class.
 * 
 * @extends SocketServer
 */
class WebSocketStream extends SocketStream
{

    /**
     * buffer
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $buffer = false;
    
    /**
     * messageStack
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $messageStack = array();
    /**
     * mask
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $mask = array();
    
    /**
     * handShakeSalt
     * 
     * (default value: '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
     * 
     * @var string
     * @access protected
     */
    protected $handShakeSalt = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    /**
     * request
     * 
     * @var mixed
     * @access protected
     */
    protected $request;
    
    /**
     * response
     * 
     * @var mixed
     * @access protected
     */
    protected $response;
    /**
     * handshake
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $handshake = false;
    
    
    /**
     * opCode
     * 
     * (default value: 0)
     * 
     * @var int
     * @access protected
     */
    protected $opCode = 0;
    
    
    /**
     * completeMessage
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    //protected $completeMessage = false;
    
    /**
     * partialMessage
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $partialMessage = false;
    /**
     * openByte
     * 
     * (default value: 129)
     * 
     * @var int
     * @access protected
     */
    protected $openByte = 129;
    
    /**
     * doubleByteLength
     * 
     * (default value: 254)
     * 
     * @var int
     * @access protected
     */
    protected $doubleByteLength  = 254;
    
    /**
     * quadByteLength
     * 
     * (default value: 255)
     * 
     * @var int
     * @access protected
     */
    protected $quadByteLength = 255;
    
    
    public function __construct($stream)
    {
        parent::__construct($stream);
        
        $this->buffer = ByteBuffer::create();
    }
    /**
     * hasHandshake function.
     * 
     * @access public
     * @return void
     */
    public function hasHandshake()
    {
        return $this->handshake;
    }
    
    /**
     * addData function.
     * 
     * @access public
     * @param mixed $msg
     * @return void
     */
    public function addData($msg)
    {
        $buffer = ByteBuffer::create($msg);
        
        $lastByte = $buffer->last();
        //trailing connection close message attached to data, close up shop after this one.
        //but don't send it along to be decoded, becomes a very bad little byte
        if($lastByte == 0 || $lastByte == "\0"){
            $buffer->pop(); 
            $this->close();
        }
        
        if($this->buffer->length() > 0){
            $this->processContinuedMessage($buffer);
        }
        else if($buffer->first() == ByteBuffer::TEXT_BYTE || $buffer->first() == ByteBuffer::PING_BYTE) {
            $this->processNewMessage($buffer);
        }
        else if($buffer->get(1) == 128){//zero length frame
            $this->close();
        }
        
        
    }
    
    public function pong($msg)
    {
        
        return $this->sendMessage($msg, ByteBuffer::PONG_BYTE);
        
    }
    
    /**
     * processPing function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    protected function processPing($buffer)
    {
        
    }
    
    /**
     * processPong function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    protected function processPong($buffer)
    {
        
    }
    
    /**
     * processNewMessage function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    protected function processNewMessage($buffer)
    {
        //@todo had the double and quad byte messages
        $this->opCode = $buffer->first();
        $this->messageLength = $buffer->get(1);
        $offset = 2;
        $lengthBuff = $buffer->slice(0, 10);
        
        //echo "\nLength buff = ". print_r($lengthBuff);
        if($this->messageLength == $this->doubleByteLength) {
            $lenBuff = $buffer->slice(2, 2);
            $this->messageLength = $lenBuff->sum();
            $offset = 4;
        }
        else if($this->messageLength == $this->quadByteLength){
            $lenBuff = $buffer->slice(2, 8);
            $this->messageLength = $lenBuff->sum();
            $offset = 10;
        }
        else{
            $this->messageLength = $this->messageLength - 128;
        }

        
        $mask = $buffer->slice($offset, 4); //array_slice($buffer, $offset, 4);
        $payload = $buffer->slice($offset + 4); //array_slice($buffer, $offset + 4);
        
        $decoded = $payload->unmask($mask);
        
        if($decoded->length() == $this->messageLength){
            $message = Message::create("$decoded");
            $message->setTypeByCode($this->opCode);
            $this->addMessage($message);
        }
        else{
            $this->mask = $mask;
            $this->addBuffer($decoded);
        }
        
    }
    
    /**
     * compileByteBuffer function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     
    protected function compileByteBuffer($buffer)
    {
        $multiplier = 0;
        $total = 0;
        $buffer = array_reverse($buffer); //flip it to LE.
                
        foreach($buffer as $index => $value){
            
            $multiplier = pow(256, $index);
            
            $total += (int)$multiplier * (int)$value;
            
        }
                
        return $total;
        
    }
    */
    /**
     * addBuffer function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    protected function addBuffer($buffer)
    {
        $buff = $this->getBuffer();
        
        return $buff->add($buffer);
    }
    
    /**
     * addEncodedBuffer function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    protected function addEncodedBuffer($buffer)
    {
        $this->buffer->addMasked($this->mask, $buffer);
    
    //    $decoded = $this->decodePayload($this->mask, $buffer);
        
    //    $this->addBuffer($decoded);
    }
    
    
    /**
     * processContinuedMessage function.
     * 
     * @access protected
     * @param mixed $buffer
     * @return void
     */
    protected function processContinuedMessage($buffer)
    {
        
        $this->addEncodedBuffer($buffer);
        
        $count = $this->buffer->length(); //count($this->buffer);
        
        if($count == $this->messageLength){
            $message = Message::create("$this->buffer");
            $message->setTypeByCode($this->opCode);
            $this->addMessage($message);
            $this->buffer = ByteBuffer::create(array());
            $this->mask   = ByteBuffer::create(array());
            $this->opCode = 0;
        }
        else if($count > $this->messageLength){
            $temp = $this->buffer->slice(0, $this->messageLength); 
            //array_slice($this->buffer, 0, $this->messageLength);
            //$this->completeMessage = $this->parseBufferToString($this->buffer);
            $tumor = $this->buffer->slice($this->messageLength);
            $message = Message::create("$temp");
            $message->setTypeByCode($this->opCode);
            
            $this->addMessage($message);
            //array_slice($this->buffer, $this->messageLength);
            $this->buffer = ByteBuffer::create(array());
            $this->mask   = ByteBuffer::create(array());
            $this->processNewMessage($tumor);
        }
        
        
    }
    
    /**
     * addMessage function.
     * 
     * @access protected
     * @param mixed $message
     * @return void
     */
    protected function addMessage(Message $message)
    {
        
        $this->messageStack[] = $message;
        
    }
    
  
    
    /**
     * setRequest function.
     * 
     * @access public
     * @param mixed $request
     * @return void
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
    
    /**
     * createHandshakeResponse function.
     * 
     * @access public
     * @return void
     */
    public function createHandshakeResponse()
    {
        
        return new Response("\r\n", 101, $this->getHandshakeHeaders());
        
    }
    
    /**
     * shakeHands function.
     * 
     * @access public
     * @return void
     */
    public function shakeHands()
    {
        
        $response = $this->createHandshakeResponse();
        
        $this->write($response . '');
        
        $this->handshake = true;
        
        return $response;
        
    }
    
    /**
     * hasCompleteMessage function.
     * 
     * @access public
     * @return void
     */
    public function hasMessage()
    {
        return count($this->messageStack) > 0;//@todo determine complete message
    }
    
    /**
     * getMessage function.
     * 
     * @access public
     * @return void
     */
    public function getMessage()
    {
        return $this->messageStack[0];
    }
    
    /**
     * getMessages function.
     * 
     * @access public
     * @return void
     */
    public function getMessages()
    {
        return $this->messageStack;   
    }
    
    /**
     * clearMessages function.
     * 
     * @access public
     * @return void
     */
    public function clearMessages()
    {
        $this->messageStack = array();
    }
    
    /**
     * getHandshakeHeaders function.
     * 
     * @access public
     * @return void
     */
    public function getHandshakeHeaders()
    {
        
        if(!$this->request){
            throw new \ErrorException('Cannot create handshake Response, ' . __CLASS__ . ' does not have a valid handshake Request');
        }
        
        $headers = array(
            'upgrade' => 'WebSocket',
            'connection' => 'Upgrade',
            'websocket-origin' => $this->request->headers->get('origin'),
            'websocket-location' => 'ws://' . $this->request->headers->get('host') . '/', //@todo retrieve the resource
            'sec-websocket-accept' => $this->getSecurityKey()
        );
        
        return $headers;    
    }
    
    /**
     * getSecurityKey function.
     * 
     * @access protected
     * @return void
     */
    protected function getSecurityKey()
    {
        $key = $this->request->headers->get('sec-websocket-key');
        
        $hash = pack('H*', sha1($key . $this->handShakeSalt));
        
        return base64_encode($hash);        
        
    }
    public function getBuffer()
    {
        if($this->buffer){
            return $this->buffer;
        }
        else{
            return $this->buffer = ByteBuffer::create(array());
        }
    }
    public function sendMessage($message, $opCode=ByteBuffer::TEXT_BYTE)
    {
        $num = (int)strlen($message);
        
        $lenBuff = ByteBuffer::parseNumberToCountBuffer($num, false);
        
        $payload = chr($opCode)
                 . $lenBuff
                 . $message; //ByteBuffer::create($message);
                 
        //$buff = ByteBuffer::create($payload);
        
        return $this->write($payload);                  
        //return $this->write($payload);        
    }

}