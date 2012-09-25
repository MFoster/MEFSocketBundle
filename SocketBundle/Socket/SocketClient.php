<?php

namespace MEF\SocketBundle\Socket;

/**
 * SocketClient class.
 * Client class to send tcp data to a tcp server.
 * 
 * @extends SocketBase
 */
class SocketClient extends SocketBase
{
    
    
    /**
     * s
     * 
     * @var mixed
     * @access protected
     */
    protected $socket;
    /**
     * errStr
     * 
     * @var mixed
     * @access public
     */
    public $errStr;
    
    /**
     * errNum
     * 
     * @var mixed
     * @access public
     */
    public $errNum;
    
    /**
     * protocol
     * 
     * @var mixed
     * @access protected
     */
    protected $protocol = 'tcp';
    
    /**
     * timeout
     * 
     * @var mixed
     * @access protected
     */
    protected $timeout = 5;
    
    /**
     * __construct function.
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        
    }
    
    public function __destruct()
    {
        $this->close();
    }
    
    /**
     * setProtocol function.
     * 
     * @access public
     * @param mixed $protocol
     * @return void
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;    
    }
    
    /**
     * getProtocol function.
     * 
     * @access public
     * @return void
     */
    public function getProtocol()
    {
        return $this->protocol;
    }
    
    /**
     * getSocket function.
     * 
     * @access public
     * @return void
     */
    public function getSocket()
    {
        if(!$this->socket){
            //$socket = fsockopen($this->getHost(), $this->getPort(), $this->errStr, $this->errNum);
            $socket = stream_socket_client($this->getProtocol() . '://'. $this->getHost() .':'. $this->getPort());
            stream_set_timeout($socket, $this->timeout);
            
            if(!$socket){
                throw new \RuntimeException('failed to connect to '. $this->getHost() . ':' . $this->getPort());
            }
            else{
                return $this->socket = $socket;
            }
        }
        else{
            return $this->socket;
        }
        
    }
    
    /**
     * Determines whether the instance has constructed an active socket.
     * If the object hasn't then it's close procedures needs to handle that
     * as closing a null resource can lead to problems
     * 
     * @access public
     * @return bool
     */
    public function hasSocket()
    {
        return !!$this->socket;
    }
    
    /**
     * write function.
     * 
     * @access public
     * @param mixed $message
     * @return void
     */
    public function write($message)
    {
        $socket = $this->getSocket();
        
        return fwrite($socket, $message);
        
    }
    
    /**
     * reads the first segment of information
     .
     * 
     * @access public
     * @return void
     */
    public function read()
    {
        $socket = $this->getSocket();
        $message = '';
        
        while(!feof($this->socket)){
            $message .= fgets($this->socket, $this->chunkLength);
            //message has been set but the newest read has returned nothing, message complete
            if(strlen($message) > 0){
                break;
            }
        }
        
        return $this->cleanMessage($message);
        
    }
    
    public function getError()
    {
        return array('number' => $this->errNum, 'message' => $this->errStr);
    }
    
    /**
     * close function.
     * 
     * @access public
     * @return void
     */
    public function close()
    {
        if(!$this->hasSocket()){
            return 0;//was never open
        }
        
        try{
            $this->write("\0");
        }
        catch(\ErrorException $err){
            //this means that the client disconnected before the write had finished
        }
        $ret = fclose($this->socket);
        $this->socket = NULL;
        return $ret;
    }
}