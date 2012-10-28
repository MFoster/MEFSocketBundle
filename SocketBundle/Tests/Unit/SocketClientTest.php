<?php

namespace MEF\SocketBundle\Tests\Unit;
use  MEF\SocketBundle\Test\ApplicationTestCase;
/**
 * SocketServerTest class.
 * DANGER WILL ROBINSON READ THIS BEFORE ATTEMPTING TO TEST
 * You MUST boot the server in a separate process and then run this test against it
 *
 * app/console socket:listen --debug --test-mode
 *
 */
class SocketServerThanksTest extends ApplicationTestCase
{

       
    /**
     * testServerHello function.
     * 
     * @access public
     * @return void
     * @group socketclient
     * @group socket
     */
    public function testServerHello()
    {
        
        $client = $this->get('mef.socket.client');
        
        $client->writeln('hello');
        
        $message = $client->read();
        
        $this->assertRegExp('/yourself$/', $message);
        
    }
    
    /**
     * testServerHello function.
     * 
     * @access public
     * @return void
     * @group socketclient
     * @group socket
     */
    public function testServerThanks()
    {
        
        $client = $this->get('mef.socket.client');
        
        $client->writeln('thanks');
        
        $message = $client->read();
        
        $this->assertRegExp('/welcome$/', $message);
        
    }

}