<?php
/*
Plugin Name: Simple Secure Contact Form
Plugin URI: http://lp-tricks.com/
Description: Simple Secure Contact Form display contact form widget in your widget area with "invisible" spam protection. No more spam, no more capcha!
Tags: widget, contact, contacts, contact form, contact form 7, email, form, mail, spam, antispam, multilingual, plugin
Version: 0.2.3
Author: Eugene Holin
Author URI: http://lp-tricks.com/
License: GPLv2 or later
Text Domain: lptw_contact_form_domain
*/

/**
 * Load plugin textdomain.
 */
add_action( 'plugins_loaded', 'lptw_contact_form_load_textdomain' );
function lptw_contact_form_load_textdomain() {
	load_plugin_textdomain( 'lptw_contact_form_domain', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/* load js and css styles */
add_action( 'wp_enqueue_scripts', 'lptw_contact_form_register_scripts' );
function lptw_contact_form_register_scripts() {
	wp_register_style( 'lptw-contact-form-style', plugins_url( 'css/simple-secure-contact-form.css', __FILE__ ) );
	wp_enqueue_style( 'lptw-contact-form-style' );

	wp_register_style( 'font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'font-awesome' );

	wp_enqueue_script( 'autosize', plugins_url( 'js/autosize.js', __FILE__ ), array(), FALSE, TRUE );

	wp_enqueue_script( 'maskedinput', plugins_url( 'js/jquery.maskedinput.min.js', __FILE__ ), array(), FALSE, TRUE );

	wp_register_script( 'lptw-contact-form-script', plugins_url( 'js/simple-secure-contact-form.js', __FILE__ ), array( 'jquery' ), FALSE, TRUE );
	wp_localize_script( 'lptw-contact-form-script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'lptw-contact-form-script' );
}

/* load js and css styles in admin area */
add_action( 'admin_enqueue_scripts', 'lptw_contact_form_register_scripts_admin' );
function lptw_contact_form_register_scripts_admin() {
	wp_register_style( 'font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'font-awesome' );
}

// Creating the widget with contact form
class lptw_contact_form_widget extends WP_Widget {

	function __construct() {

		$widget_ops = array(
			'classname' => 'lptw_contact_form_widget',
			'description' => __( "Simple and secure contact form with invisible spam protection.", 'lptw_contact_form_domain' ),
		);
		parent::__construct( 'lptw-contact-form-widget', __( 'Simple Secure Contact Form', 'lptw_contact_form_domain' ), $widget_ops );
		$this->alt_option_name = 'lptw_contact_form_widget_options';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {
		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'lptw_contact_form_widget', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];

			return;
		}

		ob_start();

		$show_widget_title = isset( $instance['show_widget_title'] ) ? $instance['show_widget_title'] : TRUE;

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Contact Form', 'lptw_contact_form_domain' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$widget_description = apply_filters( 'widget_text', empty( $instance['widget_description'] ) ? '' : $instance['widget_description'], $instance );

		if ( isset( $instance['color_scheme'] ) ) {
			$color_scheme = $instance['color_scheme'];
		} else {
			$color_scheme = 'red';
		}

		$use_wp_admin_email = isset( $instance['use_wp_admin_email'] ) ? $instance['use_wp_admin_email'] : FALSE;

		if ( isset( $instance['custom_email'] ) ) {
			$custom_email = sanitize_email( $instance['custom_email'] );
			$custom_email = base64_encode( $custom_email );
		}
		if ( isset( $instance['bcc_email'] ) ) {
			$bcc_email = sanitize_email( $instance['bcc_email'] );
			$bcc_email = base64_encode( $bcc_email );
		}

		if ( $use_wp_admin_email == TRUE ) {
			$admin_email = base64_encode( get_bloginfo( 'admin_email' ) );
		} else {
			$admin_email = $custom_email;
		}

		$show_name_input = isset( $instance['show_name_input'] ) ? $instance['show_name_input'] : FALSE;
		$show_phone_input = isset( $instance['show_phone_input'] ) ? $instance['show_phone_input'] : FALSE;
		$show_email_input = isset( $instance['show_email_input'] ) ? $instance['show_email_input'] : FALSE;
		$show_message_textarea = isset( $instance['show_message_textarea'] ) ? $instance['show_message_textarea'] : FALSE;
		$button_name = $instance['button_name'];
		$message_subject = $instance['message_subject'];
		$phone_mask = $instance['phone_mask'];
		$message_size = isset( $instance['message_size'] ) ? $instance['message_size'] : 'normal';
		$ga_eventCategory = isset( $instance['ga_eventCategory'] ) ? esc_attr( $instance['ga_eventCategory'] ) : '';
		$ga_eventAction = isset( $instance['ga_eventAction'] ) ? esc_attr( $instance['ga_eventAction'] ) : '';
		$ga_eventLabel = isset( $instance['ga_eventLabel'] ) ? esc_attr( $instance['ga_eventLabel'] ) : '';
		$ym_counterID = isset( $instance['ym_counterID'] ) ? esc_attr( $instance['ym_counterID'] ) : '';
		$ym_targetName = isset( $instance['ym_targetName'] ) ? esc_attr( $instance['ym_targetName'] ) : '';

		$label_icon_color = 'label-icon-' . $color_scheme;
		$textarea_label_color = 'textarea-label-' . $color_scheme;
		$input_label_color = 'input-label-' . $color_scheme;
		$input_wrapper_color = 'input-wrapper-' . $color_scheme;
		$textarea_wrapper_color = 'textarea-wrapper-' . $color_scheme;
		$button_color = 'lptw-button-' . $color_scheme;
		$round_color = 'lptw-round-' . $color_scheme;

		?>


		<?php echo $args['before_widget']; ?>
		<?php if ( $title && $show_widget_title == TRUE ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
		<div class="form-wrapper" id="formwr-<?php echo rand( 1000, 9999 ); ?>" data-eventcategory="<?php echo esc_attr( $ga_eventCategory ); ?>" data-eventaction="<?php echo esc_attr( $ga_eventAction ); ?>" data-eventlabel="<?php esc_attr( $ga_eventLabel ); ?>" data-counterid="<?php echo esc_attr( $ym_counterID ); ?>" data-targetname="<?php echo esc_attr( $ym_targetName ); ?>">
			<?php if ( $widget_description != '' ) : ?>
				<p class="form-description"><?php echo ! empty( $instance['filter'] ) ? wpautop( $widget_description ) : $widget_description; ?></p>
			<?php endif; ?>
			<form id="lptw-contact-form" id="form-<?php echo rand( 1000, 9999 ); ?>">
				<input type="email" name="email">
				<input type="hidden" name="admin_email" value="<?php echo $admin_email; ?>">
				<input type="hidden" name="bcc_email" value="<?php echo $bcc_email; ?>">
				<input type="hidden" name="message_subject" value="<?php echo esc_attr( $message_subject ); ?>">
				<?php wp_nonce_field( 'lptw-send-form-data' ); ?>
				<?php if ( $show_name_input == TRUE ) : ?>
					<div class="input-wrapper <?php echo $input_wrapper_color; ?>">
						<input type="text" name="your-name" id="your-name" class="input-field" placeholder="<?php _ex( 'Your name...', 'input placeholder', 'lptw_contact_form_domain' ); ?>" required>
						<label for="your-name" class="input-label <?php echo $input_label_color; ?>" title="<?php _ex( 'Your name...', 'label title', 'lptw_contact_form_domain' ); ?>"><i class="fa fa-user"></i></label>
					</div>
				<?php endif; ?>
				<?php if ( $show_phone_input == TRUE ) : ?>
					<div class="input-wrapper <?php echo $input_wrapper_color; ?>">
						<input type="text" name="your-phone" id="your-phone" class="input-field your-phone-input" placeholder="<?php _ex( 'Your phone...', 'input placeholder', 'lptw_contact_form_domain' ); ?>" required data-mask="<?php echo esc_attr( $phone_mask ); ?>">
						<label for="your-phone" class="input-label <?php echo $input_label_color; ?>" title="<?php _ex( 'Your phone...', 'label title', 'lptw_contact_form_domain' ); ?>"><i class="fa fa-phone"></i></label>
					</div>
				<?php endif; ?>
				<?php if ( $show_email_input == TRUE ) : ?>
					<div class="input-wrapper <?php echo $input_wrapper_color; ?>">
						<input type="text" name="your-email" id="your-email" class="input-field" placeholder="<?php _ex( 'Your email...', 'input placeholder', 'lptw_contact_form_domain' ); ?>" required>
						<label for="your-email" class="input-label <?php echo $input_label_color; ?>" title="<?php _ex( 'Your email...', 'label title', 'lptw_contact_form_domain' ); ?>"><i class="fa fa-envelope"></i></label>
					</div>
				<?php endif; ?>
				<?php if ( $show_message_textarea == TRUE ) : ?>
					<div class="textarea-wrapper <?php echo $textarea_wrapper_color; ?>" id="message-<?php echo rand( 1000, 9999 ); ?>">
						<textarea name="your-message" id="lptw-your-message" class="input-area"></textarea>
						<label for="lptw-your-message" class="textarea-label <?php echo $textarea_label_color; ?>"><span class="label-icon <?php echo $label_icon_color; ?>"><i class="fa fa-comments-o"></i></span><span class="label-text"><?php _ex( 'Write your message here...', 'textarea placeholder', 'lptw_contact_form_domain' ); ?></span></label>
					</div>
				<?php endif; ?>
				<button type="submit" id="lptw-contact-form-submit" class="lptw-button <?php echo $button_color; ?>"><?php
					if ( $button_name ) {
						echo esc_attr( $button_name );
					} else {
						_ex( 'Send message', 'button text', 'lptw_contact_form_domain' );
					}
					?><span class="lptw-button-spinner"><i class="fa fa-circle-o-notch fa-spin fa-fw"></i></span></button>
			</form>
			<div class="lptw-round <?php echo $round_color; ?>"></div>
			<a href="#" class="close-send-mode">&times;</a>

			<div class="after-send-text lptw-ast-<?php echo $message_size; ?>"><?php _e( 'Thank you for your message! We will answer you as soon as possible.', 'lptw_contact_form_domain' ); ?></div>
		</div>
		<?php echo $args['after_widget']; ?>

		<?php

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'lptw_contact_form_widget', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	/* --------------------------------- Widget Backend --------------------------------- */
	public function form( $instance ) {
		$title = ( isset( $instance['title'] ) ) ? esc_attr( $instance['title'] ) : __( 'Simple Secure Contact Form', 'lptw_contact_form_domain' );

		$show_widget_title = ( isset( $instance['show_widget_title'] ) ) ? (bool) $instance['show_widget_title'] : TRUE;

		$widget_description = esc_textarea( $instance['widget_description'] );

		$color_scheme = ( isset( $instance['color_scheme'] ) ) ? $instance['color_scheme'] : 'red';

		$show_name_input = ( isset( $instance['show_name_input'] ) ) ? (bool) $instance['show_name_input'] : TRUE;

		$show_phone_input = ( isset( $instance['show_phone_input'] ) ) ? (bool) $instance['show_phone_input'] : TRUE;

		$show_email_input = ( isset( $instance['show_email_input'] ) ) ? (bool) $instance['show_email_input'] : TRUE;

		$show_message_textarea = ( isset( $instance['show_message_textarea'] ) ) ? (bool) $instance['show_message_textarea'] : TRUE;

		$use_wp_admin_email = ( isset( $instance['use_wp_admin_email'] ) ) ? (bool) $instance['use_wp_admin_email'] : FALSE;

		$custom_email = ( isset( $instance['custom_email'] ) ) ? sanitize_email( $instance['custom_email'] ) : '';

		$bcc_email = ( isset( $instance['bcc_email'] ) ) ? sanitize_email( $instance['bcc_email'] ) : '';

		$button_name = ( isset( $instance['button_name'] ) ) ? esc_attr( $instance['button_name'] ) : '';

		$message_subject = ( isset( $instance['message_subject'] ) ) ? esc_attr( $instance['message_subject'] ) : '';

		$phone_mask = ( isset( $instance['phone_mask'] ) ) ? esc_attr( $instance['phone_mask'] ) : '';

		$message_size = ( isset( $instance['message_size'] ) ) ? $instance['message_size'] : 'normal';

		$ga_eventCategory = ( isset( $instance['ga_eventCategory'] ) ) ? esc_attr( $instance['ga_eventCategory'] ) : '';
		$ga_eventAction = ( isset( $instance['ga_eventAction'] ) ) ? esc_attr( $instance['ga_eventAction'] ) : '';
		$ga_eventLabel = ( isset( $instance['ga_eventLabel'] ) ) ? esc_attr( $instance['ga_eventLabel'] ) : '';

		$ym_counterID = ( isset( $instance['ym_counterID'] ) ) ? esc_attr( $instance['ym_counterID'] ) : '';
		$ym_targetName = ( isset( $instance['ym_targetName'] ) ) ? esc_attr( $instance['ym_targetName'] ) : '';

		// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_widget_title ); ?> id="<?php echo $this->get_field_id( 'show_widget_title' ); ?>" name="<?php echo $this->get_field_name( 'show_widget_title' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_widget_title' ); ?>"><?php _e( 'Display widget title?', 'lptw_contact_form_domain' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'widget_description' ); ?>"><?php _e( 'Description:', 'lptw_contact_form_domain' ); ?></label>
			<textarea class="widefat" rows="3" cols="20" id="<?php echo $this->get_field_id( 'widget_description' ); ?>" name="<?php echo $this->get_field_name( 'widget_description' ); ?>"><?php echo esc_textarea( $widget_description ); ?></textarea>
		</p>
		<hr>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $use_wp_admin_email ); ?> id="<?php echo $this->get_field_id( 'use_wp_admin_email' ); ?>" name="<?php echo $this->get_field_name( 'use_wp_admin_email' ); ?>" data-field="use_wp_admin_email"/>
			<label for="<?php echo $this->get_field_id( 'use_wp_admin_email' ); ?>"><?php _e( 'Use site admin email for messages?', 'lptw_contact_form_domain' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'custom_email' ); ?>"><?php _e( 'Custom email:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'custom_email' ); ?>" name="<?php echo $this->get_field_name( 'custom_email' ); ?>" type="text" value="<?php echo $custom_email; ?>" data-field="custom_email"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'bcc_email' ); ?>"><?php _e( 'BCC email:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'bcc_email' ); ?>" name="<?php echo $this->get_field_name( 'bcc_email' ); ?>" type="text" value="<?php echo $bcc_email; ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'message_subject' ); ?>"><?php _e( 'Message subject:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'message_subject' ); ?>" name="<?php echo $this->get_field_name( 'message_subject' ); ?>" type="text" value="<?php echo $message_subject; ?>" data-field="message_subject"/>
		</p>

		<hr>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_name_input ); ?> id="<?php echo $this->get_field_id( 'show_name_input' ); ?>" name="<?php echo $this->get_field_name( 'show_name_input' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_name_input' ); ?>"><i class="fa fa-user"></i>&nbsp;<?php _e( 'Display input for name?', 'lptw_contact_form_domain' ); ?>
			</label></p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_phone_input ); ?> id="<?php echo $this->get_field_id( 'show_phone_input' ); ?>" name="<?php echo $this->get_field_name( 'show_phone_input' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_phone_input' ); ?>"><i class="fa fa-phone"></i>&nbsp;<?php _e( 'Display input for phone?', 'lptw_contact_form_domain' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'phone_mask' ); ?>"><?php _e( 'Phone mask:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'phone_mask' ); ?>" name="<?php echo $this->get_field_name( 'phone_mask' ); ?>" type="text" value="<?php echo $phone_mask; ?>" data-field="phone_mask"/>
		</p>
		<p class="description"><?php _e( 'The following mask definitions are predefined:', 'lptw_contact_form_domain' ); ?>
			<br/>a - <?php _e( 'Represents an alpha character', 'lptw_contact_form_domain' ); ?>
			(A-Z,a-z)<br/>9 - <?php _e( 'Represents a numeric character', 'lptw_contact_form_domain' ); ?> (0-9)<br/>*
			- <?php _e( 'Represents an alphanumeric character', 'lptw_contact_form_domain' ); ?>
			(A-Z,a-z,0-9)</p>


		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_email_input ); ?> id="<?php echo $this->get_field_id( 'show_email_input' ); ?>" name="<?php echo $this->get_field_name( 'show_email_input' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_email_input' ); ?>"><i class="fa fa-envelope"></i>&nbsp;<?php _e( 'Display input for email?', 'lptw_contact_form_domain' ); ?>
			</label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_message_textarea ); ?> id="<?php echo $this->get_field_id( 'show_message_textarea' ); ?>" name="<?php echo $this->get_field_name( 'show_message_textarea' ); ?>"/>
			<label for="<?php echo $this->get_field_id( 'show_message_textarea' ); ?>"><i class="fa fa-comments-o"></i>&nbsp;<?php _e( 'Display textarea for message?', 'lptw_contact_form_domain' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'color_scheme' ); ?>"><?php _e( 'Color scheme:', 'lptw_contact_form_domain' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'color_scheme' ); ?>" id="<?php echo $this->get_field_id( 'color_scheme' ); ?>" class="widefat">
				<option value="green"<?php selected( $color_scheme, 'green' ); ?>><?php _e( 'Green', 'lptw_contact_form_domain' ); ?></option>
				<option value="red"<?php selected( $color_scheme, 'red' ); ?>><?php _e( 'Red', 'lptw_contact_form_domain' ); ?></option>
				<option value="orange"<?php selected( $color_scheme, 'orange' ); ?>><?php _e( 'Orange', 'lptw_contact_form_domain' ); ?></option>
				<option value="lightblue"<?php selected( $color_scheme, 'lightblue' ); ?>><?php _e( 'Light blue', 'lptw_contact_form_domain' ); ?></option>
				<option value="darkblue"<?php selected( $color_scheme, 'darkblue' ); ?>><?php _e( 'Dark blue', 'lptw_contact_form_domain' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'message_size' ); ?>"><?php _e( 'Message size:', 'lptw_contact_form_domain' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'message_size' ); ?>" id="<?php echo $this->get_field_id( 'message_size' ); ?>" class="widefat">
				<option value="small"<?php selected( $message_size, 'small' ); ?>><?php _e( 'Small', 'lptw_contact_form_domain' ); ?></option>
				<option value="normal"<?php selected( $message_size, 'normal' ); ?>><?php _e( 'Normal', 'lptw_contact_form_domain' ); ?></option>
				<option value="large"<?php selected( $message_size, 'large' ); ?>><?php _e( 'Large', 'lptw_contact_form_domain' ); ?></option>
			</select>
		</p>
		<p class="description"><?php _e( 'Select size of a message font after email sending.', 'lptw_contact_form_domain' ); ?></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'button_name' ); ?>"><?php _e( 'Button name:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'button_name' ); ?>" name="<?php echo $this->get_field_name( 'button_name' ); ?>" type="text" value="<?php echo $button_name; ?>" data-field="button_name"/>
		</p>
		<hr>
		<p>
			<label for="<?php echo $this->get_field_id( 'ga_eventCategory' ); ?>"><?php _e( 'GA Event Category:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'ga_eventCategory' ); ?>" name="<?php echo $this->get_field_name( 'ga_eventCategory' ); ?>" type="text" value="<?php echo $ga_eventCategory; ?>" data-field="ga_eventCategory"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'ga_eventAction' ); ?>"><?php _e( 'GA Event Action:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'ga_eventAction' ); ?>" name="<?php echo $this->get_field_name( 'ga_eventAction' ); ?>" type="text" value="<?php echo $ga_eventAction; ?>" data-field="ga_eventAction"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'ga_eventLabel' ); ?>"><?php _e( 'GA Event Label:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'ga_eventLabel' ); ?>" name="<?php echo $this->get_field_name( 'ga_eventLabel' ); ?>" type="text" value="<?php echo $ga_eventLabel; ?>" data-field="ga_eventLabel"/>
		</p>
		<p class="description">
			<a href="https://support.google.com/analytics/answer/1033068?hl=en" target="_blank"><?php _e( 'All about Events Tracking in Google Analytics', 'lptw_contact_form_domain' ); ?></a>
		</p>
		<hr>
		<p>
			<label for="<?php echo $this->get_field_id( 'ym_counterID' ); ?>"><?php _e( 'YM Counter ID:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'ym_counterID' ); ?>" name="<?php echo $this->get_field_name( 'ym_counterID' ); ?>" type="text" value="<?php echo $ym_counterID; ?>" data-field="ym_counterID"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'ym_targetName' ); ?>"><?php _e( 'YM Target Name:', 'lptw_contact_form_domain' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'ym_targetName' ); ?>" name="<?php echo $this->get_field_name( 'ym_targetName' ); ?>" type="text" value="<?php echo $ym_targetName; ?>" data-field="ym_targetName"/>
		</p>
		<p class="description">
			<a href="https://yandex.ru/support/metrika/objects/reachgoal.xml" target="_blank"><?php _e( 'All about form submit tracking in Yandex.Metrika', 'lptw_contact_form_domain' ); ?></a>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show_widget_title'] = isset( $new_instance['show_widget_title'] ) ? (bool) $new_instance['show_widget_title'] : FALSE;

		$instance['use_wp_admin_email'] = isset( $new_instance['use_wp_admin_email'] ) ? (bool) $new_instance['use_wp_admin_email'] : FALSE;
		$instance['custom_email'] = sanitize_email( $new_instance['custom_email'] );
		$instance['bcc_email'] = sanitize_email( $new_instance['bcc_email'] );

		$instance['button_name'] = sanitize_text_field( $new_instance['button_name'] );

		$instance['message_subject'] = sanitize_text_field( $new_instance['message_subject'] );

		$instance['phone_mask'] = sanitize_text_field( $new_instance['phone_mask'] );

		$instance['widget_description'] = sanitize_text_field( $new_instance['widget_description'] );

		$instance['color_scheme'] = strip_tags( $new_instance['color_scheme'] );

		$instance['show_name_input'] = isset( $new_instance['show_name_input'] ) ? (bool) $new_instance['show_name_input'] : FALSE;
		$instance['show_phone_input'] = isset( $new_instance['show_phone_input'] ) ? (bool) $new_instance['show_phone_input'] : FALSE;
		$instance['show_email_input'] = isset( $new_instance['show_email_input'] ) ? (bool) $new_instance['show_email_input'] : FALSE;
		$instance['show_message_textarea'] = isset( $new_instance['show_message_textarea'] ) ? (bool) $new_instance['show_message_textarea'] : FALSE;

		$instance['message_size'] = isset( $new_instance['message_size'] ) ? $new_instance['message_size'] : 'normal';

		$instance['ga_eventCategory'] = isset( $new_instance['ga_eventCategory'] ) ? $new_instance['ga_eventCategory'] : '';
		$instance['ga_eventAction'] = isset( $new_instance['ga_eventAction'] ) ? $new_instance['ga_eventAction'] : '';
		$instance['ga_eventLabel'] = isset( $new_instance['ga_eventLabel'] ) ? $new_instance['ga_eventLabel'] : '';
		$instance['ym_counterID'] = isset( $new_instance['ym_counterID'] ) ? $new_instance['ym_counterID'] : '';
		$instance['ym_targetName'] = isset( $new_instance['ym_targetName'] ) ? $new_instance['ym_targetName'] : '';

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions['lptw_contact_form_widget_options'] ) ) {
			delete_option( 'lptw_contact_form_widget_options' );
		}

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete( 'lptw_contact_form_widget', 'widget' );
	}

} // Class wpb_widget ends here

// Register and load the widget
function lptw_contact_form_load_widget() {
	register_widget( 'lptw_contact_form_widget' );
}

add_action( 'widgets_init', 'lptw_contact_form_load_widget' );

/* add filter for letter format in html */
add_filter( 'wp_mail_content_type', 'set_html_content_type' );
function set_html_content_type( $content_type ) {
	return 'text/html';
}

/* simple secure contact form - sending email through ajax */
add_action( 'wp_ajax_contact_form', 'send_contact_form_data' );
add_action( 'wp_ajax_nopriv_contact_form', 'send_contact_form_data' );

function send_contact_form_data() {
	check_ajax_referer( 'lptw-send-form-data' );

	if ( ! empty( $_POST['email'] ) ) {
		die();
	}

	$admin_email = base64_decode( $_POST['admin_email'] );
	$bcc_email = base64_decode( $_POST['bcc_email'] );

	if ( $_POST['message_subject'] ) {
		$subject = $_POST['message_subject'];
	} else {
		$subject = _x( 'New message from', 'email subject', 'lptw_contact_form_domain' ) . ' ' . get_bloginfo( 'name' );
	}

	$contacts_name = $_POST['your-name'];
	$contacts_phone = $_POST['your-phone'];
	$contacts_email = $_POST['your-email'];
	$contacts_message = $_POST['your-message'];

	$home_url = home_url();
	$home_url = str_replace( 'http://', '', $home_url );
	$home_url = str_replace( 'https://', '', $home_url );
	$home_url = str_replace( 'www.', '', $home_url );
	$headers[] = 'From: ' . $home_url . ' <noreply@' . $home_url . '>';

	$message = '';

	if ( ! empty( $contacts_name ) ) {
		$message .= '<p>' . _x( 'Name', 'email template', 'lptw_contact_form_domain' ) . ': ' . esc_attr( stripslashes( $contacts_name ) ) . '</p>' . "\r\n";
	}
	if ( ! empty( $contacts_phone ) ) {
		$message .= '<p>' . _x( 'Phone', 'email template', 'lptw_contact_form_domain' ) . ': ' . esc_attr( stripslashes( $contacts_phone ) ) . '</p>' . "\r\n";
	}
	if ( ! empty( $contacts_email ) ) {
		$message .= '<p>' . _x( 'E-mail', 'email template', 'lptw_contact_form_domain' ) . ': ' . esc_attr( stripslashes( $contacts_email ) ) . '</p>' . "\r\n";
	}
	if ( ! empty( $contacts_message ) ) {
		$message .= '<p>' . _x( 'Message', 'email template', 'lptw_contact_form_domain' ) . ': ' . esc_textarea( stripslashes( $contacts_message ) ) . '</p>' . "\r\n";
	}

	wp_mail( $admin_email, $subject, $message, $headers );
	if ( ! empty( $bcc_email ) ) {
		wp_mail( $bcc_email, $subject, $message, $headers );
	}
	die();
}

?>
