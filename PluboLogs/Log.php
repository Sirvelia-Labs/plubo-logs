<?php

namespace PluboLogs;

// Log Class
class Log
{
    /**
     * Log Class Constants
     */
    private static $TABLE_NAME      = 'plubo_logs';

    public static $STATUS_LOG       = 'log';
    public static $STATUS_WARNING   = 'warning';
    public static $STATUS_ERROR     = 'error';

    /**
     * Log Entity Attributes
     */
    public $id;
    public $date;
    public $service;
    public $content;
    public $status;
    public $backtrace;

    /**
     * Private Method to return all log properties in an array
     */
    private function get_properties()
    {
        return [
            'date'      => $this->date,
            'service'   => $this->service,
            'content'   => $this->content,
            'status'    => $this->status,
            'backtrace' => $this->backtrace
        ];
    }

    /**
     * Construct Method
     */
    public function __construct( $fields )
    {
        foreach( $fields as $field => $value ) {
            $this->{$field} = $value;
        }
    }

    /**
     * Syncs the initialized log with its DB information
     */
    public function sync()
    {
        global $wpdb;

        $db_table_name  = $wpdb->prefix . self::$TABLE_NAME;
        $log_fields     = $wpdb->get_row( "SELECT * FROM $db_table_name WHERE id = {$this->id}" );
        
        if ( !$log_fields ) return false;
        foreach( $log_fields as $field => $value ) {
            $this->{$field} = $value;
        }

        return true;
    }

    /**
     * Stores the instantiated log into the DB
     */
    public function store()
    {
        global $wpdb;

        $db_table_name  = $wpdb->prefix . self::$TABLE_NAME;
        if ( $this->id ) {
            $wpdb->update( $db_table_name, $this->get_properties(), [ 'id' => $this->id ] );
            return $this->id;
        }

        $insert_result = $wpdb->insert( $db_table_name, $this->get_properties() );        
        return $insert_result ? $wpdb->insert_id : 0;
    }
}