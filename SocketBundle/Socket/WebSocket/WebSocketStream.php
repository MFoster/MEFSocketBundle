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
     * the incoming message, a message that isn't completely finished
     * 
     * @var mixed
     * @access protected
     */
    protected $incoming;
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
    
    
    public function __construct($stream, $serializer)
    {
        parent::__construct($stream, $serializer);
        
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
    
    /*v*
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
        
      
        if(Message::isControlFrame($buffer->first())) {
            $this->processNewMessage($buffer);
        }
        else if($buffer->get(1) == 128){//zero length frame
            $this->close();
        }
        else if($this->incoming && !($this->incoming->isComplete())){
            $this->processContinuedMessage($buffer);
        }
        else{
            print_r($buffer);
            throw new \RuntimeException('Failed to determine how to handle data');
        }

        
        
    }
    
    public function pong($msg)
    {
        
        return $this->sendMessage($msg, Message::PONG_FRAME);
        
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
    
        $message = Message::create($buffer);
        
        if($message->isComplete()){
            $this->addMessage($message);
            if($message->hasTumor()){
                $this->processNewMessage($message->getTumor());
            }
        } else {
            $this->incoming = $message;
        }
        
        return true;        
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
    
        $this->incoming->addEncoded($buffer);
        
        if($this->incoming->hasTumor()){
            echo "\n HAS TUMOR, PROCESSING THAT NOW \n";
            $tumor = $this->incoming->getTumor();
            print_r($tumor);
            $this->processNewMessage($tumor);
        }
        

        if($this->incoming->isComplete()){
            echo "\n DONE WITH INCOMGIN MESSAGE \n";
            $this->addMessage($this->incoming);
            $this->incoming = false;
        }
    
        return true;

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
        
        return new Response(null, 101, $this->getHandshakeHeaders());
        
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
    
    /**
     * Uses a serialization function to turn the data into a string.
     * The string is then passed to websocket message instance which packages
     * the data string into a WS Payload and sends it through the socket.
     * 
     * @access public
     * @param mixed $message string or object that represents the data that needs to be passed
     * @param mixed $opCode (default: Message::TEXT_FRAME)
     * @return void
     */
    public function sendMessage($message, $opCode=Message::TEXT_FRAME)
    {
        
        $input = $this->serializer->serialize($message);
        
        $message = Message::create($message);
        
        $message->setOpcode($opCode);
        
        return $this->write($message->serialize());
       
    }

}