<?php

namespace MEF\SocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * WebSocketListenCommand class executes and starts a php socket.
 * 
 * @extends ContainerAwareCommand
 */
class WebSocketListenCommand extends ContainerAwareCommand
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
            ->setName('socket:web:listen')
            ->setDescription('Starts listening for websocket traffic on a specified port')
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
        $socket = $container->get('mef.websocket.server');
        $evt = $container->get('event_dispatcher');
        
        $this->output = $output;
        if($input->getOption('port')){
            $socket->setPort($input->getOption('port'));
        }
        
        if($input->getOption('host')){
            $socket->setHost($input->getOption('host'));
        }
                
        if($input->getOption('debug')){
            $evt->addListener('websocket.handshake', array($this, 'debugSocketHandshake'));
            $evt->addListener('websocket.data', array($this, 'debugSocketData'));
            $evt->addListener('websocket.message', array($this, 'debugSocketMessage'));
        }
        
        if($input->getOption('test-mode')){
            $evt->addListener('websocket.message', array($this, 'testModeMessage'));
        }
        
        $evt->addListener('websocket.open', array($this, 'handleSocketOpen'));
        $evt->addListener('websocket.message', array($this, 'handleSocketMessage'));
        $evt->addListener('websocket.ping', array($this, 'handleSocketPing'));
        $evt->addListener('websocket.close', array($this, 'handleSocketClose'));

        set_time_limit(0);
        
        $output->writeln(sprintf('Attempt socket connect host = %s port = %d', $socket->getHost(), $socket->getPort()));
        
        $socket->listen();
        
        $output->writeln(sprintf("Finished listening at %s:%d", $socket->getHost(), $socket->getPort()));
    }
        
    /**
     * debugSocketHandshake function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function debugSocketHandshake($evt)
    {
        $this->output->writeln('shaking hands '. $evt->getMessage());
    }
    
    /**
     * debugSocketData function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function debugSocketData($evt)
    {
        $this->output->writeln('received websocket data '. substr($evt->getMessage(), 0, 100));
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
        $this->output->writeln(sprintf('receiving websocket message (%s)', substr($evt->getMessage(), 0, 100)));
    }
    
    /**
     * handleSocketOpen function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function handleSocketOpen($evt)
    {
        $this->output->writeln('opened websocket');
    }
    
    /**
     * handleSocketClose function.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function handleSocketClose($evt)
    {
        $this->output->writeln('closing websocket');
    }
    
    
    /**
     * This function only gets executed when running in --test-mode.
     * Responds to a few canned messages to assist in testing the client's ability
     * to read messages from the server.
     * 
     * @access public
     * @param mixed $evt
     * @return void
     */
    public function testModeMessage($evt)
    {
        $stream = $evt->getStream();
        $message = $evt->getMessage();
        if($stream->isClosed() || strlen("$message") == 0 || "$message" == "\0" ){
            $this->output->writeln("Stream already closed or sent empty message, sending nothing");
            return;
        }
        
        
        $this->output->writeln('About to response to message (' . $message . ')');
        if($message == 'hello'){
            $result = $stream->sendMessage('Why hello yourself');
            if($result == false){
                $this->output->writeln('failed to respond to hello');
            }
            else{
                $this->output->writeln('successfully wrote to socket, responded to hello');
            }
        }
        else if ($message == 'hello medium'){
            $msg = $this->generateMediumMessage();
            $result = $stream->sendMessage($msg);
            if($result === false){
                $this->output->writeln('failed to respond to medium message');
            }
            else{
                $this->output->writeln('successfully responded to medium message, length = ' . strlen($msg));
            }
        }
        else if ($message = 'hello big'){
            $message = $this->generateBigMessage();
            $result = $stream->sendMessage($message);
            if($result === false){
                $this->output->writeln('failed to respond to big message');
            }
            else{
                $this->output->writeln('successfully responded to big message message length = '. strlen($message));
            }
        }
    }
    
    protected function generateMediumMessage()
    {
        
        return $this->generateMessage(3500);
        
    }
    
    protected function generateBigMessage()
    {
        
        
        return $this->generateMessage(80000);
        
        
    }
    
    protected function generateMessage($num)
    {
    
        $str = 'A';
        for($i = 0; $i < $num; $i++){
            $str .= 'A';
        }
        
        return $str . 'F';
        
    }
    
    public function handleSocketPing($evt)
    {
        $stream = $evt->getStream();
        
        $stream->pong($evt->getMessage());
        
        $this->output->writeln('receive ping message, sent pong');
        
    }
    
    public function handleSocketMessage($evt)
    {
    
        $this->output->writeln('handling websocket message');
        
    }
    

}