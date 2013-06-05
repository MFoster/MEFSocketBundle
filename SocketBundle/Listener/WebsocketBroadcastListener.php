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
    
    public function getUrl()
    {
        return $this->socketServer->getUrl();
    }
    
    public function onMessage(SocketEvent $evt)
    {
    
        $message = $evt->getMessage();
        
        $broadcast = sprintf('received message [%s] at %s', $message, time());
        
        if($evt->getStream()->serializer === $this->socketServer){
            $this->logger->debug('server instances are the same');
        }
        else{
            $this->logger->debug('servier instances are DIFFERENT');
        }
        
        
        $this->socketServer->broadcast($broadcast);
        
        $this->logger->info(sprintf('broadcast listener has received a message [%s] and is responding to socket', $message));
        
    }


}