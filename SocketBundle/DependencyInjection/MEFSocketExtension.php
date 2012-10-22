<?php
/**
 * Interacts with the ContainerBuilder to load up the services provided 
 * by the bundle.  Also registers any compiler passes and modifies service definitions
 * where configuration options dictate.
 */

namespace MEF\SocketBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MEFSocketExtension extends Extension
{
    
    /**
     * load function.
     * 
     * @access public
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        
        $server = $container->getDefinition('mef.socket.server');
        
        $client = $container->getDefinition('mef.socket.client');
        
        //if host and port are set directly, that will be used
        if(isset($config['host']) && isset($config['port'])){
            $this->setServerClientCombo($server, $client, $config);
            $container->setParameter('mef.socket.url', $config['host']. ':'. $config['port']);
        }//tcp specific settings, web also inherits this unless overriden
        else if(isset($config['tcp'])){
            $this->setServerClientCombo($server, $client, $config['tcp']);
            $container->setParameter('mef.socket.url', 'tcp://' . $config['tcp']['host']. ':'. $config['tcp']['port']);
        }
        
        //web setting override
        if(isset($config['web'])){
            $server = $container->getDefinition('mef.websocket.server');
        
            $client = $container->getDefinition('mef.websocket.client');
            
            $this->setServerClientCombo($server, $client, $config['web']);
            
            $container->setParameter('mef.websocket.url', 'ws://' . $config['web']['host'] . ':' . $config['web']['port']);
        }
        
        if(isset($config['servers'])){
            $loggerDef = new Reference('logger');
            $eventDispatcher = new Reference('event_dispatcher');
            foreach($config['servers'] as $name => $serverConfig){
                $className = ($serverConfig['protocol'] == 'web' ? 
                             $container->getParameter('mef.websocket.server.class') : 
                             $container->getParameter('mef.socket.server.class'));
                             
                $serverDef = new Definition($className);
                $serverDef->addMethodCall('setName', array($name));
                $serverDef->addMethodCall('setHost', array($serverConfig['host']));
                $serverDef->addMethodCall('setPort', array($serverConfig['port']));
                $serverDef->setArguments(array($loggerDef, $eventDispatcher));
                $container->setDefinition(sprintf('socket.%s.server', $name), $serverDef);
            }
        }
        
        if(isset($config['clients'])){
            foreach($config['clients'] as $name => $clientConfig){
                $className = ($clientConfig['protocol'] == 'web' ? 
                             $container->getParameter('mef.websocket.client.class') : 
                             $container->getParameter('mef.socket.client.class'));
                             
                $clientDef = new Definition($className);
                $clientDef->addMethodCall('setHost', array($clientConfig['host']));
                $clientDef->addMethodCall('setPort', array($clientConfig['port']));
                $container->setDefinition(sprintf('socket.%s.client', $name), $clientDef);
                
            }
        }
        
        
    }
    
    protected function setServerClientCombo($server, $client, $config)
    {
        
        $server->addMethodCall('setHost', array($config['host']));
        $server->addMethodCall('setPort', array($config['port']));
        
        $client->addMethodCall('setHost', array($config['host']));
        $client->addMethodCall('setPort', array($config['port']));        
        
    }
    
    
}
