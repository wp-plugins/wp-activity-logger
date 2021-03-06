<?php
/**
 * @package WPActivityLogger
 * @version 1.0.0
 */
/*
Plugin Name: WP Activity Logger
Plugin URI: http://allanbruford.com/wp-activity-logger
Description: A plugin for logging various WordPress activity and errors
Author: Allan Bruford
Version: 1.0.0
Author URI: http://allanbruford.com
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl.html

Copyright (c) 2013 Quicken Loans

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class WPActivityLogger {
    const PLUGIN_SLUG = 'WPActivityLogger';

    protected $site;
    protected $email;

    public function __construct() {
        $this->site = get_bloginfo();
        $this->email = ( get_option( self::PLUGIN_SLUG . '_email' ) ? get_option( self::PLUGIN_SLUG . '_email' ) : get_option('admin_email') );

        /* Admin Actions */
        add_filter( 'admin_menu', array( $this, 'admin_actions' ) );

        /* Plugin Monitoring */
        add_action( 'activated_plugin', array( $this, 'log_activation' ) );
        add_action( 'deactivated_plugin', array( $this, 'log_deactivation' ) );

        /* Login Monitoring */
        // @TODO: monitor logins
        //add_action( 'wp_login_failed', array( $this, 'log_badLogin' ) );
        //add_action( 'wp_login', array( $this, 'log_login' ) );

        /* Error Monitoring */
        register_shutdown_function( array( $this, 'log_error') );
    }

    /* Plugins */
    public function log_activation( $plugin, $network_wide ) {
        $this->log_plugin( 'activated', $plugin );
    }

    public function log_deactivation( $plugin, $network_wide ) {
        $this->log_plugin( 'deactivated', $plugin );
    }

    /* Authentication */
    public function log_badLogin( $username ) {
    }

    public function log_login( $username ) {
    }

    public function admin_actions() {
        add_options_page(
            'WP Activity Logger',
            'WP Activity Logger',
            'manage_options',
            'WPActivityLogger',
            array( $this, 'settings_page')
        );

        register_setting( self::PLUGIN_SLUG, 'settings', array( $this, 'settings_callback' ) );
        add_settings_section( 'settings', 'Settings', array( $this, 'section_info' ), self::PLUGIN_SLUG );
        add_settings_field( 'email', 'Email Address', array( $this, 'create_email_address' ), self::PLUGIN_SLUG, 'settings' );
    }

    public function create_email_address() {
        ?><input type="text" class="regular-text" id="email" name="settings[email]" value="<?php echo get_option( self::PLUGIN_SLUG . '_email' );?>" /><?php
    }

    public function settings_callback( $input ) {
        $valid = array();
        if ( is_email( $input['email'] ) ) {
            $val = $input['email'];
            $this->save_option( self::PLUGIN_SLUG . '_email', $val );
            $valid['email'] = $val;
        } else {
            if ( $input['email'] == '' ) {
                $val = '';
                $this->save_option( self::PLUGIN_SLUG . '_email', $val );
                $valid['email'] = $val;
            }
        }
        return $valid;
    }

    public function save_option( $key, $val ) {
        if ( get_option( $key ) === FALSE ) {
            add_option( $key, $val );
        } else {
            update_option( $key, $val );
        }
    }

    public function settings_page() {
        include 'templates/admin_page.html.php';
    }

    /**
     * Error Reporting
     *
     * 1 E_ERROR (integer)
     * Fatal run-time errors. These indicate errors that can not be recovered 
     * from, such as a memory allocation problem. Execution of the script is 
     * halted.
     *  
     * 4 E_PARSE (integer)
     * Compile-time parse errors. Parse errors should only be generated by the 
     * parser.
     *  
     * 16 E_CORE_ERROR (integer)
     * Fatal errors that occur during PHP's initial startup. This is like an 
     * E_ERROR, except it is generated by the core of PHP.
     * 
     */
    public function log_error() {
        $error = error_get_last();
        $errorValuesToLog = array( 1, 4, 16 );
        if ( in_array( $error['type'], $errorValuesToLog ) ) {

            $subject = $this->site . ' - Fatal Error';

            $message = $error['message']; 
            $message .= "\n\n";
            $message .= 'On line: ' . $error['line'] . ' of ' . $error['file'];

            wp_mail( $this->email, $subject, $message );
        }
    }

    /* Plugin Logging Function */
    protected function log_plugin( $type, $plugin ) {
        global $current_user;

        $subject = $this->site . ' - Plugin ' . ucfirst( $type );
        $message = $current_user->user_login . ' has ' . $type . ' the following plugin: ' . $plugin;

        wp_mail( $this->email, $subject, $message );
    }

}

$wpal = new WPActivityLogger();
