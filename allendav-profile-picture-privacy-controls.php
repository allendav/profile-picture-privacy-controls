<?php
/*
Plugin Name: Profile Picture Privacy
Plugin URI: http://www.allendav.com/
Description: Gives users control over whether or not to opt-in to Gravatar. Avoids revealing Gravatars to logged-out visitors.
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
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'pre_get_avatar_data', array( $this, 'pre_get_avatar_data' ), 10, 2 );
		add_filter( 'user_profile_picture_description', array( $this, 'user_profile_picture_description' ), 10, 2 );
		add_action( 'personal_options_update', array( $this, 'personal_options_update' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	}

	function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			__( 'By default, we do not send your email address to the
			Gravatar service, nor enable Gravatar powered profile
			pictures for any user. Registered users may enable
			their Gravatar by editing their
			<a href="%s" target="_blank">profile</a>.

			To further protect your privacy, even if you enable your
			Gravatar on your profile, it will only be shared with
			logged-in users of the site. Search engines, visitors,
			and logged-out users will not be able to see Gravatars
			for any user on this site.
			',
			'allendav-profile-picture-privacy' ),
			admin_url( 'profile.php#profile-picture-privacy' )
		);

		wp_add_privacy_policy_content(
			'Profile Picture Privacy',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	function is_user_using_gravatar( $user_id ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		$using_gravatar = get_user_meta( $user_id, 'ppp_using_gravatar', true );
		return ! empty( $using_gravatar );
	}

	function get_user_id_from_argument( $argument ) {
		// Is the argument a user ID?
		if ( ! is_object( $argument ) ) {
			$user = get_user_by( 'ID', $argument );
			if ( ! empty( $user ) ) {
				return $user->ID;
			}

			// Is the argument an email? Does it resolve to a user ID?
			if ( is_email( $argument ) ) {
				$user = get_user_by( 'email', $argument );
				if ( ! empty( $user ) ) {
					return (int) $user->ID;
				}
			}

			return false;
		}

		// Is the argument a WP_User object?
		if ( is_a( $argument, 'WP_User' ) ) {
			return (int) $argument->ID;
		}

		// Is the argument a WP_Post object?
		if ( is_a( $argument, 'WP_Post' ) ) {
			return (int) $argument->post_author;
		}

		// Is the argument a WP_Comment object?
		if ( isset( $argument->comment_ID ) ) {
			$comment = get_comment( $argument->comment_ID );
			if ( ! is_null( $comment ) ) {
				$user_ID = (int) $comment->user_id;
				if ( 0 < $user_ID ) {
					return $user_ID;
				}
			}
		}

		return false;
	}

	function pre_get_avatar_data( $args, $argument ) {
		$show_gravatar = false;

		// We only show gravatars to logged in users.
		// No need to test the argument if no one is logged in.
		if ( is_user_logged_in() ) {
			$user_id = $this->get_user_id_from_argument( $argument );
			if ( $user_id ) {
				$show_gravatar = $this->is_user_using_gravatar( $user_id);
			}
		}

		if ( ! $show_gravatar ) {
			$args[ 'url' ] = plugins_url( 'images/mystery.png', __FILE__ );
		}

		return $args;
	}

	function user_profile_picture_description( $description, $profile_user ) {
		if ( ! defined( 'IS_PROFILE_PAGE' ) || ! IS_PROFILE_PAGE ) {
			return $description;
		}

		$use_gravatar_data = $this->is_user_using_gravatar( $profile_user->ID ) ? 'yes' : 'no';
		return '<span class="profile-picture-privacy-controls" data-use-gravatar="' . esc_attr( $use_gravatar_data ) . '" />';
	}

	function admin_footer() {
		if ( ! defined( 'IS_PROFILE_PAGE' ) || ! IS_PROFILE_PAGE ) {
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
			__( 'A weakly encoded version of your email address will be provided to the <a href="%s">Gravatar</a> service and will appear in the code for pages on this site to any logged-in user.', 'allendav-profile-picture-privacy' ),
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
		if ( ! defined( 'IS_PROFILE_PAGE' ) || ! IS_PROFILE_PAGE ) {
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
