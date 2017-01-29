<?php
namespace Wordpress_Framework\Service_Manager\v1\Factory;

use Wordpress_Framework\Service_Manager\v1\Service_Manager_Interface;

/**
 * Interface for a factory
 * A factory is an callable object that is able to create an object
 */
interface Factory_Interface {
    /**
     * Create an object
     *
     * @param  Service_Manager_Interface $service_manager
     * @param  string $requested_name
     * @param  null|array $options
     * @return mixed
     */
    public function __invoke( Service_Manager_Interface $service_manager, string $requested_name, array $options = null );
}
