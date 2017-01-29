<?php
namespace Wordpress_Framework\Service_Manager\v1;

use Wordpress_Framework\Config\v1\Config_Interface;

interface Service_Manager_Interface {
    
    /**
     * Build a service by service name, using optional options (services are NEVER cached)
     *
     * @param  string $service_name
     * @param  null|array $options
     * @return mixed
     */
    public function build( string $service_name, array $options = null );
    
    /**
     * Find or create an service instance of the manager and returns it
     *
     * @param  string $service_name
     * @return mixed
     */
    public function get( string $service_name );
    
    /**
     * Set new instance of some service to manager
     * 
     * @param  string $service_name
     * @param  mixed $service
     * @return void
     */
    public function set( string $service_name, $service );
    
    /**
     * Returns true if instance of service exist in manager
     *
     * @param  string $service_name
     * @return bool
     */
    public function has( string $service_name );
}