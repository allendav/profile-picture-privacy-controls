<?php
/*
Plugin Name: Profile Picture Privacy Controls by Allendav
Plugin URI: http://www.allendav.com/
Description: Allow users to control whether or not the Gravatar service is contacted for their profile picture. Requires WordPress 4.7 or higher.
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
		add_filter( 'user_profile_picture_description', array( $this, 'user_profile_picture_description' ), 10, 2 );
		add_action( 'personal_options_update', array( $this, 'personal_options_update' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	}

	function is_user_using_gravatar( $user_id ) {
		return get_user_meta( $user_id, 'ppp_using_gravatar', true );
	}

	function pre_get_avatar_data( $args, $id_or_email ) {
		$user = get_user_by( 'ID', $id_or_email );
		if ( ! empty( $user ) ) {
			if ( $this->is_user_using_gravatar( $user->ID ) ) {
				return $args;
			}
		}

		// TODO - allow unregistered users to opt-in to using their gravatar as well
		// for now, they get mystery

		$args[ 'url' ] = plugins_url( 'images/mystery.png', __FILE__ );
		return $args;
	}

	function user_profile_picture_description( $description, $profile_user ) {
		if ( ! IS_PROFILE_PAGE ) {
			return $description;
		}

		$use_gravatar_data = $this->is_user_using_gravatar( $profile_user->ID ) ? 'yes' : 'no';
		return '<span class="profile-picture-privacy-controls" data-use-gravatar="' . esc_attr( $use_gravatar_data ) . '" />';
	}

	function admin_footer() {
		if ( ! IS_PROFILE_PAGE ) {
			return;
		}

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$use_gravatar = $this->is_user_using_gravatar( $user_id );

		$allowed_html = array(
			'a' => array(
				'href' => array(),
			),
			'em' => array(),
			'strong' => array(),
		);

		$allow_label = __( 'Use the Gravatar service for my profile picture.', 'allendav-profile-picture-privacy' );
		$allow_descr = sprintf(
			__( 'A hashed version of your email address will be provided to the <a href="%s">Gravatar</a> service and will appear in the code for pages on this site. This means that people could use search engines and other tools to find your comments and articles on other websites using just that information.', 'allendav-profile-picture-privacy' ),
			'https://en.gravatar.com'
		);

		$deny_label = __( 'Use a blank profile picture.', 'allendav-profile-picture-privacy' );
		$deny_descr = __( 'This makes it harder for people to track your activities across the web.', 'allendav-profile-picture-privacy' );

		$allow_checked = $use_gravatar ? 'checked="checked"' : '';
		$deny_checked = $use_gravatar ? '' : 'checked="checked"';

		?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					var allowGravatarLabel = '<?php echo wp_kses( $allow_label, $allowed_html ); ?>';
					var allowGravatarDesc = '<?php echo wp_kses( $allow_descr, $allowed_html ); ?>';
					var denyGravatarLabel = '<?php echo wp_kses( $deny_label, $allowed_html ); ?>';
					var denyGravatarDesc = '<?php echo wp_kses( $deny_descr, $allowed_html ); ?>';
					var radioButtonMarkup = '<fieldset id="profile-picture-privacy">' +
						'<label>' +
							'<input name="ppp_using_gravatar" value="yes" id="ppp_using_gravatar_yes" type="radio" <?php echo $allow_checked; ?> />' +
							'<span>' +
								allowGravatarLabel +
							'</span>' +
						'</label>' +
						'<p class="description">' +
							allowGravatarDesc +
						'</p>' +
						'<br/>' +
						'<label>' +
							'<input name="ppp_using_gravatar" value="no" id="ppp_using_gravatar_no" type="radio" <?php echo $deny_checked; ?> />' +
							'<span>' +
								denyGravatarLabel +
							'</span>' +
						'</label>' +
						'<p class="description">' +
							denyGravatarDesc +
						'</p>' +
					'</fieldset>';
					$( '.profile-picture-privacy-controls' ).parents( 'td' ).append( radioButtonMarkup );
				} );
			</script>
		<?php
	}

	function personal_options_update( $user_id ) {
		if ( ! IS_PROFILE_PAGE ) {
			return;
		}

		if ( ! isset( $_POST[ 'ppp_using_gravatar' ] ) ) {
			return;
		}

		$use_gravatar = 'yes' === $_POST[ 'ppp_using_gravatar' ];

		update_user_meta( $user_id, 'ppp_using_gravatar', $use_gravatar );
	}
}

Allendav_Profile_Picture_Privacy::getInstance();
