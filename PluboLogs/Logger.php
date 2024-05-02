<?php

namespace PluboLogs;

class Logger
{
    /**
     * The logger instance.
     * 
     * @var Logger|null
     */
    private static $instance = null;

    /**
     * Constructor.
     * 
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks with WordPress
     */
    private function init_hooks()
    {
        add_action( 'init', [ Database::class, 'setup_db' ] );
        add_action( 'admin_menu', [ AdminMenu::class, 'add_logs_submenu' ] );
        add_action( 'admin_enqueue_scripts', [ AdminMenu::class, 'enqueue_log_scripts' ] );
        add_action( 'rest_api_init', [ Endpoints::class, 'register_log_endpoints' ] );
    }

    /**
     * Clone not allowed.
     */
    private function __clone() {}

    /**
     * Prepares the log DB Table and the required configuration
     */
    public static function init()
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        // Custom action for checker initialization
        do_action( 'plubo/logger_init' );

        return self::$instance;
    }

    /**
     * Private function to get the service slug from the backtrace
     * 
     * @var array $backtrace The backtrace of the log
     * 
     * @return string The service slug
     */
    private static function get_service_slug( array $backtrace )
    {
        $backtrace  = reset( $backtrace );

        $file_path  = $backtrace['file'] ?? false;
        if ( !$file_path ) return 'undefined';

        $pos        = strpos( $file_path, 'plugins/' ) + strlen( 'plugins/' );
        $sub_path   = substr( $file_path, $pos );
        $next_slash = strpos( $sub_path, '/' );
        $slug       = substr( $sub_path, 0, $next_slash );

        return $slug;
    }

    /**
     * Private function to insert a new log into the DB
     * 
     * @var string $content The content to log
     * @var string $status The status of the log
     * @var array $backtrace The backtrace of the log
     * @var string $service The service associated with the log
     */
    private static function insert_log( string $content, string $status, array $backtrace, string $service = null )
    {
        $new_log = new Log( [
            'date'      => current_time( 'mysql' ),
            'service'   => $service ?: self::get_service_slug( $backtrace ),
            'content'   => $content,
            'status'    => $status,
            'backtrace' => json_encode( $backtrace )
        ] );
        $new_log->store();
    }

    /**
     * Creates a log with the given message on the Plugin Logs settings submenu
     * 
     * @var string $content The logged message
     * @var null|string $service Identifies the log service. If set to null, the plugin slug will be used
     */
    public static function log( string $content, $service = null )
    {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
        self::insert_log( $content, Log::$STATUS_LOG, $backtrace, $service );
        do_action( 'plubo/log_info' );
    }

    /**
     * Creates an alert with the given message on the Plugin Logs settings submenu
     * 
     * @var string $content The logged message
     * @var null|string $service Identifies the alert service. If set to null, the plugin slug will be used
     */
    public static function alert( string $content, $service = null )
    {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
        self::insert_log( $content, Log::$STATUS_WARNING, $backtrace, $service );
        do_action( 'plubo/log_alert' );
    }

    /**
     * Creates an error with the given message on the Plugin Logs settings submenu
     * 
     * @var string $content The logged message
     * @var null|string $service Identifies the error service. If set to null, the plugin slug will be used
     */
    public static function error( string $content, $service = null )
    {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
        self::insert_log( $content, Log::$STATUS_ERROR, $backtrace, $service );
        do_action( 'plubo/log_error' );
    }
}