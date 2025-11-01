<?php
/**
 * Logs the last PHP error for AJAX requests to wp-content/ajax-last-error.log
 */
if ( defined('DOING_AJAX') && DOING_AJAX ) {
    register_shutdown_function( function () {
        $e = error_get_last();
        if ( $e && in_array( $e['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ] , true ) ) {
            $log = sprintf(
                "[%s] %s in %s on line %d\nREQUEST: %s\n",
                gmdate('c'),
                $e['message'],
                $e['file'],
                $e['line'],
                isset($_REQUEST['action']) ? $_REQUEST['action'] : '(no action)'
            );
            @file_put_contents( WP_CONTENT_DIR . '/ajax-last-error.log', $log, FILE_APPEND );
        }
    } );
}
