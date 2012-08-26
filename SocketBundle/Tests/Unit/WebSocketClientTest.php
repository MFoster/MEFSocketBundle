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
     * @access public
     * @return void
     * @group websocket
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
     * @return void
     * @group websocket
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
     * @return void
     * @group websocket
     */
    public function testMediumReply()
    {
        $client = $this->get('mef.websocket.client');
        
        $client->sendMessage('hello medium');
        
        $response = $client->read();
        
        $str = "$response";
        
        $this->assertEquals(strlen($str), 3502);
        
        $this->assertRegExp('/^A+F$/i', $str);
        
        
        
    }

    /**
     * testBigReply function.
     * 
     * @access public
     * @return void
     * @group websocket
     */
    public function testBigReply()
    {
        $client = $this->get('mef.websocket.client');
        
        $client->sendMessage('hello big');
        
        $response = $client->read();
        
        $str = "$response";
        
        $this->assertRegExp('/^A+F$/', $str);
        
        $this->assertEquals(strlen($str), 80002);
    }
        
    /**
     * testMediumMessage function.
     * 
     * @access public
     * @return void
     * @group websocket
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
     * @group websocket
     */
    public function testLongMessage()
    {
        
        $client = $this->get('mef.websocket.client');
        
        $msg = $this->buildBigMessage(80000);
        
        $result = $client->sendMessage($msg);
        
        $this->assertEquals(80014, $result);
        
        
    }
  
    public function testClose()
    {
        $client = $this->get('mef.websocket.client');
        
        $result = $client->close();
        
        $this->assertEquals(0, $result);
        
        echo "RESULT OF SOCKET CLOSE $result";
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