<?php
namespace Wordpress_Framework\Service_Manager\v1\Tests\Factory;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

use Wordpress_Framework\Service_Manager\v1\Tests\Factory\Test_Assets\Default_Object;

use Wordpress_Framework\Service_Manager\v1\Factory\Default_Factory;

use Wordpress_Framework\Service_Manager\v1\Service_Manager_Interface;

class Default_Factory_Test extends TestCase {
    
    public function test_can_create_object() {
        $service_manager = $this->getMockBuilder( Service_Manager_Interface::class )->getMock();
        $factory = new Default_Factory();
        $default_object = $factory( $service_manager, Default_Object::class );
        
        $this->assertInstanceOf( Default_Object::class, $default_object );
        $this->assertEquals( null , $default_object->options );
    }
    
    public function test_can_create_object_with_options() {
        $service_manager = $this->getMockBuilder( Service_Manager_Interface::class )->getMock();
        $factory = new Default_Factory();
        $default_object = $factory( $service_manager, Default_Object::class, ['test_key' => 'test_value'] );
        
        $this->assertInstanceOf( Default_Object::class, $default_object );
        $this->assertEquals( ['test_key' => 'test_value'] , $default_object->options );
    }
}