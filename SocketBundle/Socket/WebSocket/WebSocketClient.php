<?php

namespace MEF\SocketBundle\Socket\WebSocket;

use MEF\SocketBundle\Socket\SocketClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * WebSocketClient class.
 * Acts as a client to send websocket messages to a websocket server.
 * 
 * @extends SocketClient
 */
class WebSocketClient extends SocketClient
{

    /**
     * httpHeaderDelimeter
     * 
     * (default value: "/[\r\n]{4}$/")
     * 
     * @var string
     * @access protected
     */
    protected $httpHeaderDelimeter = "/[\r\n]{4,}$/";
    
    /**
     * uri
     * 
     * (default value: '/')
     * 
     * @var string
     * @access protected
     */
    protected $uri = '/';
    
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
     * keySalt
     * 
     * (default value: '11C5ABA001AFE5352679AA002211')
     * 
     * @var string
     * @access protected
     */
    protected $keySalt = '11C5ABA001AFE5352679AA002211';
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
     * websocket protocol version
     * 
     * (default value: 13)
     * 
     * @var int
     * @access protected
     */
    protected $version = 13;
    
    /**
     * chunkLength
     * 
     * (default value: 1024)
     * 
     * @var int
     * @access protected
     */
    protected $chunkLength = 1024;

    
    /**
     * sendMessage function.
     * 
     * @access public
     * @param mixed $message
     * @return void
     */
    public function sendMessage($message, $opCode=Message::TEXT_FRAME)
    {
        if(!$this->handshake){
            $this->shakeHands();
        }
        
        $wsMessage = Message::create($message)->setMasked(true)->setOpcode($opCode);
         
        return $this->write($wsMessage->serialize());
        
    }
    
    /**
     * ping function.
     * 
     * @access public
     * @param string $msg (default: "ping")
     * @return void
     */
    public function ping($msg = "ping")
    {
                
        return $this->sendMessage($msg, Message::PING_FRAME);
        
    }
    
    /**
     * getUri function.
     * 
     * @access public
     * @return void
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * shakeHands function.
     * 
     * @access public
     * @return void
     */
    public function shakeHands()
    {
        $request = $this->generateHandshakeRequest();
        $str = "$request";
        
        $this->write($str);
        
        $response = $this->readHandshake();
        
        $response = $this->createResponseFromRaw($response);
        
        $key = $response->headers->get('sec-websocket-accept');
        
        $hash = $this->getHandshakeValidation();
        
        if($key == $hash){
            $this->handshake = true;
            return true;
        }
        else{
            return false;
        }
        
    }
    
    /**
     * getHandshakeValidation function.
     * 
     * @access protected
     * @return void
     */
    protected function getHandshakeValidation()
    {
        return base64_encode(pack('H*', sha1($this->handshakeKey . $this->handShakeSalt)));
    }
    
    /**
     * createResponseFromRaw function.
     * 
     * @access protected
     * @param mixed $responseStr
     * @return void
     */
    protected function createResponseFromRaw($responseStr)
    {
    
        $parts = explode("\r\n", $responseStr);
        $status = array_shift($parts);
        $statusStruct = $this->parseHttpStatus($status);
        $headers = array();
        foreach($parts as $header){
            $headerPart = explode(':', $header);
            $headers[strtolower($headerPart[0])] = trim($headerPart[1]);
        }
        
        $response = new Response(null, $statusStruct['code'], $headers);
        
        return $response;
        
    }
    
    /**
     * parseHttpStatus function.
     * 
     * @access protected
     * @param mixed $str
     * @return void
     */
    protected function parseHttpStatus($str)
    {
        preg_match('/\s([0-9]+)\s(.*)$/i', $str, $matches);
        return array(
            'code' => $matches[1],
            'text'  => $matches[2]
        );
    }
    
    /**
     * generateHandshakeRequest function.
     * 
     * @access protected
     * @return void
     */
    protected function generateHandshakeRequest()
    {
    
        $request = Request::create($this->getUri(), 'GET');
        $request->headers->replace($this->generateHandshakeHeaders());
        return $request;
    }
    
    /**
     * generateHandshakeHeaders function.
     * 
     * @access protected
     * @return void
     */
    protected function generateHandshakeHeaders()
    {
        return array(
            'host' => $this->getHost(),
            'upgrade' => 'websocket',
            'connection' => 'upgrade',
            'sec-websocket-key' => $this->generateKey(),
            'sec-websocket-protocol' => 'websocket',
            'sec-websocket-version' => $this->version,
            'origin' => $this->getHost()
        );
    }
    
    /**
     * generateKey function.
     * 
     * @access protected
     * @return void
     */
    protected function generateKey()
    {
        return $this->handshakeKey = base64_encode(md5(rand(0, 1000) * rand(0, 255) . $this->keySalt));
    }
        
    /**
     * generateMask function.
     * 
     * @access protected
     * @return void
     */
    protected function generateMask()
    {
        return array(rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255));        
    }
    
    /**
     * reads the websocket message.
     *
     * 
     * @access public
     * @return Message
     */
    public function read()
    {
        $socket = $this->getSocket();
        $reader = ByteReader::create($socket);
        $message = Message::create();
        
        $buff = $reader->read(1);
        
        $message->setOpcode($buff->first());
        
        $length = $reader->read(1)->first();//client reading from server, should never be masked.
        
        $buff->push($length);

        if($length < 126){
            //validation, nothing to do.
        } elseif ($length == 126) {
            $length = $reader->read(2)->sum();
        } elseif ($length == 127) {
            $length = $reader->read(8)->sum();
        } else {
            
            throw new \RuntimeException('Failed to determine length in websocket client read method');
        }
        
        $message->setLength($length);
        
        $message->setPayload($reader->read($length));
                
        return $message;
              
    }
    
    protected function _read($num)
    {
        $buffer = ByteBuffer::create();
        $count = 0;
        
        while($count < $num){
            $byte = fgetc($socket);
            $buffer->push($byte);
        }
        
        return $buffer;
    }
    
    /**
     * Handles the HTTP handshake read operation which is drastically different than 
     * the rest of the WS protocol style messages.
     * 
     * @access public
     * @return string  The handshake HTTP response from the server
     */
    public function readHandshake()
    {
        $socket = $this->getSocket();
        $message = '';
               
        while(true){
            $byte = fgetc($this->socket);
            $message .= $byte;
            if(preg_match($this->httpHeaderDelimeter, $message)){
                break;
            }
            else if($byte === FALSE){
                break;
            }
        }
        
        return $this->cleanMessage($message);

    }



}