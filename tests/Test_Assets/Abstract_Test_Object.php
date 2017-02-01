<?php
namespace Wordpress_Framework\Service_Manager\v1\Tests\Test_Assets;

use Wordpress_Framework\Service_Manager\v1\Service_Manager_Interface;

abstract class Abstract_Test_Object {

    /**
     * @var Service_Manager_Interface
     */
    public $service_manager;

    /**
     * @var array
     */
    public $options;

    /**
     * @param null|array $options
     */
    public function __construct( array $options = null, Service_Manager_Interface $service_manager = null ) {
        $this->options = $options;
        $this->service_manager = $service_manager;
    }

    /**
     * @return null|array
     */
    public function get_options() {
        return $this->options;
    }
}
