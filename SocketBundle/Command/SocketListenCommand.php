<?php

namespace MEF\SocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SocketListenCommand class executes and starts a php socket.
 * 
 * @extends ContainerAwareCommand
 */
class SocketListenCommand extends ContainerAwareCommand
{
    
    /**
     * saved reference to output interface, used for debugging socket connections.
     * 
     * @var mixed
     * @access protected
     */
    protected $output;
    /**
     * used for deriving meta information about the command, help docs etc.
     * 
     * @access protected
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('socket:listen')
            ->setDescription('Starts the socket listening to a specified port')
            ->addOption('port', null, InputOption::VALUE_NONE, 'If set, will override configured port')
            ->addOption('host', null, InputOption::VALUE_NONE, 'If set will override configured host name')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Adds log events to terminal')
            ->addOption('test-mode', null, InputOption::VALUE_NONE, 'Will add events to reply to specific keywords');
        ;
    }
    
    /**
     * execute function, the meat of the class that actually runs the command
     * 
     * @access protected
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        //$container->enterScope('socket');
        $socket = $container->get('mef.socket.server');
        $evt = $container->get('event_dispatcher');
        
        $this->output = $output;
        if($input->getOption('port')){
            $socket->setPort($input->getOption('port'));
        }
        
        if($input->getOption('host')){
            $socket->setHost($input->getOption('host'));
        }
        
        if($input->getOption('debug')){
            $evt->addListener('socket.open', array($this, 'debugSocketOpen'));
            $evt->addListener('socket.message', array($this, 'debugSocketMessage'));
            $evt->addListener('socket.close', array($this, 'debugSocketClose'));
        }
        
        if($input->getOption('test-mode')){
            $evt->addListener('socket.message', array($this, 'testModeMessage'));
        }
        
        $evt->addListener('socket.message', array($this, 'handleSocketMessage'));
        
        set_time_limit(0);
        
        $output->writeln(sprintf('Attempt socket connect host = %s port = %d', $socket->getHost(), $socket->getPort()));
        
        $socket->listen();
        
        $output->writeln(sprintf("Finished listening at %s:%d", $socket->getHost(), $socket->getPort()));
    }
    
    
    public function handleSocketMessage($evt)
    {
        $msg = $evt->getMessage();
        
        if($msg == 'exit' || $msg == "\0"){
            $stream = $evt->getStream();
            $stream->writeln('bye!');
            $stream->close();
        }
    }
    /**
     * debugSocketOpen function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function debugSocketOpen($evt)
    {
        $this->output->writeln('Opened new socket');
    }
    
    /**
     * debugSocketMessage function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function debugSocketMessage($evt)
    {
        $msg = $evt->getMessage();
        $this->output->writeln(sprintf('received new socket message (%s)', $msg));
    }
    
    /**
     * debugSocketClose function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function debugSocketClose($evt)
    {
        $this->output->writeln('socket closed');
    }
    
    
    /**
     * testModeMessage function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function testModeMessage($evt)
    {
        $msg = $evt->getMessage();
        $stream = $evt->getStream();
        if($msg == 'hello'){
            $stream->writeln('Why hello yourself');
        }
        if($msg == 'thanks'){
            $stream->writeln('more than welcome');
        }

    }
    /**
     * Convienence function for retrieving a service from the DI container.
     * 
     * @access protected
     * @param string $key
     * @return mixed
     */
    protected function get($key)
    {
        
        $container = $this->getContainer();
        
        return $container->get($key);
        
    }
}