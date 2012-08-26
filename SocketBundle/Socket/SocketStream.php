<?php

namespace MEF\SocketBundle\Socket;

use Symfony\Component\EventDispatcher\EventDispatcher;

class SocketStream extends EventDispatcher
{
    
    /**
     * is_closed
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
     * @return void
     */
    public function __construct($stream)
    {    
        $this->stream = $stream;   
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
     */
    public function write($message)
    {   
        if($this->isClosed()){
            return false;
        }
        
        try{
            return socket_write($this->stream, $message);    
        }
        catch(\ErrorException $e) {
            //@todo log exception
            $this->close();
            return 0;
        }
          
    }
    
    /**
     * writeln function.
     * 
     * @access public
     * @param mixed $message
     * @return void
     */
    public function writeln($message)
    {
        $message .= "\n";
        return $this->write($message);
    }
    
    /**
     * close function.
     * 
     * @access public
     * @return void
     */
    public function close()
    {
        $this->is_closed = true;
       // socket_close($this->stream);
        
    }

    
    /**
     * isClosed function.
     * 
     * @access public
     * @return void
     */
    public function isClosed()
    {
        return $this->is_closed;
    }

}