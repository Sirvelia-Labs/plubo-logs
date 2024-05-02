<?php

namespace PluboLogs;

class Endpoints
{
    public static $NAMESPACE = 'plubo-logs/v1';

    /**
     * Checks if the given rest route namespace already exists
     * 
     * @var string $namespace The namespace being checked
     * 
     * @return boolean true if the namespace exists, false otherwise
     */
    private static function route_namespace_exists( string $namespace )
    {
        $namespace_routes = rest_get_server()->get_routes( $namespace );

        return !empty( $namespace_routes );
    }

    /**
     * Adds the needed endpoints for the log admin panel
     */
    public static function register_log_endpoints()
    {
        if ( self::route_namespace_exists( self::$NAMESPACE ) ) return;

        register_rest_route( self::$NAMESPACE, '/get_logs', [ 'method' => 'GET', 'callback' => [ self::class, 'get_logs' ] ] );
    }

    /**
     * Gets the saved logs
     */
    public static function get_logs( $request )
    {
        $params     = $request->get_params();
        
        $page       = sanitize_text_field( $params['page'] ?? '0' );
        $service    = is_array( $params['service'] ) ? rest_sanitize_array( $params['service'] ) : sanitize_text_field( $params['service'] ?? '' );
        $status     = is_array( $params['status'] ) ? rest_sanitize_array( $params['status'] ) : sanitize_text_field( $params['status'] ?? '' );
        $date       = sanitize_text_field( $params['date'] ?? '' );

        return Database::get_logs( $page, $service, $status, $date );
    }
}