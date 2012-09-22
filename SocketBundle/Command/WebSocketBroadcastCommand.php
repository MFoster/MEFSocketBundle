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
class WebSocketBroadcastCommand extends ContainerAwareCommand
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
            ->setName('socket:web:broadcast')
            ->setDescription('Send a message to all clients connected to your websocket server')
            ->addArgument('message', InputArgument::REQUIRED, 'The message you want to broadcast')
            ->addOption('port', null, InputOption::VALUE_NONE, 'If set, will override configured port')
            ->addOption('host', null, InputOption::VALUE_NONE, 'If set will override configured host name')
            ->addOption('type', null, InputOption::VALUE_NONE, 'The type of serialization the message will go through, json.')
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
        $socket = $container->get('mef.websocket.client');
        
        $this->output = $output;
        if($input->getOption('port')){
            $socket->setPort($input->getOption('port'));
        }
        
        if($input->getOption('host')){
            $socket->setHost($input->getOption('host'));
        }
        
        $message = $input->getArgument('message');
        
        $message = json_encode(array('broadcast' => $message));
        
        $result = $socket->sendMessage($message);
        
        if($result){
            $output->writeln('Message was successfully sent');
        }
        else{
            $output->writeln('Error occurred, failed to send message');
        }
                
        
    }
        


}