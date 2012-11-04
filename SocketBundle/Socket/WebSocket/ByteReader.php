<?php

namespace MEF\SocketBundle\Socket\WebSocket;


class ByteReader
{
    
    protected $count = 0;
    
    
    public static function create($socket)
    {
        return new ByteReader($socket);
    }
    
    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    public function read($callable, $num = false)
    {
        if(is_callable($callable) && $num){
            return $this-readIterable($callable, $num);
        } else{
            $num = $callable;
        }
        
        $buffer = ByteBuffer::create();
        $count = 0;
        
        while($count < $num){
            $byte = fgetc($this->socket);
            $buffer->push($byte);
            $count++;
        }
        
        $this->count += $count;
        
        return $buffer;
        
    }
    
    protected function readIterable($callable, $num)
    {
        $count = 0;
        while($count < $num){
            $byte = fgetc($this->socket);
            $result = $callable($byte, $count, $this->count);
            $count++;
        }
        
        return $result;
        
    }
    



}