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
class SocketMessageCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('socket:message')
            ->setDescription('Sends a message using a specified client')
            ->addArgument('name', InputArgument::REQUIRED, 'The protocol or name of the client service to use to send the message')
            ->addArgument('message', InputArgument::REQUIRED, 'The message you want to send')
            ->addOption('reply', null, InputOption::VALUE_NONE, 'To wait for a response from the socket server');
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
        
        $name = $input->getArgument('name');
        
        if($name == 'tcp'){
            $clientId = 'mef.socket.client';
        }
        else if($name == 'web'){
            $clientId = 'mef.websocket.client';
        }
        else{
            $clientId = sprintf('socket.%s.client', $name);
        }
        
        $client = $this->getContainer()->get($clientId);
        
        $message = $input->getArgument('message');
        
        $client->sendMessage($message);
        
        if($input->getOption('reply')){
            $content = $client->read();
            $output->writeln("$content");
        }
    
    }
}