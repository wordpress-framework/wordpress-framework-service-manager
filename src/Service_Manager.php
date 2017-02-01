<?php
namespace Wordpress_Framework\Service_Manager\v1;

use Wordpress_Framework\Service_Manager\v1\Exception\Service_Manager_Modifications_Not_Allowed_Exception;

use Wordpress_Framework\Service_Manager\v1\Factory\Factory_Interface;
use Wordpress_Framework\Service_Manager\v1\Factory\Default_Factory;

use Wordpress_Framework\Config\v1\Config_Interface;
use Wordpress_Framework\Config\v1\Config;

class Service_Manager implements Service_Manager_Interface {

    /**
     * A list of already loaded services
     *
     * @var array
     */
    protected $services = [];

    /**
     * Flag specifying whether modifications to services are allowed
     *
     * @param string
     */
    protected $allow_override;

    /**
     * Service manager that will be inserted into the factory when creating object
     *
     * @var Service_Manager_Interface
     */
    protected $creation_service_manager;

    /**
     * A list of aliases
     *
     * @var Config_Interface
     */
    protected $aliases;

    /**
     * A list of factories
     *
     * @var Config_Interface
     */
    protected $factories;

    /**
     * Enable/disable sharing service instance
     *
     * @var Config_Interface
     */
    protected $shared;

    /**
     * Flag specifying whether services should be shared by default
     *
     * @var bool
     */
    protected $shared_by_default = true;

    /**
     * Constructor
     *
     * @param  Config_Interface $config
     * @param  string $allow_override can_override || can_not_override
     * @param  Service_Manager_Interface $creation_service_manager
     * @return void
     */
    public function __construct( Config_Interface $config = null, string $allow_override = 'can_not_override', Service_Manager_Interface $creation_service_manager = null ) {
        $this->allow_override = $allow_override;
        $this->creation_service_manager = $creation_service_manager instanceof Service_Manager_Interface ? $creation_service_manager : $this;

        $this->aliases = new Config( [], 'read_and_write' );
        $this->factories = new Config( [], 'read_and_write' );
        $this->shared = new Config( [], 'read_and_write' );

        if ( $config instanceof Config_Interface ) {
            if ( isset( $config->aliases ) ) {
                $this->aliases->merge( $config->aliases );
            }

            if ( isset( $config->factories ) ) {
                $this->factories->merge( $config->factories );
            }

            if ( isset( $config->shared ) ) {
                $this->shared->merge( $config->shared );
            }

            if ( isset( $config->shared_by_default ) ) {
                $this->shared_by_default = $config->shared_by_default;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function build( string $service_name, array $options = null ) {
        $service_name = isset( $this->aliases->$service_name ) ? $this->aliases->$service_name : $service_name;
        return $this->create_service_object( $service_name, $options );
    }

    /**
     * @inheritDoc
     */
    public function get( string $service_name ) {
        $requested_service_name = $service_name;

        if ( $this->has( $service_name ) ) {
            return $this->services[$service_name];
        }

        $service_name = isset( $this->aliases->$service_name ) ? $this->aliases->$service_name : $service_name;

        if (
            $service_name !== $requested_service_name &&
            ( ! isset( $this->shared->$requested_service_name ) || true === $this->shared->$requested_service_name ) &&
            $this->has( $service_name )
        ) {
            $this->services[$requested_service_name] = $this->services[$service_name];
            return $this->services[$service_name];
        }

        $service = $this->create_service_object( $service_name );

        $this->save_shared_service( $service_name, $service );

        if ( $service_name !== $requested_service_name ) {
            $this->save_shared_service( $requested_service_name, $service );
        }

        return $service;
    }

    /**
     * @inheritDoc
     * @throws ServiceNotFoundException if the allow override is not possible and exist service instance
     */
    public function set( string $service_name, $service ) {
        if ( ! $this->can_override() && $this->has( $service_name ) ) {
            throw new Service_Manager_Modifications_Not_Allowed_Exception( sprintf(
                'An updated %s is not allowed, service is already exist in service manager and service manager does not allow changes for existing instances of services',
                $service_name
            ) );
        }

        $this->services[$service_name] = $service;
    }

    /**
     * @inheritDoc
     */
    public function has( string $service_name ) {
        return isset( $this->services[$service_name] );
    }

    /**
     * Add/update alias
     *
     * Update only when function can_override() return true
     *
     * @param  string $alias
     * @param  string $alias_target
     * @return void
     * @throws ServiceNotFoundException if the allow override is not possible and exist alias
     */
    public function set_alias( string $alias, string $alias_target ) {
        if ( ! $this->can_override() && isset( $this->aliases->$alias ) ) {
            throw new Service_Manager_Modifications_Not_Allowed_Exception( sprintf(
                'An updated %s is not allowed, alias is already exist in service manager and service manager does not allow changes for existing aliases',
                $alias
            ) );
        }

        $this->aliases->$alias = $alias_target;
    }

    /**
     * Add/update factory
     *
     * Update only when function can_override() return true
     *
     * @param  string $service_name
     * @param  string|Factory_Interface $factory
     * @return void
     * @throws ServiceNotFoundException if the allow override is not possible and exist factory for service
     */
    public function set_factory( string $service_name, $factory ) {
        if ( ! $this->can_override() && isset( $this->factories->$service_name ) ) {
            throw new Service_Manager_Modifications_Not_Allowed_Exception( sprintf(
                'An updated %s is not allowed, factory for service is already exist in service manager and service manager does not allow changes for existing factories',
                $service_name
            ) );
        }

        $this->factories->$service_name = $factory;
    }

    /**
     * Add/update shared
     *
     * Update only when function can_override() return true
     *
     * @param  string $service_name
     * @param  bool $flag
     * @return void
     * @throws ServiceNotFoundException if the allow override is not possible and exist shared flag for service
     */
    public function set_shared( string $service_name, bool $flag ) {
        if ( ! $this->can_override() && isset( $this->shared->$service_name ) ) {
            throw new Service_Manager_Modifications_Not_Allowed_Exception( sprintf(
                'An updated %s is not allowed, shared flag for service is already exist in service manager and service manager does not allow changes for existing shared flags',
                $service_name
            ) );
        }

        $this->shared->$service_name = $flag;
    }

    /**
     * Return information whether modifications to data are allowed
     *
     * @return boolean
     */
    public function can_override(): bool {
        return $this->allow_override === 'can_override' ? true : false;
    }

    /**
     * Change allow override services for this instance to can_not_override
     *
     * @return void
     */
    public function forbid_override() {
        $this->allow_override = 'can_not_override';
    }

    /**
     * Create a new service instance
     *
     * @param  string $service_name
     * @param  null|array $options
     * @return mixed
     */
    private function create_service_object( string $service_name, array $options = null ) {
        $factory = $this->get_service_factory( $service_name );
        return $factory( $this->creation_service_manager, $service_name, $options );
    }

    /**
     * Save service in manager when service can be shared
     *
     * @param  string $service_name
     * @param  mixed $service
     * @return void
     */
    private function save_shared_service( string $service_name, $service ) {
        if (
            ( true === $this->shared_by_default && ! isset( $this->shared->$service_name ) ) ||
            ( isset( $this->shared->$service_name ) && true === $this->shared->$service_name )
        ) {
            $this->services[$service_name] = $service;
        }
    }

    /**
     * Get a factory for service
     *
     * @param  string $service_name
     * @return Factory_Interface
     */
    private function get_service_factory( string $service_name ) {
        $factory = isset( $this->factories->$service_name ) ? $this->factories->$service_name : null;

        if ( null !== $factory) {
            if ( is_string( $factory ) && class_exists( $factory ) ) {
                $factory = new $factory();
                $this->factories->$service_name = $factory;
            }

            if ( $factory instanceof Factory_Interface ) {
                return $factory;
            }
        }

        $factory = new Default_Factory();
        $this->factories->$service_name = $factory;
        return $factory;
    }
}
