<?php
namespace Wordpress_Framework\Service_Manager\v1\Factory;

use Wordpress_Framework\Service_Manager\v1\Service_Manager_Interface;

final class Default_Factory implements Factory_Interface {
    /**
     * @inheritDoc
     */
    public function __invoke( Service_Manager_Interface $service_manager, string $requested_name, array $options = null ) {
        return ( null === $options ) ? new $requested_name() : new $requested_name( $options );
    }
}
