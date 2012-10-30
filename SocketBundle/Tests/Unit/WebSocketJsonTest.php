<?php

namespace MEF\SocketBundle\Tests\Unit;
use MEF\SocketBundle\Test\ApplicationTestCase;
use MEF\SocketBundle\Socket\WebSocket\ByteBuffer;
use MEF\SocketBundle\Socket\WebSocket\JsonMessage;

class WebSocketJsonTest extends ApplicationTestCase
{

       
    /**
     * testServerHello function.
     * 
     * @access public
     * @return void
     * @group websocket
     * @group websocket.json
     */
    public function testHandshake()
    {
        
        $client = $this->get('mef.websocket.client');
        
        $message = JsonMessage::create(array('message' => 'hello'));
        
        
        for($i = 0; $i < 0; $i++){
            $client->sendMessage($message);
        
            $result = JsonMessage::decode($client->read());
            
            $bool = isset($result->message);
            
            $this->assertTrue($bool);
        }
       
        
                
    }

}       