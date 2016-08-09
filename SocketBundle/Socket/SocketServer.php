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
     * Stores SocketStream objects by id.
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $socketHash = array();
    /**
     * eventDispatch
     * 
     * @var mixed
     * @access protected
     */
    protected $eventDispatcher;
    
    /**
     * serializer
     * 
     * @var mixed
     * @access protected
     */
    protected $serializer;
    
    /**
     * serializeFormat
     * 
     * (default value: 'plain')
     * 
     * @var string
     * @access protected
     */
    protected $serializeFormat = 'plain';
    
    /**
     * socket resource opened and listening to the specified port.
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $socket = false;
    
    
    /**
     * application name, used for events to discerne between one server and another
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $name = 'default';
    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $host (default: false)
     * @param mixed $port (default: false)
     * @return void
     */
    public function __construct($logger, $eventDispatcher, $serializer)
    {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }
    
    public function __destruct()
    {
        $this->shutdown();
    }
    
    public function getUrl()
    {
        return $this->getHost() . ':' . $this->getPort();
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
     * broadcast function.
     * 
     * @access public
     * @param mixed $input
     * @return void
     */
    public function broadcast($message)
    {
        $this->logger->debug('entering server broadcast ');
        
        foreach($this->socketStreams as $stream) {
            if($stream && method_exists($stream, 'sendMessage')){
                $stream->sendMessage($message);
            }
        }
    }
    
    /**
     * iterates over the internal collection of streams and opens any new connections
     * or read streams that have new information. 
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
        $this->logger->debug('dispatched socket open event');
        
        if($evt->isValid() && !$socket_stream->isClosed()){
            //$stream = $this->createStream($new_stream);
            
            $id = $this->generateStreamId();
            $this->logger->debug(sprintf('socket is valid, adding stream with id of [%s] to the collection', $id));    
            $this->streams[] = $new_stream;
            $this->socketStreams[] = $socket_stream;
            $socket_stream->setId($id);
            $this->socketHash[$id] = $socket_stream;
        
        }
        else{
            $this->logger->notify('socket deemed invalid, closing down and rejecting connection');
            $this->close($new_stream);
        }
    }
    
    protected function generateStreamId()
    {
        return md5(microtime());
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
        return new SocketStream($stream, $this);
    }
    
    
    public function serialize($data)
    {
        return $this->serializer->serialize($data, $this->serializeFormat);
    }
    
    protected function unserialize($str)
    {
        return $this->serializer->unserialize($str, $this->serializeFormat);
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
        foreach($this->streams as $index => $loop_stream){
            if($stream === $loop_stream){
                $socketStream = $this->socketStreams[$index];
                $id = $socketStream->getId();
                $socketStream->close();
                $evt = new SocketEvent($socketStream, SocketEvent::CLOSE);
                $this->dispatch($evt);
                $this->logger->debug(sprintf('closing down stream with id of [%s]', $id));
                unset($this->streamHash[$id]);
                array_splice($this->streams, $index, 1);
                array_splice($this->socketStreams, $index, 1);
                break;
            }
        }
        socket_close($stream);
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
        if($this->socket) {
            socket_close($this->socket);   
        }
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
        $name = $this->eventPrefix . '.' . ($this->hasName() == true ? $this->getName() . '.' . $evt->getType() : $evt->getType());
        
        $this->eventDispatcher->dispatch($name, $evt);
    }
    
    protected function findStreamByStream($stream)
    {
        foreach($this->streams as $index => $loopStream){
            if($loopStream === $stream){
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
            $socketStream = $this->findStreamByStream($stream);
            $message = $socketStream->read();
        }
        catch(\ErrorException $ex){
            $this->logger->debug('socket connection has been closed by peer, removing from collection');
            $this->close($stream);
            return;
        }
        
        $input = $this->cleanMessage($message);
        if(strlen($input) > 0){
            if($socketStream == false){
                $this->logger->err('failed to find socket stream by stream');
                return false;
            }
            
            $evt = new SocketEvent($socketStream, SocketEvent::MESSAGE);
            
            $evt->setMessage($input);
            
            $data = $this->serializer->unserialize($input, $this->serializeFormat);
            
            $evt->setData($data);
            
            $this->dispatch($evt);
            
            if($socketStream->isClosed()){
                $this->close($stream);
            }
            $this->logger->debug("Received information from a socket ". substr($input, 0, 35));
        }
        else {
            $this->close($stream);
        }
    }
    
    /**
     * setName function.
     * 
     * @access public
     * @param mixed $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * getName function.
     * 
     * @access public
     * @return void
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * hasName function.
     * 
     * @access public
     * @return void
     */
    public function hasName()
    {
        return $this->name != false;
    }
    

}
