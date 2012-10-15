<?php


namespace MEF\SocketBundle\Listener;

use MEF\SocketBundle\Socket\SocketEvent;

class WebSocketBroadcastListener
{

    protected $socketServer;
    
    public function __construct($logger, $socketServer)
    {
        $this->logger = $logger;
        $this->socketServer = $socketServer;
        
    }
    
    public function onMessage(SocketEvent $evt)
    {
    
        $message = $evt->getMessage();
        
        if(!$this->hasAuthorization($evt)){
            $this->logger->warn(sprintf('Received unauthorized attempt to broadcast with message of %s', $message));
            return false;
        }
        
        $this->logger->debug(sprintf('received message of some sort in listener %s', $message));
                
        $result = json_decode("$message");
        
        $this->logger->debug(sprintf('result of json_decode on message %s', print_r($result, true)));
        
        if(is_object($result) && isset($result->broadcast)) {
            $this->logger->debug('received broadcast message broadcasting: '. $result->broadcast);
            $this->socketServer->broadcast($message);
        }
        
        
        
    }
    
    /**
     * This method is meant to be subclassed.  Broadcasting is a powerful operation, you should
     * limit who can perform this action, override the parameter for this service with a subclass
     * that will handle authorization.
     * 
     * @access public
     * @param SocketEvent $evt
     * @return void
     */
    public function hasAuthorization($evt)
    {
        return true;
    }




}