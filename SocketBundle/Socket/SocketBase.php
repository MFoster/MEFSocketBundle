<?php

namespace MEF\SocketBundle\Socket;

abstract class SocketBase
{
    /**
     * The port number the socket will listen on
     * 
     * @var int
     * @access protected
     */
    protected $port;
    
    /**
     * hostname for the socket to listen on.
     * 
     * @var string
     * @access protected
     */
    protected $host;
   
    /**
     * the native socket resource reference
     * 
     * @var mixed
     * @access protected
     */
    protected $socket;
    
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
     * cleanMessages
     *
     * (default value: true)
     *
     * @var bool
     * @access public
     */
    protected $cleanMessages = true;

     /**
     * setHost function.
     * 
     * @access public
     * @param string $host
     * @return void
     */
    public function setHost($host)
    {
        $this->host = $host;
    }
    
    /**
     * setPort function.
     * 
     * @access public
     * @param int $port
     * @return void
     */
    public function setPort($port)
    {
        $this->port = $port;   
    }

    /**
    * setCleanMessages function.
    *
    * @access public
    * @param bool $cleanMessages
    * @return void
    */
    public function setCleanMessages($cleanMessages)
    {
       $this->cleanMessages = $cleanMessages;
    }

    /**
     * getPort function.
     * 
     * @access public
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }
    
    /**
     * getHost function.
     * 
     * @access public
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * getCleanMessages function.
     *
     * @access public
     * @return bool
     */
    public function getCleanMessages()
    {
        return $this->cleanMessages;
    }

    /**
     * cleanMessage function.
     * 
     * @access protected
     * @param string $message
     * @return string
     */
    protected function cleanMessage($message)
    {
        if ($this->cleanMessages) {
            return preg_replace('/^[\s\r\n]+|[\s\r\n]+$/', '', $message);
        }
        else {
            return $message;
        }
    }

}
