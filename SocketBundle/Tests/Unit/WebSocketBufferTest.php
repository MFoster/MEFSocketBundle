<?php

namespace MEF\SocketBundle\Tests\Unit;
use  MEF\SocketBundle\Test\ApplicationTestCase;
use MEF\SocketBundle\Socket\WebSocket\ByteBuffer;

/**
 * SocketServerTest class.
 */
class WebSocketBufferTest extends ApplicationTestCase
{

   public $helloArray = array("104","101","108","108","111");

   /**
    * testBuffer function.
    * 
    * @access public
    * @return void
    * @group websocket
    * @group websocket_buffer
    */
   public function testBuffer()
   {
       $buff = new ByteBuffer($this->helloArray);
       
       $bool = $buff == 'hello';
       
       $this->assertTrue($bool);
       
   }
   
   /**
    * testMaskBuffer function.
    * 
    * @access public
    * @return void
    * @group websocket
    * @group websocket_buffer
    */
   public function testMaskBuffer()
   {
        $mask = array(50, 52, 54, 56);
        
        $buff = new ByteBuffer($this->helloArray);
        
        $masked = $buff->mask($mask);
        
        $unmasked = $masked->unmask($mask);
        
        $maskBool = $masked != 'hello';
        
        $unmaskedBool = $unmasked == 'hello';
        
        $this->assertTrue($maskBool, 'Masked bool matched hello, failed to mask data');
        
        $this->assertTrue($unmaskedBool, 'Unmasked bool did not match hello, failed to unmask data');
       
       
   }
   
   /**
    * testBufferIterable function.
    * 
    * @access public
    * @return void
    * @group websocket
    * @group websocket_buffer
    */
   public function testBufferIterable()
   {
       $buff = new ByteBuffer($this->helloArray);
       
       $msg = '';
       
       foreach($buff as $val){
           $msg .= chr($val);
       }
       
       $this->assertEquals($msg, 'hello');
       
   }
   
   /**
    * testBufferLength function.
    * 
    * @access public
    * @return void
    * @group websocket
    * @group websocket_buffer
    */
   public function testBufferLength()
   {
       
       for($i = 0; $i < 10; $i++){
           $val = 10000 * $i;
         
           $buffer = ByteBuffer::parseNumberToBuffer($val);
       
           $this->assertEquals($val, $buffer->sum());

       }
             
   }
   
   /**
    * testBufferFixedValue function.
    * 
    * @access public
    * @return void
    * @group websocket
    * @group websocket_buffer
    */
   public function testBufferFixedValue()
   {
       $arr = array(0,1,56,128,37,40,72,148);
       
       $buffer = ByteBuffer::create($arr);
       
       $this->assertEquals(343598007077012, $buffer->sum());
       
   }
   
   

}