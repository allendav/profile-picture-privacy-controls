<?php
/*
Plugin Name: Profile Picture Privacy Controls by Allendav
Plugin URI: http://www.allendav.com/
Description: Allow users to control whether or not the Gravatar service is contacted for their profile picture.
Version: 1.0.0
Author: allendav
Author URI: http://www.allendav.com
License: GPL2
*/

class Allendav_Profile_Picture_Privacy {
	private static $instance;

	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {
	}

	private function __wakeup() {
	}

	protected function __construct() {
		add_filter( 'pre_get_avatar_data', array( $this, 'pre_get_avatar_data' ), 10, 2 );
	}

	function pre_get_avatar_data( $args, $id_or_email ) {
		// Default to using anonymous profile picture
		// Registered user? Check user meta for ppp_using_gravatar (true/false)
		// Email address only? Check ppp_emails_using_gravatar option (array) for this email address
		$args[ 'url' ] = plugins_url( 'images/mystery.png', __FILE__ );
		return $args;
	}

	// add a setting in usermeta for ppp_using_gravatar (bool)

	// add a field on the no-priv comment for for ppp_emails_using_gravatar

	// handle comment save form
}

Allendav_Profile_Picture_Privacy::getInstance();
