<?php
namespace Wordpress_Framework\Service_Manager\v1\Tests;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

use Wordpress_Framework\Service_Manager\v1\Tests\Test_Assets\Test_Object_One;
use Wordpress_Framework\Service_Manager\v1\Tests\Test_Assets\Test_Object_Two;

use Wordpress_Framework\Service_Manager\v1\Tests\Test_Assets\Factory\Test_Factory_One;
use Wordpress_Framework\Service_Manager\v1\Tests\Test_Assets\Factory\Test_Factory_Two;

use Wordpress_Framework\Config\v1\Config;
use Wordpress_Framework\Service_Manager\v1\Service_Manager;
use Wordpress_Framework\Service_Manager\v1\Factory\Default_Factory;

use ReflectionClass;

class Service_Manager_Test extends TestCase {

    public function setUp() {
        $this->config = new Config( [
            'aliases' => [
                'test_alias_to_object' => Test_Object_One::class,
                'test_alias_to_factory' => 'test_factory'
            ],
            'factories' => [
                Test_Object_One::class => Test_Factory_One::class,
                'test_factory' => Test_Factory_Two::class
            ],
            'shared' => [
                Test_Object_Two::class => false
            ],
            'shared_by_default' => true
        ], 'read_and_write' );
    }

    public function test_correct_construct() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );

        $aliases_property = $service_manager_reflection->getProperty( 'aliases' );
        $aliases_property->setAccessible( true );
        $this->assertEquals( $this->config->aliases->to_array() , $aliases_property->getValue( $service_manager )->to_array() );

        $factories_property = $service_manager_reflection->getProperty( 'factories' );
        $factories_property->setAccessible( true );
        $this->assertEquals( $this->config->factories->to_array() , $factories_property->getValue( $service_manager )->to_array() );

        $shared_property = $service_manager_reflection->getProperty( 'shared' );
        $shared_property->setAccessible( true );
        $this->assertEquals( $this->config->shared->to_array() , $shared_property->getValue( $service_manager )->to_array() );

        $shared_by_default = $service_manager_reflection->getProperty( 'shared_by_default' );
        $shared_by_default->setAccessible( true );
        $this->assertTrue( $shared_by_default->getValue( $service_manager ) );
    }

    public function test_construct_with_creation_service_manager() {
        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $creation_service_manager_property = $service_manager_reflection->getProperty( 'creation_service_manager' );
        $creation_service_manager_property->setAccessible( true );

        $service_manager = new Service_Manager( $this->config );
        $this->assertSame( $service_manager, $creation_service_manager_property->getValue( $service_manager ) );

        $child_service_manager = new Service_Manager( new Config(), 'can_override', $service_manager );
        $this->assertSame( $service_manager, $creation_service_manager_property->getValue( $child_service_manager ) );
    }

    public function test_get_service_factory() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $get_service_factory_method = $service_manager_reflection->getMethod( 'get_service_factory' );
        $get_service_factory_method->setAccessible( true );

        $this->assertInstanceOf( Test_Factory_One::class, $get_service_factory_method->invokeArgs( $service_manager, [Test_Object_One::class] ) );
        $this->assertInstanceOf( Test_Factory_Two::class, $get_service_factory_method->invokeArgs( $service_manager, ['test_factory'] ) );
        $this->assertInstanceOf( Default_Factory::class, $get_service_factory_method->invokeArgs( $service_manager, [Test_Object_Two::class] ) );

        //test cahe factory object
        $factories_property = $service_manager_reflection->getProperty( 'factories' );
        $factories_property->setAccessible( true );
        $this->assertInstanceOf( Test_Factory_One::class, $factories_property->getValue( $service_manager )->get( Test_Object_One::class ) );
        $this->assertInstanceOf( Test_Factory_Two::class, $factories_property->getValue( $service_manager )->get( 'test_factory' ) );
        $this->assertInstanceOf( Default_Factory::class, $factories_property->getValue( $service_manager )->get( Test_Object_Two::class ) );
    }

    /**
     * @depends test_get_service_factory
     */
    public function test_create_service_object() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $create_service_object_method = $service_manager_reflection->getMethod( 'create_service_object' );
        $create_service_object_method->setAccessible( true );

        $this->assertInstanceOf( Test_Object_One::class, $create_service_object_method->invokeArgs( $service_manager, [Test_Object_One::class] ) );
        $this->assertInstanceOf( Test_Object_One::class, $create_service_object_method->invokeArgs( $service_manager, ['test_factory'] ) );
        $this->assertInstanceOf( Test_Object_Two::class, $create_service_object_method->invokeArgs( $service_manager, [Test_Object_Two::class] ) );
    }

    /**
     * @depends test_get_service_factory
     */
    public function test_create_service_object_with_other_creation_service_manager() {

        $creation_service_manager = new Service_Manager( $this->config );
        $service_manager = new Service_Manager( $this->config, 'can_not_override', $creation_service_manager );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $create_service_object_method = $service_manager_reflection->getMethod( 'create_service_object' );
        $create_service_object_method->setAccessible( true );

        $object = $create_service_object_method->invokeArgs( $creation_service_manager, [Test_Object_One::class] );
        $this->assertSame( $creation_service_manager, $object->service_manager );

        $object = $create_service_object_method->invokeArgs( $service_manager, [Test_Object_One::class] );
        $this->assertSame( $creation_service_manager, $object->service_manager );
    }

    /**
     * @depends test_create_service_object
     */
    public function test_build() {
        $service_manager = new Service_Manager( $this->config );

        $this->assertInstanceOf( Test_Object_One::class, $service_manager->build( Test_Object_One::class ) );
        $this->assertInstanceOf( Test_Object_One::class, $service_manager->build( 'test_factory' ) );
        $this->assertInstanceOf( Test_Object_Two::class, $service_manager->build( Test_Object_Two::class ) );
    }

    /**
     * @depends test_create_service_object
     */
    public function test_get() {
        $service_manager = new Service_Manager( $this->config );

        $this->assertInstanceOf( Test_Object_One::class, $service_manager->get( Test_Object_One::class ) );
        $this->assertInstanceOf( Test_Object_One::class, $service_manager->get( 'test_factory' ) );
        $this->assertInstanceOf( Test_Object_Two::class, $service_manager->get( Test_Object_Two::class ) );
    }

    /**
     * @depends test_get
     * @group shared_tests
     */
    public function test_shared() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $services_property = $service_manager_reflection->getProperty( 'services' );
        $services_property->setAccessible( true );

        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        $test_one_object = $service_manager->get( Test_Object_One::class );
        $this->assertEquals( [Test_Object_One::class => $test_one_object] , $services_property->getValue( $service_manager ) );

        $test_one_object_by_test_factory = $service_manager->get( 'test_factory' );
        $this->assertEquals( [
            Test_Object_One::class => $test_one_object,
            'test_factory' => $test_one_object_by_test_factory
        ] , $services_property->getValue( $service_manager ) );

        $service_manager->get( Test_Object_Two::class );
        $this->assertEquals( [
            Test_Object_One::class => $test_one_object,
            'test_factory' => $test_one_object_by_test_factory
        ] , $services_property->getValue( $service_manager ) );
    }

    /**
     * @depends test_get
     * @group shared_tests
     */
    public function test_aliases_shared() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $services_property = $service_manager_reflection->getProperty( 'services' );
        $services_property->setAccessible( true );

        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        $test_one_object = $service_manager->get( 'test_alias_to_object' );
        $this->assertEquals( [
            Test_Object_One::class => $test_one_object,
            'test_alias_to_object' => $test_one_object
        ] , $services_property->getValue( $service_manager ) );

        $test_one_object_by_test_factory = $service_manager->get( 'test_alias_to_factory' );
        $this->assertEquals( [
            Test_Object_One::class => $test_one_object,
            'test_alias_to_object' => $test_one_object,
            'test_factory' => $test_one_object_by_test_factory,
            'test_alias_to_factory' => $test_one_object_by_test_factory
        ] , $services_property->getValue( $service_manager ) );
    }

    /**
     * @depends test_get
     * @group shared_tests
     */
    public function test_shared_when_no_shared_by_default() {
        $config = clone $this->config;
        $config->shared_by_default = false;

        $service_manager = new Service_Manager( $config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $services_property = $service_manager_reflection->getProperty( 'services' );
        $services_property->setAccessible( true );

        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        $service_manager->get( Test_Object_One::class );
        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        $service_manager->get( 'test_factory' );
        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        $service_manager->get( Test_Object_Two::class );
        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );
    }

    /**
     * @depends test_get
     * @group shared_tests
     */
    public function test_shared_when_no_shared_by_default_and_object_has_define_shared() {
        $config = clone $this->config;
        $config->shared_by_default = false;
        $config->shared->set( Test_Object_One::class, true );

        $service_manager = new Service_Manager( $config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $services_property = $service_manager_reflection->getProperty( 'services' );
        $services_property->setAccessible( true );

        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        $test_one_object = $service_manager->get( Test_Object_One::class );
        $this->assertEquals( [Test_Object_One::class => $test_one_object] , $services_property->getValue( $service_manager ) );

        $test_one_object_by_test_factory = $service_manager->get( 'test_factory' );
        $this->assertEquals( [
            Test_Object_One::class => $test_one_object
        ] , $services_property->getValue( $service_manager ) );

        $service_manager->get( Test_Object_Two::class );
        $this->assertEquals( [
            Test_Object_One::class => $test_one_object
        ] , $services_property->getValue( $service_manager ) );
    }

    /**
     * @depends test_get
     */
    public function test_has() {
        $service_manager = new Service_Manager( $this->config );

        $this->assertFalse( $service_manager->has( Test_Object_One::class ) );
        $service_manager->get( Test_Object_One::class );
        $this->assertTrue( $service_manager->has( Test_Object_One::class ) );

        $this->assertFalse( $service_manager->has( Test_Object_Two::class ) );
        $service_manager->get( Test_Object_Two::class );
        $this->assertFalse( $service_manager->has( Test_Object_Two::class ) );
    }

    public function test_override() {
        $service_manager = new Service_Manager( $this->config );
        $this->assertFalse( $service_manager->can_override() );
        $service_manager->forbid_override();
        $this->assertFalse( $service_manager->can_override() );

        $service_manager = new Service_Manager( $this->config, 'can_override' );

        $this->assertTrue( $service_manager->can_override() );
        $service_manager->forbid_override();
        $this->assertFalse( $service_manager->can_override() );
    }

    /**
     * @depends test_override
     */
    public function test_set() {
        $service_manager = new Service_Manager( $this->config, 'can_override' );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $services_property = $service_manager_reflection->getProperty( 'services' );
        $services_property->setAccessible( true );

        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        //add new test
        $test_two_object = new Test_Object_Two();
        $service_manager->set( Test_Object_Two::class, $test_two_object );
        $this->assertEquals( [Test_Object_Two::class => $test_two_object] , $services_property->getValue( $service_manager ) );

        //override test
        $test_one_object = $service_manager->get( Test_Object_One::class );
        $this->assertEquals( [
            Test_Object_Two::class => $test_two_object,
            Test_Object_One::class => $test_one_object
        ] , $services_property->getValue( $service_manager ) );

        $test_one_object_two = new Test_Object_One( ['test_set' => 'test'] );
        $service_manager->set( Test_Object_One::class, $test_one_object_two );
        $this->assertEquals( [
            Test_Object_Two::class => $test_two_object,
            Test_Object_One::class => $test_one_object_two
        ] , $services_property->getValue( $service_manager ) );

        $test_two_object_two  = new Test_Object_Two( ['test_set' => 'test'] );
        $service_manager->set( Test_Object_Two::class, $test_two_object_two );
        $this->assertEquals( [
            Test_Object_Two::class => $test_two_object_two,
            Test_Object_One::class => $test_one_object_two
        ] , $services_property->getValue( $service_manager ) );
    }

    /**
     * @depends test_override
     * @expectedException \Wordpress_Framework\Service_Manager\v1\Exception\Service_Manager_Modifications_Not_Allowed_Exception
     */
    public function test_set_when_can_not_override() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $services_property = $service_manager_reflection->getProperty( 'services' );
        $services_property->setAccessible( true );

        $this->assertEquals( [] , $services_property->getValue( $service_manager ) );

        //add new
        $test_two_object = new Test_Object_Two();
        $service_manager->set( Test_Object_Two::class, $test_two_object );
        $this->assertEquals( [Test_Object_Two::class => $test_two_object] , $services_property->getValue( $service_manager ) );

        //override test
        $test_one_object = $service_manager->get( Test_Object_One::class );
        $this->assertEquals( [
            Test_Object_Two::class => $test_two_object,
            Test_Object_One::class => $test_one_object
        ] , $services_property->getValue( $service_manager ) );

        $test_one_object_two = new Test_Object_One( ['test_set' => 'test'] );
        $service_manager->set( Test_Object_One::class, $test_one_object_two );
    }

    /**
     * @depends test_override
     */
    public function test_set_alias() {
        $service_manager = new Service_Manager( $this->config, 'can_override' );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $aliases_property = $service_manager_reflection->getProperty( 'aliases' );
        $aliases_property->setAccessible( true );

        //add new
        $service_manager->set_alias( 'test_alias_new', Test_Object_One::class );

        $this->assertEquals( [
            'test_alias_to_object' => Test_Object_One::class,
            'test_alias_to_factory' => 'test_factory',
            'test_alias_new' => Test_Object_One::class
        ] , $aliases_property->getValue( $service_manager )->to_array() );

        //override test
        $service_manager->set_alias( 'test_alias_to_object', Test_Object_Two::class );

        $this->assertEquals( [
            'test_alias_to_object' => Test_Object_Two::class,
            'test_alias_to_factory' => 'test_factory',
            'test_alias_new' => Test_Object_One::class
        ] , $aliases_property->getValue( $service_manager )->to_array() );
    }

    /**
     * @depends test_override
     * @expectedException \Wordpress_Framework\Service_Manager\v1\Exception\Service_Manager_Modifications_Not_Allowed_Exception
     */
    public function test_set_alias_when_can_not_override() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $aliases_property = $service_manager_reflection->getProperty( 'aliases' );
        $aliases_property->setAccessible( true );

        //add new
        $service_manager->set_alias( 'test_alias_new', Test_Object_One::class );

        $this->assertEquals( [
            'test_alias_to_object' => Test_Object_One::class,
            'test_alias_to_factory' => 'test_factory',
            'test_alias_new' => Test_Object_One::class
        ] , $aliases_property->getValue( $service_manager )->to_array() );

        //override test
        $service_manager->set_alias( 'test_alias_to_object', Test_Object_Two::class );
    }

    /**
     * @depends test_override
     */
    public function test_set_factory() {
        $service_manager = new Service_Manager( $this->config, 'can_override' );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $factories_property = $service_manager_reflection->getProperty( 'factories' );
        $factories_property->setAccessible( true );

        //add new
        $service_manager->set_factory( 'test_factory_new', Test_Factory_One::class );

        $this->assertEquals( [
            Test_Object_One::class => Test_Factory_One::class,
            'test_factory' => Test_Factory_Two::class,
            'test_factory_new' => Test_Factory_One::class
        ] , $factories_property->getValue( $service_manager )->to_array() );

        //override test
        $service_manager->set_factory( Test_Object_One::class, Test_Factory_Two::class );

        $this->assertEquals( [
            Test_Object_One::class => Test_Factory_Two::class,
            'test_factory' => Test_Factory_Two::class,
            'test_factory_new' => Test_Factory_One::class
        ] , $factories_property->getValue( $service_manager )->to_array() );
    }

    /**
     * @depends test_override
     * @expectedException \Wordpress_Framework\Service_Manager\v1\Exception\Service_Manager_Modifications_Not_Allowed_Exception
     */
    public function test_set_factory_when_can_not_override() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $factories_property = $service_manager_reflection->getProperty( 'factories' );
        $factories_property->setAccessible( true );

        //add new
        $service_manager->set_factory( 'test_factory_new', Test_Factory_One::class );

        $this->assertEquals( [
            Test_Object_One::class => Test_Factory_One::class,
            'test_factory' => Test_Factory_Two::class,
            'test_factory_new' => Test_Factory_One::class
        ] , $factories_property->getValue( $service_manager )->to_array() );

        //override test
        $service_manager->set_factory( Test_Object_One::class, Test_Factory_Two::class );
    }

    /**
     * @depends test_override
     */
    public function test_set_shared() {
        $service_manager = new Service_Manager( $this->config, 'can_override' );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $shared_property = $service_manager_reflection->getProperty( 'shared' );
        $shared_property->setAccessible( true );

        //add new
        $service_manager->set_shared( Test_Object_One::class, true );

        $this->assertEquals( [
            Test_Object_Two::class => false,
            Test_Object_One::class => true
        ] , $shared_property->getValue( $service_manager )->to_array() );

        //override test
        $service_manager->set_shared( Test_Object_Two::class, true );

        $this->assertEquals( [
            Test_Object_Two::class => true,
            Test_Object_One::class => true
        ] , $shared_property->getValue( $service_manager )->to_array() );
    }

    /**
     * @depends test_override
     * @expectedException \Wordpress_Framework\Service_Manager\v1\Exception\Service_Manager_Modifications_Not_Allowed_Exception
     */
    public function test_set_shared_when_can_not_override() {
        $service_manager = new Service_Manager( $this->config );

        $service_manager_reflection = new ReflectionClass( Service_Manager::class );
        $shared_property = $service_manager_reflection->getProperty( 'shared' );
        $shared_property->setAccessible( true );

        //add new
        $service_manager->set_shared( Test_Object_One::class, true );

        $this->assertEquals( [
            Test_Object_Two::class => false,
            Test_Object_One::class => true
        ] , $shared_property->getValue( $service_manager )->to_array() );

        //override test
        $service_manager->set_shared( Test_Object_Two::class, true );
    }
}
