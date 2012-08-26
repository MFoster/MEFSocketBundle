<?php

namespace MEF\SocketBundle\Socket;

/**
 * Socket class acts a proxy class around native php socket methods.
 */
class SocketServer extends SocketBase
{
    
    /**
     * eventPrefix
     * 
     * (default value: 'socket')
     * 
     * @var string
     * @access protected
     */
    protected $eventPrefix = 'socket';
    /**
     * reference to a logger instance
     * 
     * @var mixed
     * @access protected
     */
    protected $logger;
    
    /**
     * array of socket stream references
     * 
     * @var mixed
     * @access protected
     */
    protected $streams = array();
    
    /**
     * socketStreams
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $socketStreams = array();
    /**
     * eventDispatch
     * 
     * @var mixed
     * @access protected
     */
    protected $eventDispatcher;
    
    
    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $host (default: false)
     * @param mixed $port (default: false)
     * @return void
     */
    public function __construct($logger, $eventDispatcher)
    {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function __destruct()
    {
        $this->shutdown();
    }
    
       
    /**
     * kicks off socket listening.
     * 
     * @access public
     * @return void
     */
    public function listen()
    {
        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
        
        if(!socket_bind($this->socket, $this->getHost(), $this->getPort())){
            return false;
        }
        
        socket_listen($this->socket);
        
        //stream_socket_server('tcp://'. $this->getHost() . ':' . $this->getPort());
        
        if(!$this->socket){
            return false;
        }
        
        $this->streams[] = $this->socket;
        $this->socketStreams[] = null;//placeholder to keep sync
        
        while(true){
            $this->loop();
        }
        
                
    }
    
    /**
     * loop function.
     * 
     * @access protected
     * @return void
     */
    protected function loop()
    {       
        $n = null;
        $streams = $this->streams;
        $num = socket_select($streams, $n, $n, $n);
        
        $this->logger->debug('num socks that have changed '. $num);
        foreach($streams as $stream){
            if($stream == $this->socket){
                $this->open();
            }
            else{
                $this->readStream($stream);
            }
        }            
            
    }
    
    /**
     * opens a new socket connection.
     * 
     * @access protected
     * @return void
     */
    protected function open()
    {
        $new_stream = socket_accept($this->socket);
        $socket_stream = $this->createStream($new_stream);
        $evt = new SocketEvent($socket_stream, SocketEvent::OPEN);
        $this->dispatch($evt);
        if($evt->isValid() && !$socket_stream->isClosed()){
            $this->streams[] = $new_stream;
            $this->socketStreams[] = $this->createStream($new_stream);
        }
        else{
            $this->close($new_stream);
        }
    }
    
    /**
     * factory function to return SocketStream instance
     * 
     * @access protected
     * @param mixed $stream
     * @return SocketStream
     */
    protected function createStream($stream)
    {
        return new SocketStream($stream);
    }
    
    /**
     * Dispatches close event for a socket stream and removes it from
     * the stream collection and closes the socket stream.  Use this method when
     * closing a stream, closing a stream elsewhere would be fatal to the socket server.
     * The resource will become stagnant but remain in the stream array, causing a memory leak.
     *
     * TLDR; Don't you dare use socket_close anywhere else!
     * 
     * @access protected
     * @param mixed $stream
     * @return void
     */
    protected function close($stream)
    {
        $evt = new SocketEvent($stream, SocketEvent::CLOSE);
        $this->dispatch($evt);
        socket_close($stream);
        foreach($this->streams as $index => $loop_stream){
            if($stream == $loop_stream){
                array_splice($this->streams, $index, 1);
                array_splice($this->socketStreams, $index, 1);
                break;
            }
        }
    }
    
    
    /**
     * Closes the "master socket".  This is the resource that gets created by the listen method.
     * Should only be called by the destructor.
     * 
     * @access protected
     * @return void
     */
    protected function shutdown()
    {
        socket_close($this->socket);
    }
    
    /**
     * Convienence method for delegating events to the event dispatcher.
     * 
     * @access protected
     * @param mixed $type
     * @param mixed $evt
     * @return void
     */
    protected function dispatch($evt)
    {
        $name = $this->eventPrefix . '.' . $evt->getType();
        
        $this->eventDispatcher->dispatch($name, $evt);
    }
    
    protected function findStreamByStream($stream)
    {
        foreach($this->streams as $index => $loopStream){
            if($loopStream == $stream){
                return $this->socketStreams[$index];
            }
        }
        return false;
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
        if(strlen($input) > 0){
            
            $socketStream = $this->findStreamByStream($stream);
            
            if($socketStream == false){
                $this->logger->err('failed to find socket stream by stream');
                return false;
            }
            $evt = new SocketEvent($socketStream, SocketEvent::MESSAGE);
            $evt->setMessage($input);
            $this->dispatch($evt);
            if($socketStream->isClosed()){
                $this->close($stream);
            }
            $this->logger->debug("Received information from a socket ". substr($input, 0, 35));
        }
    }
    
    
    

}