<?php


namespace MEF\SocketBundle\Socket\WebSocket;

/**
 * Message class.
 */
class Message implements \Serializable
{
    
    /**
     * _isText
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $_isText  = false;
    
    /**
     * _isPong
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $_isPong  = false;
    
    /**
     * _isPing
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $_isPing  = false;
    
    /**
     * _isClose
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $_isClose = false;
    
    protected $expectedLength = 0;
    
    protected $currentLength = 0;
    
    protected $buffer;
    
    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $msg
     * @return void
     */
    public function __construct($msg)
    {
        $this->setMessage($msg);
    }
    
    public static function create($msg)
    {
        return new Message($msg);
    }
    
    /**
     * setMessage function.
     * 
     * @access public
     * @param mixed $msg
     * @return void
     */
    public function setMessage($msg)
    {
        $this->message = $msg;
    }
    
    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * __toString function.
     * 
     * @access public
     * @return string
     */
    public function __toString()
    {
        return "$this->message";
    }
        
    /**
     * isText function.
     * 
     * @access public
     * @return boolean
     */
    public function isText()
    {
        return $this->_isText;
    }
    
    /**
     * isPing function.
     * 
     * @access public
     * @return boolean
     */
    public function isPing()
    {
        return $this->_isPing;
    }
    
    /**
     * isPong function.
     * 
     * @access public
     * @return boolean
     */
    public function isPong()
    {
        return $this->_isPong;
    }
    
    
    /**
     * isClose function.
     * 
     * @access public
     * @return boolean
     */
    public function isClose()
    {
        return $this->_isClose;
    }
    
    
    /**
     * setText function.
     * 
     * @access public
     * @param boolean $bool
     * @return void
     */
    public function setText($bool)
    {
        $this->clear();
        $this->_isText = true;
    }
    
    /**
     * setClose function.
     * 
     * @access public
     * @param boolean $bool
     * @return void
     */
    public function setClose($bool)
    {
        $this->clear();
        $this->_isClose = $bool;
    }
    
    /**
     * setPing function.
     * 
     * @access public
     * @param boolean $bool
     * @return void
     */
    public function setPing($bool)
    {
        $this->clear();
        $this->_isPing = $bool;
    }
    
    /**
     * setPong function.
     * 
     * @access public
     * @param boolean $bool
     * @return void
     */
    public function setPong($bool)
    {
        $this->clear();
        $this->_isPong= true;
    }
    
    /**
     * setTypeByCode function.
     * 
     * @access public
     * @param int $code
     * @return void
     */
    public function setTypeByCode($code)
    {
    
        switch($code){
            case ByteBuffer::TEXT_BYTE:
                $this->setText(true);
                break;
            case ByteBuffer::CLOSE_BYTE:
                $this->setClose(true);
                break;
            case ByteBuffer::PING_BYTE:
                $this->setPing(true);
                break;
            case ByteBuffer::PONG_BYTE:
                $this->setPong(true);
                break;
        }
        
    }
    
    /**
     * setType function.
     * 
     * @access public
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        switch($type){
            case 'text':
                $this->setText(true);
                break;
            case 'close':
                $this->setClose(true);
                break;
            case 'ping':
                $this->setPing(true);
                break;
            case 'pong':
                $this->setPong(true);
                break;
        }
    }
    
    public function clear()
    {
        $this->_isText = $this->_isClose = $this->_isPong = $this->_isPing = false;
    }
    
    public function serialize()
    {
        return "$this";
    }
    
    public function unserialize($str)
    {
        $this->setMessage($str);
    }
    
}