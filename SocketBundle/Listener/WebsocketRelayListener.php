<?php

namespace MEF\SocketBundle\Listener;

class WebsocketRelayListener
{

    /**
     * stack
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $stack = array();
    
    
    /**
     * logger
     * 
     * @var mixed
     * @access protected
     */
    protected $logger;
    
    /**
     * Class constructor, receives the server parameter and saves a reference
     * for later operations, the reference is typically only used for broadcasting
     * 
     * @access public
     * @param mixed $server
     * @return void
     */
    public function __construct($logger, $server)
    {
        
        $this->server = $server;
        $this->logger = $logger;
        
    }
    
    public function getUrl()
    {
        return $this->server->getUrl();
    }
    /**
     * Reacts to the message event.  This is the primary event handler and will receive messages
     * and send them back out to all clients connected to the server.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function handleMessage($evt)
    {
        $message = $evt->getMessage();
        
        $stream = $evt->getStream();
        
        $reply = sprintf('message [%s] received at %s', $message, time());
        
        $stream->sendMessage($reply);
        
    }
    
    /**
     * Reacts to the handshake event.  Sends entire stack to newly initiated client.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function handleHandshake($evt)
    {
        $this->logger->debug('relay listener has received a handshake');
    }
    

}