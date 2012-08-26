<?php

namespace MEF\SocketBundle\Test;
/**
 * SocketServerTestCase class.
 * 
 * This is all very flakey when run by phpunit.  The timing with processes and ports freeing up just doesn't play nice.
 * It can run once and that's fine but if you try to run two of these test cases in the same suite, back to back, the second
 * time the server goes to launch it will choke because the port is still being held by the previous server instance socket reference.
 *
 * TL;DR; only use one instance of this TestCase per test suite
 */
class SocketServerTestCase extends ApplicationTestCase
{

    /**
     * the pid of the socket listen command
     * 
     * @var mixed
     * @access protected
     */
    protected $server_pid;
    /**
     * setUp function.
     * 
     * @access public
     * @return void
     */
    public function setUp()
    {
        $this->launchSocketServer();
    }   
    
    /**
     * launchSocketServer function.
     * 
     * @access protected
     * @param string $flags (default: '--test-mode --debug')
     * @todo use configurable log file for output storage.
     * @return void
     */
    protected function launchSocketServer($flags = '--test-mode --debug')
    {
        if($this->server_pid)
            return false;//already started
            
            
        $cmd = sprintf('nohup app/console socket:listen %s > socket_test.log & echo $!', $flags);
        $this->server_pid = exec($cmd);
        //i'm sorry, but this is an asynchronous call and no callback but requires some time to setup
        //@todo poll the log file for information.
        sleep(2); 
    }
    

    
    /**
     * tearDown function.
     * 
     * @access public
     * @return void
     */
    public function tearDown()
    {
        if($this->server_pid){
            $cmd = sprintf('kill %d', $this->server_pid);
            shell_exec($cmd); 
        }
    } 

}