<?php

namespace MEF\SocketBundle\Socket;

use Symfony\Component\EventDispatcher\Event;

class SocketEvent extends Event
{
    const MESSAGE = 'message';
    const OPEN = 'open';
    const CLOSE = 'close';
    const PING = 'ping';
    const PONG = 'pong';
    /**
     * stream
     * 
     * @var mixed
     * @access protected
     */
    protected $stream;
    
    /**
     * type
     * 
     * @var mixed
     * @access protected
     */
    protected $type;
    
    /**
     * message
     * 
     * @var mixed
     * @access protected
     */
    protected $message;
    
    /**
     * is_valid
     * 
     * (default value: true)
     * 
     * @var bool
     * @access protected
     */
    protected $is_valid = true;
    
    /**
     * is_closed read only boolean, used to mark a closed socket before the server
     * has taken care of closing the stream.
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $is_closed = false;
    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $stream
     * @param mixed $type
     * @return void
     */
    public function __construct($stream, $type)
    {
       $this->stream = $stream;
       $this->type = $type; 
    }
    
    /**
     * setMessage function.
     * 
     * @access public
     * @param mixed $message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
    
    /**
     * getMessage function.
     * 
     * @access public
     * @return void
     */
    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * getStream function.
     * 
     * @access public
     * @return void
     */
    public function getStream()
    {
        return $this->stream;
    }
    
    /**
     * write function.
     * 
     * @access public
     * @param mixed $message
     * @return void
     * @todo throw an event here
     */
    public function write($message)
    {
        if(!$this->is_closed){
            return $this->stream->write($message);
        }
        else{
            return false;
        }
    }
    
    /**
     * close function.
     * 
     * @access public
     * @return void
     * @todo throw an event here so the server knows it's closed too and removes it from the stream array.
     */
    public function close()
    {
        $this->stream->close();
    }
    
    /**
     * isClosed function.
     * 
     * @access public
     * @return void
     */
    public function isClosed()
    {
        return $this->stream->isClosed();
    }
    
    /**
     * setValid function.
     * 
     * @access public
     * @param mixed $bool
     * @return void
     */
    public function setValid($bool)
    {
        $this->is_valid = $bool;
    }
    
    /**
     * getType function.
     * 
     * @access public
     * @return void
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * isValid function.
     * 
     * @access public
     * @return void
     */
    public function isValid()
    {
        return $this->is_valid;
    }

}