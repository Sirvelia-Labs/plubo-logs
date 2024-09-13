<?php

namespace PluboLogs;

class Database
{
    private static $TABLE_NAME  = 'plubo_logs';
    private static $DB_VERSION  = '0.0.1';

    private static $PER_PAGE    = 25;

    /**
     * Function to set up the logger db
     */
    public static function setup_db()
    {
        global $wpdb;

        $db_version         = get_option( 'plubo_logs_db_version', false );

        if ( !$db_version )
        {
            $db_table_name      = $wpdb->prefix . self::$TABLE_NAME;
            $charset_collate    = $wpdb->get_charset_collate();
            $sql                = "CREATE TABLE `$db_table_name` (
                `id` bigint(20) NOT NULL auto_increment,
                `date` datetime,
                `service` varchar(500),
                `content` longtext,
                `status` varchar(20),
                `backtrace` longtext,
                UNIQUE KEY id (id)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            add_option( 'plubo_logs_db_version', self::$DB_VERSION );
        }
    }

    /**
     * Private function to get the SQL statement for date filtering
     */
    private static function get_date_filter( $date )
    {
        switch( $date )
        {
            case 'last_hour':
                return "TIMESTAMPDIFF(SECOND, date, NOW()) < 3600";

            case 'today':
                return "TIMESTAMPDIFF(HOUR, date, NOW()) < 24";

            case 'this_week':
                return "TIMESTAMPDIFF(DAY, date, NOW()) < 7";

            case '':
                return '';

            default:
                return "DATE(date) = '$date'";
        }
    }

    /**
     * Gets the logs from the database
     */
    public static function get_logs( $page, $service = '', $status = '', $date = '' )
    {
        global $wpdb;

        $db_table_name  = $wpdb->prefix . self::$TABLE_NAME;
        
        $filters        = [];
        if ( $service && $service !== 'all' && is_array( $service ) ) {
            $filters[] = "service IN (" . implode( ', ', array_map( function( $service ) { return "'$service'"; }, $service ) ) . ")";
        }
        if ( $status && is_array( $status ) ) {
            $filters[] = "status IN (" . implode( ', ', array_map( function( $status ) { return "'$status'"; }, $status ) ) . ")";
        }
        if ( $date )    $filters[] = self::get_date_filter( $date );
        $filters        = implode( ' AND ', $filters );

        $page_offset    = self::$PER_PAGE * $page;
        $sql            = "SELECT * FROM $db_table_name" . ( $filters ? " WHERE $filters" : '' ) . " ORDER BY date DESC LIMIT " . self::$PER_PAGE . " OFFSET $page_offset";

        return $wpdb->get_results( $sql, ARRAY_A );
    }

}