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
    public function sendMessage($message, $opCode=ByteBuffer::TEXT_BYTE)
    {
        if(!$this->handshake){
            $this->shakeHands();
        }
        
        $mask = $this->generateMask();
        $buffer = ByteBuffer::create($message);
        $maskedBuffer = $buffer->mask($mask);
        
        $count = strlen($message);
        
        if($count < 126) {
            $count = chr(128 + $count);//mask plus count
        }
        else if($count > 125 && $count < ByteBuffer::DOUBLE_BYTE_LENGTH) {
            $count = ByteBuffer::parseNumberToBuffer($count);
            $count->unshift(ByteBuffer::DOUBLE_BYTE);
        }
        else{
            $count = ByteBuffer::parseNumberToBuffer($count);
            $count->unshift(ByteBuffer::QUAD_BYTE);
        }

        $compiledMessage = chr($opCode)
                   . $count
                   . ByteBuffer::parseArrayToString($mask)
                   . $maskedBuffer;
                           
        return $this->write($compiledMessage);
        
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
                
        return $this->sendMessage($msg, ByteBuffer::PING_BYTE);
        
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
        
        $response = new Response('', $statusStruct['code'], $headers);
        
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
        $buffer = ByteBuffer::create();
        
        $count = 0;
        $length = -1;
        $open = false;
        $doubleByte = false;
        $quadByte = false;
        $offset = 0;
        $firstByte = 0;
        while(true){
        
            $byte = ByteBuffer::parseByteToInt(fgetc($socket));
            $buffer->push($byte);
            
            
            if(   ($byte == ByteBuffer::TEXT_BYTE 
                || $byte == ByteBuffer::PONG_BYTE 
                || $byte == ByteBuffer::CLOSE_BYTE) //end byte check
                && $count == 0 
                && $open == false) {
        
                $openByte = $byte;
                $open = true;
                $count++;
                $length = 1; //keep it chugging
                continue;
            }
            else if($open == true && $count == 1 && $byte <= 125){
                $length = $byte + 1;
                $offset = 2;
            }
            else if($open == true && $count == 1 && $byte == 126){
                $length = $offset = 4;//allow length buffer to fill up
                $doubleByte = true;
            }
            else if($open == true && $count == 1 && $byte == 127){
                $length = $offset = 10;
                $quadByte = true;
            }
            else if(($doubleByte || $quadByte) && $buffer->length() == $length){
                $length = $buffer->slice(2, $offset - 2)->sum() + $offset - 1;
            }            
            
            if ($count >= $length){
                break;
            }
            
            $count++;
            
        
        }

        $message = $buffer->slice($offset);
        $msg = Message::create($message);
        $msg->setTypeByCode($firstByte);
        return $message;
        
        
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