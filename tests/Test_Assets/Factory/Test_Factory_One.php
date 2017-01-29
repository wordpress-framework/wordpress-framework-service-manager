<?php
namespace Wordpress_Framework\Service_Manager\v1\Tests\Test_Assets\Factory;

use Wordpress_Framework\Service_Manager\v1\Factory\Factory_Interface;
use Wordpress_Framework\Service_Manager\v1\Service_Manager_Interface;

final class Test_Factory_One implements Factory_Interface {
    /**
     * @inheritDoc
     */
    public function __invoke( Service_Manager_Interface $service_manager, string $requested_name, array $options = null ) {
        return new $requested_name( ['test_option' => 'option_value_added_by_test_factory_one'] );
    }
}
