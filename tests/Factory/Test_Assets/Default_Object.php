<?php
namespace Wordpress_Framework\Service_Manager\v1\Tests\Factory\Test_Assets;

class Default_Object {
    
    /**
     * @var array
     */
    public $options;
    
    /**
     * @param null|array $options
     */
    public function __construct( array $options = null ) {
        $this->options = $options;
    }
    
    /**
     * @return null|array
     */
    public function get_options() {
        return $this->options;
    }
}