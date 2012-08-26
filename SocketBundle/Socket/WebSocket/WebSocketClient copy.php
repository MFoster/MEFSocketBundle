<?php

namespace MEF\SocketBundle\Socket\WebSocket;

use MEF\SocketBundle\Socket\SocketClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * WebSocketBase class.
 * 
 * @extends SocketBase
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
    protected $httpHeaderDelimeter = "/[\r\n]{4}$/";
    
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
     * doubleByteLength
     * 
     * (default value: 65280)
     * 
     * @var int
     * @access protected
     */
    protected $doubleByteLength = 65280;
    
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
     * openByte
     * 
     * (default value: 129)
     * 
     * @var int
     * @access protected
     */
    protected $openByte = 129;
    /**
     * sendMessage function.
     * 
     * @access public
     * @param mixed $message
     * @return void
     */
    public function sendMessage($message)
    {
        if(!$this->handshake){
            $this->shakeHands();
        }
        
        $mask = $this->generateMask();
        //$mask = array(50, 51, 52, 53);
        $buffer = ByteBuffer::create($message);
        $maskedBuffer = $buffer->mask($mask);
        
        $count = strlen($message);
        
        
        //@todo refactor this to use ByteBuffer::parseNumberToCountBuffer
        if($count < 128){
            $count = chr(128 + $count);
        }
        else if($count > 128 && $count < $this->doubleByteLength){
            $count = ByteBuffer::parseNumberToBuffer($count);
            //print_r($count);
            //die("We're done");
            $count->unshift(254);
            //get your math on
        }
        else{
            $count = ByteBuffer::parseNumberToBuffer($count);
            $count->unshift(255);
        }
        //echo "And the count is ($count)";
        //print_r($count);
        
        $compiledMessage = chr($this->openByte)
                   . $count
                   . ByteBuffer::parseArrayToString($mask)
                   . $maskedBuffer;
                   
        
        //echo $compiledMessage;
        
        //echo "Converted message $compiledMessage";
        
        //$buff = ByteBuffer::create($compiledMessage);
        
        //print_r($buff->slice(0, 10));
        
        return $this->write($compiledMessage);
    }
    
    public function getUri()
    {
        return $this->uri;
    }
    
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
    
    protected function getHandshakeValidation()
    {
        return base64_encode(pack('H*', sha1($this->handshakeKey . $this->handShakeSalt)));
    }
    //we can just assume it's headers all the way down.
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
        
        $response = new Response("\r\n\r\n", $statusStruct['code'], $headers);
        
        return $response;
        
    }
    protected function parseHttpStatus($str)
    {
        preg_match('/\s([0-9]+)\s(.*)$/i', $str, $matches);
        return array(
            'code' => $matches[1],
            'text'  => $matches[2]
        );
    }
    
    protected function generateHandshakeRequest()
    {
    
        $request = Request::create($this->getUri(), 'GET');
        $request->headers->replace($this->generateHandshakeHeaders());
        return $request;
    }
    
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
    
    protected function generateKey()
    {
        return $this->handshakeKey = base64_encode(md5(rand(0, 1000) * rand(0, 255) . $this->keySalt));
    }
        
    protected function generateMask()
    {
        return array(rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255));        
    }
    
    /**
     * reads the first segment of information
     *
     * 
     * @access public
     * @return void
     */
    public function read()
    {
        $socket = $this->getSocket();
        $buffer = ByteBuffer::create();
        
        $count = 0;
        $length = -1;
        $open = false;
        
        while(true){
            //$byte = fread($socket, 10);f
            $byte = ByteBuffer::parseByteToInt(fgetc($socket));
            $buffer->push($byte);
            
            //$message .= $byte;
            //echo " RECEIVED NEW BYTE " . $message;
            if($byte == 129 && $count == 0){
                $open = true;
            }
            else if($open == true && $count == 1){
                $length = $byte + 1;
            }
            else if ($count >= $length){
                break;
            }
            
            $count++;
        }
        echo "DETERMINED LENGTH = ". $length;
        
        print_r($buffer);
        $message = $buffer->slice(2);
        return $this->cleanMessage($message);
        
    }
    
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