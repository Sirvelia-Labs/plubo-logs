<?php

if ( !defined( 'PLUBO_LOGS_DIR_URL' ) ) {
    define( 'PLUBO_LOGS_DIR_URL', plugin_dir_url( __FILE__ ) );
}

if ( !defined( 'PLUBO_LOGS_ASSETS_URL' ) ) {
    define( 'PLUBO_LOGS_ASSETS_URL', PLUBO_LOGS_DIR_URL . 'dist/' );
}