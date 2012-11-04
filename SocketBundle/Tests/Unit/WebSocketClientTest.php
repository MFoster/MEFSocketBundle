<?php

namespace MEF\SocketBundle\Tests\Unit;
use MEF\SocketBundle\Test\ApplicationTestCase;
use MEF\SocketBundle\Socket\WebSocket\ByteBuffer;

class WebSocketClientTest extends ApplicationTestCase
{

       
    /**
     * testServerHello function.
     * 
     * @access public
     * @return void
     * @group websocket
     * @group websocket.handshake
     */
    public function testHandshake()
    {
        
        $client = $this->get('mef.websocket.client');
        
        $result = $client->shakeHands();
        
        $this->assertTrue($result);
        
    }
    
    
    /**
     * testSayHello function.
     * @group websocket
     * @group websocket.hello
     * @access public
     * @return void
     */
    public function testSayHello()
    {
        $client = $this->get('mef.websocket.client');
        
        $result = $client->sendMessage('hello');
        
        $reply = $client->read();
       
        $this->assertRegExp('/hello/', "$reply");
                        
    }  
    /**
     * testPingPong function.
     * 
     * @access public
     * @group websocket
     * @group websocket.ping
     * @return void
     */
    public function testPingPong()
    {
        
        $client = $this->get('mef.websocket.client');
        
        $client->ping();
        
        $reply = $client->read();
        
        $this->assertEquals('ping', "$reply");
        
        
    }
    
    
    /**
     * testMediumReply function.
     * 
     * @access public
     * @group websocket
     * @group websocket.medium
     */
    public function testMediumReply()
    {
        $client = $this->get('mef.websocket.client');
        
        $client->sendMessage('hello medium');
        
        $response = $client->read();
        
        $str = "$response";
        
        $this->assertEquals(strlen($str), 3500);
        
        $this->assertRegExp('/^FA+F$/i', $str);
        
        
        
    }

    /**
     * testBigReply function.
     * 
     * @access public
     * @return void
     * @group websocket
     * @group websocket.big
     */
    public function testBigReply()
    {
        $client = $this->get('mef.websocket.client');
        
        $client->sendMessage('hello big');
        
        $response = $client->read();
        
        $str = "$response";
        
        $this->assertRegExp('/^FA+F$/i', $str);
        
        $this->assertEquals(strlen($str), 80000);
    }
        
    /**
     * testMediumMessage function.
     * 
     * @access public
     * @return void
     * @group websocket
     * @group websocket.medium
     */
    public function testMediumMessage()
    {
        $client = $this->get('mef.websocket.client');
        
        $msg = $this->buildBigMessage(300);
        
        $result = $client->sendMessage($msg);
        
        $this->assertEquals($result, 308);
        
        $msg = $this->buildBigMessage(3500);
        
        $result = $client->sendMessage($msg);
        
        $this->assertEquals($result, 3508);
                
    }  


    /**
     * testLongMessage function.
     * 
     * @access public
     * @return void
     * @group websocket.big
     */
    public function testLongMessage()
    {
        
        $client = $this->get('mef.websocket.client');
        
        $msg = $this->buildBigMessage(80000);
        
        $result = $client->sendMessage($msg);
        
        $this->assertEquals(80014, $result);
        
        $response = $client->read();//more for the point of just hanging this test until it's finished on the other end
        
        $this->assertEquals("$response", 'message received');
        
    }
    
    /**
     * Tests sockets ability to open a new socket and immediately close it..
     * @group websocket
     * @group websocket.close
     * @access public
     * @return void
     */
    public function testClose()
    {
        $client = $this->get('mef.websocket.client');
        
        $result = $client->close();
        
        $this->assertEquals($result, true);
        
    }
    

    protected function buildBigMessage($num)
    {
        $msg = '';
        $str = 'A';
        
        for($i = 0; $i < $num; $i++){
            $msg .= $str;
        }
        
        return $msg;
        
    }


}