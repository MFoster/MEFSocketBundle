<?php


namespace MEF\SocketBundle\Socket\WebSocket;

/**
 * Message class.
 */
class JsonMessage extends Message
{
    
    public static function decode($str)
    {
        return json_decode($str);
    }
    
    public static function create($obj = false)
    {
        return new JsonMessage($obj);
    }
    
    public function setMessage($obj)
    {
        if(is_string($obj)){
            parent::setMessage($obj);
        }
        else{
            $json = json_encode($obj);
            if(false === $json){
                throw \InvalidArgumentException('Object passed to '. __CLASS__ . '\'s ' . __METHOD__ . ' did not evaluate to json');
            }
            parent::setMessage($json);
        }
        return $this;
        
    }



}