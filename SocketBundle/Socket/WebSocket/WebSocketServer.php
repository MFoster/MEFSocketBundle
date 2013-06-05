<?php

namespace MEF\SocketBundle\Socket\WebSocket;

use MEF\SocketBundle\Socket\SocketServer;
use MEF\SocketBundle\Socket\SocketEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * WebSocketServer class.
 * 
 * @extends SocketServer
 */
class WebSocketServer extends SocketServer
{
    protected $eventPrefix = 'websocket';
    
    
    public function getUrl()
    {
        return 'ws://' . parent::getUrl();
    }

    /**
     * factory function to return SocketStream instance
     * 
     * @access protected
     * @param mixed $stream
     * @return SocketStream
     */
    public function createStream($stream)
    {
        return new WebSocketStream($stream, $this);
    }
    
    /**
     * Accepts the array result from socket_select.  Iterates over the array, if it finds
     * a stagnant resource it attempts to close it out.  Otherwise reads messages from connected
     * socket streams.
     * 
     * @access protected
     * @param mixed $stream
     * @return void
     */
    protected function readStream($stream)
    {
        try{
            //iterate over the list of active streams, retrieve the stream
            //or create a new one.
            $socketStream = $this->findStreamByStream($stream);
            //read the buffer on the stream and return as a string.
            $message = $socketStream->read();
        }
        catch(\ErrorException $ex){
            $this->logger->debug('socket connection has been closed by peer, removing from collection');
            $this->close($stream);
            return;
        }
        
        $input = $this->cleanMessage($message);
        
        if(strlen($input) > 1){            
  
            if($socketStream->hasHandshake()){
                $this->processData($input, $socketStream);
            }
            else{
                $this->processHandshake($input, $socketStream);
            }

            
            if($socketStream->isClosed()){
                $this->close($stream);
            }
            $this->logger->debug("Received information from a socket ");
        }
        else if("\0" == $input || "" == $input){
            $this->close($stream);
        }
       
    }
    
    protected function processData($input, $socketStream)
    {
        //add information to existing stream, this might
        //be the trailing part of an incomplete message
        $socketStream->addData($input);
                
                
        //ask the stream if it has a complete message pending
        if($socketStream->hasMessage()){
            //treat as an array, could be multiple but typically
            //a single message is present.
            foreach($socketStream->getMessages() as $message){
                $type = SocketEvent::MESSAGE;
                
                if($message->isPing()){
                    //@todo add this as a constant.
                    $type = SocketEvent::PING;
                }
                
                $evt = new SocketEvent($socketStream, $type);
                $evt->setMessage($message);
                //dispatch event to server application that a message or ping has
                //come in from a websocket client
                $this->dispatch($evt);
            }
            //successfully iterated over messages, clean them out.
            $socketStream->clearMessages();
        }
        else{
            //the data is incomplete but the application still
            //can respond to this. 
            $evt = new SocketEvent($socketStream, 'data');
            $evt->setMessage($input);
            $this->dispatch($evt);

        }
    }

    protected function processHandshake($input, $socketStream)
    {
        
        //process handshake
        $this->logger->debug('begin websocket handshake');
        //create handshake response, need to refactor this to response class.
        $request = $this->createHandshakeRequest($input);
        $socketStream->setRequest($request);
        //send data back to client
        $response = $socketStream->shakeHands();    
        //notify application of this event.
        $evt = new SocketEvent($socketStream, 'handshake');
        $evt->setMessage($input);
        //dispatching handshake event.
        $this->dispatch($evt);            
    }
    
    protected function createHandshakeRequest($str)
    {
        //@todo turn this index a regex that just does the beginning and end
        if(strpos($str, 'GET /') !== 0){
            throw new MalformedWebSocketException('Unknown protocol HTTP protocol'. $str);
        }
        
        $arr = preg_split("/\r\n/", $str);//split all new lines
        
        //print_r($arr);
        array_shift($arr); //shift off the /HTTP/
        $headers = array();
        foreach($arr as $header){
            $headerPart = explode(':', $header);
            $headers[strtolower($headerPart[0])] = trim($headerPart[1]);
        }
        //print_r($headers);
        //UPPGRAAAYYYDE
        if(preg_match('/websocket/i', $headers['upgrade']) == 0 || preg_match('/upgrade/i', $headers['connection']) == 0){
            throw new MalformedWebSocketException('Bad header information given');
        }
        
        if(!isset($headers['sec-websocket-key'])){
            throw new MalformedWebSocketException('No key given');
        }
        
        $uri = $headers['origin'];
                
        $request = Request::create($uri);
        
        $request->headers->replace($headers);
        
        return $request;        
    }

}