<?php


namespace MEF\SocketBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * ApplicationTestCase class.  Boots an instance of the kernel and creates the 
 * service container, storing both in static properties.
 * 
 * @extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
 */
class ApplicationTestCase extends WebTestCase
{
    
    /**
     * kernel
     * 
     * @var mixed
     * @access protected
     * @static
     */
    protected static $kernel;
    /**
     * container
     * 
     * @var mixed
     * @access protected
     * @static
     */
    protected static $container;
    
    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $name (default: null)
     * @return void
     */
    public function __construct($name=null)
    {
        
        parent::__construct($name);
        
        static::$kernel = static::createKernel();
        
        static::$kernel->boot();
        
        static::$container = static::$kernel->getContainer();
        
        
    }
    
    /**
     * get function.
     * 
     * @access public
     * @param mixed $key
     * @return mixed
     */
    public function get($key)
    {
        
        return static::$container->get($key);
    
    }
    
    /**
     * getContainer function.
     * 
     * @access public
     * @return mixed
     */
    public function getContainer()
    {
    
        return static::$container;
    
    }
    
}
