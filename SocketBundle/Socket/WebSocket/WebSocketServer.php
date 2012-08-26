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
    /**
     * factory function to return SocketStream instance
     * 
     * @access protected
     * @param mixed $stream
     * @return SocketStream
     */
    protected function createStream($stream)
    {
        return new WebSocketStream($stream);
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
            $message = socket_read($stream, $this->chunkLength);
        }
        catch(\ErrorException $ex){
            $this->logger->debug('socket connection has been closed by peer, removing from collection');
            $this->close($stream);
            return;
        }
        
        $input = $this->cleanMessage($message);
        if(strlen($input) > 1){
            
            $socketStream = $this->findStreamByStream($stream);
            
            if($socketStream == false){
                $this->logger->err('failed to find socket stream by stream');
                return false;
            }
            else if($socketStream->hasHandshake()){
                $socketStream->addData($input);
                
                if($socketStream->hasMessage()){
                    foreach($socketStream->getMessages() as $message){
                        $type = SocketEvent::MESSAGE;
                        
                        if($message->isPing()){
                            //@todo add this as a constant.
                            $type = SocketEvent::PING;
                        }
                        
                        $evt = new SocketEvent($socketStream, $type);
                        $evt->setMessage($message);
                        $this->dispatch($evt);
                    }
                    $socketStream->clearMessages();
                }
                else{
                    $evt = new SocketEvent($socketStream, 'data');
                    $evt->setMessage($input);
                    $this->dispatch($evt);

                }
            }
            else{
                //process handshake
                $this->logger->debug('begin websocket handshake');
                $request = $this->createHandshakeRequest($input);
                $socketStream->setRequest($request);
                $response = $socketStream->shakeHands();    
                $evt = new SocketEvent($socketStream, 'handshake');
                $evt->setMessage($input);
                $this->dispatch($evt);    
            }

            
            if($socketStream->isClosed()){
                $this->close($stream);
            }
            $this->logger->debug("Received information from a socket ". substr($input, 0, 35));
        }
        else if($input == '' || $input == "\0"){
            $this->close($stream);
        }
    }
    
    protected function createHandshakeRequest($str)
    {
        //@todo turn this index a regex that just does the beginning and end
        if(strpos($str, 'GET /') !== 0){
            throw new MalformedWebSocketException('Unknown protocol HTTP protocol');
        }
        
        $arr = preg_split("/\r\n/", $str);//split all new lines
        array_shift($arr); //shift off the /HTTP/
        $headers = array();
        foreach($arr as $header){
            $headerPart = explode(':', $header);
            $headers[strtolower($headerPart[0])] = trim($headerPart[1]);
        }
        //UPPGRAAAYYYDE
        if(strtolower($headers['upgrade']) !== 'websocket' || strtolower($headers['connection']) !== 'upgrade'){
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