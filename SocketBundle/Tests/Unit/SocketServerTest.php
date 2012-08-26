<?php

namespace MEF\SocketBundle\Tests\Unit;
use  MEF\SocketBundle\Test\SocketServerTestCase;
/**
 * SocketServerTest class.
 * This class is still very experimental and probably should be avoided.
 * I wanted to get the server to spin up before the test began so the test 
 * could act with the client and the server would be assured up, however this process
 * got outrageously messy and it was far easier to just run the server in a separate 
 * terminal tab and run the client tests from another.
 */
class SocketServerTest extends SocketServerTestCase
{

       
    /**
     * testServerHello function.
     * 
     * @access public
     * @return void
     * @group server
     * @group socket
     * @group socketserver
     */
    public function testServerHello()
    {
        
        $client = $this->get('mef.socket.client');
        
        $client->write('hello');
        
        $message = $client->read();
        
        $this->assertRegExp('/yourself$/', $message);
        
    }
    

}