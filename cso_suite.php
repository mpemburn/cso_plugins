<?php
/*
 * @wordpress-plugin
 * Plugin Name: CSO Plugin Suite
 * Description: A suite of integrated WordPress plugins for the Chesapeake Spokes Bike Club
 * Version: 1.0 Alpha
 * Author: Mark Pemburn
 * Author URI: http://www.pemburnia.com/
*/

include 'admin_settings.php';
include 'roster_api.php';
include 'elections.php';
include 'join_cso.php';
include 'ride_leader.php';
include 'sms_switchboard.php';

class CsoSuite
{
    public static function register()
    {
        $instance = new self;

        if (is_admin()) {
            new \CsoSuiteAdminSettings();
        }

        $instance->enqueueAssets();

        CsoElections::register();
        JoinCso::register();
        //RideLeader::register();
        SmsSwitchboard::register();
    }

    private function __construct()
    {
    }

    /**
     *
     */
    public function enqueueAssets()
    {
        $version = '1.2';
        wp_enqueue_style( 'jquery-ui'. 'http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css' );
        wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');

        wp_enqueue_script('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script( 'jquery-ui-dialog' );
    }
}
// Load as singleton to add actions and enqueue assets
CsoSuite::register();
