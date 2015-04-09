<?php
/*
Plugin Name: Simple Secure Contact Form
Plugin URI: http://lp-tricks.com/
Description: Simple Secure Contact Form display contact form widget in your widget area with "invisible" spam protection. No more spam, no more capcha!
Tags: widget, contact, contacts, contact form, contact form 7, email, form, mail, spam, antispam, multilingual, plugin
Version: 0.2
Author: Eugene Holin
Author URI: http://lp-tricks.com/
License: GPLv2 or later
Text Domain: lptw_contact_form_domain
*/

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
add_action( 'plugins_loaded', 'lptw_contact_form_load_textdomain' );
function lptw_contact_form_load_textdomain() {
  load_plugin_textdomain( 'lptw_contact_form_domain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/* load js and css styles */
add_action( 'wp_enqueue_scripts', 'lptw_contact_form_register_scripts' );
function lptw_contact_form_register_scripts() {
	wp_register_style( 'lptw-contact-form-style', plugins_url( 'css/simple-secure-contact-form.css', __FILE__ ) );
	wp_enqueue_style( 'lptw-contact-form-style' );

	wp_register_style( 'font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'font-awesome' );

	wp_enqueue_script( 'autosize', plugins_url( 'js/autosize.js', __FILE__ ), array(), '0.1', false );

	wp_register_script( 'lptw-contact-form-script', plugins_url( 'js/simple-secure-contact-form.js', __FILE__ ), array('jquery'), '0.1', false );
    wp_localize_script( 'lptw-contact-form-script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script('lptw-contact-form-script');
}

/* load js and css styles in admin area */
add_action( 'admin_enqueue_scripts', 'lptw_contact_form_register_scripts_admin' );
function lptw_contact_form_register_scripts_admin() {
	wp_register_style( 'font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'font-awesome' );
}

// Creating the widget with fluid images
class lptw_contact_form_widget extends WP_Widget {

    function __construct() {

		$widget_ops = array('classname' => 'lptw_contact_form_widget', 'description' => __( "Simple and secure contact form with invisible spam protection.", 'lptw_contact_form_domain') );
		parent::__construct('lptw-contact-form-widget', __('Simple Secure Contact Form', 'lptw_contact_form_domain'), $widget_ops);
		$this->alt_option_name = 'lptw_contact_form_widget_options';

		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );

    }

    // Creating widget front-end
    // This is where the action happens
	public function widget($args, $instance) {
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

		$show_widget_title = isset( $instance['show_widget_title'] ) ? $instance['show_widget_title'] : true;

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Contact Form', 'lptw_contact_form_domain' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$widget_description = apply_filters( 'widget_text', empty( $instance['widget_description'] ) ? '' : $instance['widget_description'], $instance );

        if ( isset( $instance[ 'color_scheme' ] ) ) { $color_scheme = $instance[ 'color_scheme' ] ; }
        else { $color_scheme = 'red'; }

		$use_wp_admin_email = isset( $instance['use_wp_admin_email'] ) ? $instance['use_wp_admin_email'] : false;

        if ( isset( $instance[ 'custom_email' ] ) ) {
            $custom_email = sanitize_email( $instance[ 'custom_email' ]) ;
            $custom_email = base64_encode($custom_email);
            }
        if ( isset( $instance[ 'bcc_email' ] ) ) {
            $bcc_email = sanitize_email( $instance[ 'bcc_email' ]) ;
            $bcc_email = base64_encode($bcc_email);
            }

        if ($use_wp_admin_email == true) {
            $admin_email = base64_encode(get_bloginfo('admin_email'));
        }
        else {
            $admin_email = $custom_email;
        }

		$show_name_input = isset( $instance['show_name_input'] ) ? $instance['show_name_input'] : false;
		$show_phone_input = isset( $instance['show_phone_input'] ) ? $instance['show_phone_input'] : false;
		$show_email_input = isset( $instance['show_email_input'] ) ? $instance['show_email_input'] : false;
		$show_message_textarea = isset( $instance['show_message_textarea'] ) ? $instance['show_message_textarea'] : false;

        $label_icon_color = 'label-icon-'.$color_scheme;
        $textarea_label_color = 'textarea-label-'.$color_scheme;
        $input_label_color = 'input-label-'.$color_scheme;
        $input_wrapper_color = 'input-wrapper-'.$color_scheme;
        $textarea_wrapper_color = 'textarea-wrapper-'.$color_scheme;
        $button_color = 'lptw-button-'.$color_scheme;
        $round_color = 'lptw-round-'.$color_scheme;

        ?>


		<?php echo $args['before_widget']; ?>
		<?php if ( $title && $show_widget_title == true ) {
			echo $args['before_title'] . $title . $args['after_title'];
		} ?>
        <div class="form-wrapper" id="formwr-<?php echo rand(1000,9999); ?>">
            <?php if ($widget_description != '') : ?>
            <p class="form-description"><?php echo !empty( $instance['filter'] ) ? wpautop( $widget_description ) : $widget_description; ?></p>
            <?php endif; ?>
            <form id="lptw-contact-form" id="form-<?php echo rand(1000,9999); ?>">
                <input type="email" name="email">
                <input type="hidden" name="admin_email" value="<?php echo $admin_email; ?>">
                <input type="hidden" name="bcc_email" value="<?php echo $bcc_email; ?>">
                <?php wp_nonce_field( 'lptw-send-form-data' ); ?>
                <?php if ($show_name_input == true) : ?>
        		<div class="input-wrapper <?php echo $input_wrapper_color; ?>">
                    <input type="text" name="your-name" id="your-name" class="input-field" placeholder="<?php _ex( 'Your name...', 'input placeholder', 'lptw_contact_form_domain' ); ?>" required>
                    <label for="your-name" class="input-label <?php echo $input_label_color; ?>" title="<?php _ex( 'Your name...', 'label title', 'lptw_contact_form_domain' ); ?>"><i class="fa fa-user"></i></label>
                </div>
                <?php endif; ?>
                <?php if ($show_phone_input == true) : ?>
    		    <div class="input-wrapper <?php echo $input_wrapper_color; ?>">
                    <input type="text" name="your-phone" id="your-phone" class="input-field" placeholder="<?php _ex( 'Your phone...', 'input placeholder', 'lptw_contact_form_domain' ); ?>" required>
                    <label for="your-phone" class="input-label <?php echo $input_label_color; ?>" title="<?php _ex( 'Your phone...', 'label title', 'lptw_contact_form_domain' ); ?>"><i class="fa fa-phone"></i></label>
                </div>
                <?php endif; ?>
                <?php if ($show_email_input == true) : ?>
    			<div class="input-wrapper <?php echo $input_wrapper_color; ?>">
                    <input type="text" name="your-email" id="your-email" class="input-field" placeholder="<?php _ex( 'Your email...', 'input placeholder', 'lptw_contact_form_domain' ); ?>" required>
                    <label for="your-email" class="input-label <?php echo $input_label_color; ?>" title="<?php _ex( 'Your email...', 'label title', 'lptw_contact_form_domain' ); ?>"><i class="fa fa-envelope"></i></label>
                </div>
                <?php endif; ?>
                <?php if ($show_message_textarea == true) : ?>
    	    	<div class="textarea-wrapper <?php echo $textarea_wrapper_color; ?>" id="message-<?php echo rand(1000,9999); ?>">
                    <textarea name="your-message" id="lptw-your-message" class="input-area"></textarea>
                    <label for="lptw-your-message" class="textarea-label <?php echo $textarea_label_color; ?>"><span class="label-icon <?php echo $label_icon_color; ?>"><i class="fa fa-comments-o"></i></span><span class="label-text"><?php _ex( 'Write your message here...', 'textarea placeholder', 'lptw_contact_form_domain' ); ?></span></label>
                </div>
                <?php endif; ?>
    		    <button type="submit" id="lptw-contact-form-submit" class="lptw-button <?php echo $button_color; ?>"><?php _ex( 'Send message', 'button text', 'lptw_contact_form_domain' ); ?></button>
    		</form>
            <div class="lptw-round <?php echo $round_color; ?>"></div>
            <a href="#" class="close-send-mode">&times;</a>
            <div class="after-send-text"><?php _e( 'Thank you for your message! We will answer you as soon as possible.', 'lptw_contact_form_domain' ); ?></div>
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
        if ( isset( $instance[ 'title' ] ) ) { $title = esc_attr( $instance[ 'title' ]) ; }
        else { $title = __( 'Simple Secure Contact Form', 'lptw_contact_form_domain' ); }

        if ( isset( $instance[ 'show_widget_title' ] ) ) { $show_widget_title = (bool) $instance[ 'show_widget_title' ]; }
        else { $show_widget_title = true; }

    	$widget_description = esc_textarea($instance['widget_description']);

        if ( isset( $instance[ 'color_scheme' ] ) ) { $color_scheme = $instance[ 'color_scheme' ] ; }
        else { $color_scheme = 'red'; }

        if ( isset( $instance[ 'show_name_input' ] ) ) { $show_name_input = (bool) $instance[ 'show_name_input' ]; }
        else { $show_name_input = true; }

        if ( isset( $instance[ 'show_phone_input' ] ) ) { $show_phone_input = (bool) $instance[ 'show_phone_input' ]; }
        else { $show_phone_input = true; }

        if ( isset( $instance[ 'show_email_input' ] ) ) { $show_email_input = (bool) $instance[ 'show_email_input' ]; }
        else { $show_email_input = true; }

        if ( isset( $instance[ 'show_message_textarea' ] ) ) { $show_message_textarea = (bool) $instance[ 'show_message_textarea' ]; }
        else { $show_message_textarea = true; }

        if ( isset( $instance[ 'use_wp_admin_email' ] ) ) { $use_wp_admin_email = (bool) $instance[ 'use_wp_admin_email' ]; }
        else { $use_wp_admin_email = false; }

        if ( isset( $instance[ 'custom_email' ] ) ) { $custom_email = sanitize_email( $instance[ 'custom_email' ]) ; }
        if ( isset( $instance[ 'bcc_email' ] ) ) { $bcc_email = sanitize_email( $instance[ 'bcc_email' ]) ; }

        // Widget admin form
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'lptw_contact_form_domain' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />

		<p><input class="checkbox" type="checkbox" <?php checked( $show_widget_title ); ?> id="<?php echo $this->get_field_id( 'show_widget_title' ); ?>" name="<?php echo $this->get_field_name( 'show_widget_title' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_widget_title' ); ?>"><?php _e( 'Display widget title?', 'lptw_contact_form_domain' ); ?></label></p>

        <p><label for="<?php echo $this->get_field_id( 'widget_description' ); ?>"><?php _e( 'Description:', 'lptw_contact_form_domain' ); ?></label>
		<textarea class="widefat" rows="3" cols="20" id="<?php echo $this->get_field_id('widget_description'); ?>" name="<?php echo $this->get_field_name('widget_description'); ?>"><?php echo $widget_description; ?></textarea></p>
        <hr>

		<p><input class="checkbox" type="checkbox" <?php checked( $use_wp_admin_email ); ?> id="<?php echo $this->get_field_id( 'use_wp_admin_email' ); ?>" name="<?php echo $this->get_field_name( 'use_wp_admin_email' ); ?>" data-field="use_wp_admin_email" />
		<label for="<?php echo $this->get_field_id( 'use_wp_admin_email' ); ?>"><?php _e( 'Use site admin email for messages?', 'lptw_contact_form_domain' ); ?></label></p>

        <label for="<?php echo $this->get_field_id( 'custom_email' ); ?>"><?php _e( 'Custom email:', 'lptw_contact_form_domain' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'custom_email' ); ?>" name="<?php echo $this->get_field_name( 'custom_email' ); ?>" type="text" value="<?php echo $custom_email; ?>" data-field="custom_email" />

        <label for="<?php echo $this->get_field_id( 'bcc_email' ); ?>"><?php _e( 'BCC email:', 'lptw_contact_form_domain' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'bcc_email' ); ?>" name="<?php echo $this->get_field_name( 'bcc_email' ); ?>" type="text" value="<?php echo $bcc_email; ?>" />

		<p><input class="checkbox" type="checkbox" <?php checked( $show_name_input ); ?> id="<?php echo $this->get_field_id( 'show_name_input' ); ?>" name="<?php echo $this->get_field_name( 'show_name_input' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_name_input' ); ?>"><i class="fa fa-user"></i>&nbsp;<?php _e( 'Display input for name?', 'lptw_contact_form_domain' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_phone_input ); ?> id="<?php echo $this->get_field_id( 'show_phone_input' ); ?>" name="<?php echo $this->get_field_name( 'show_phone_input' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_phone_input' ); ?>"><i class="fa fa-phone"></i>&nbsp;<?php _e( 'Display input for phone?', 'lptw_contact_form_domain' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_email_input ); ?> id="<?php echo $this->get_field_id( 'show_email_input' ); ?>" name="<?php echo $this->get_field_name( 'show_email_input' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_email_input' ); ?>"><i class="fa fa-envelope"></i>&nbsp;<?php _e( 'Display input for email?', 'lptw_contact_form_domain' ); ?></label></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_message_textarea ); ?> id="<?php echo $this->get_field_id( 'show_message_textarea' ); ?>" name="<?php echo $this->get_field_name( 'show_message_textarea' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_message_textarea' ); ?>"><i class="fa fa-comments-o"></i>&nbsp;<?php _e( 'Display textarea for message?', 'lptw_contact_form_domain' ); ?></label></p>

		<p>
			<label for="<?php echo $this->get_field_id('color_scheme'); ?>"><?php _e( 'Color scheme:', 'lptw_contact_form_domain' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'color_scheme' ); ?>" id="<?php echo $this->get_field_id('color_scheme'); ?>" class="widefat">
				<option value="green"<?php selected( $color_scheme, 'green' ); ?>><?php _e('Green', 'lptw_contact_form_domain'); ?></option>
				<option value="red"<?php selected( $color_scheme, 'red' ); ?>><?php _e('Red', 'lptw_contact_form_domain'); ?></option>
				<option value="orange"<?php selected( $color_scheme, 'orange' ); ?>><?php _e('Orange', 'lptw_contact_form_domain'); ?></option>
				<option value="lightblue"<?php selected( $color_scheme, 'lightblue' ); ?>><?php _e('Light blue', 'lptw_contact_form_domain'); ?></option>
				<option value="darkblue"<?php selected( $color_scheme, 'darkblue' ); ?>><?php _e('Dark blue', 'lptw_contact_form_domain'); ?></option>
			</select>
		</p>


        </p>
        <?php
    }

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['show_widget_title'] = isset( $new_instance['show_widget_title'] ) ? (bool) $new_instance['show_widget_title'] : false;

		$instance['use_wp_admin_email'] = isset( $new_instance['use_wp_admin_email'] ) ? (bool) $new_instance['use_wp_admin_email'] : false;
   		$instance['custom_email'] = sanitize_email($new_instance['custom_email']);
   		$instance['bcc_email'] = sanitize_email($new_instance['bcc_email']);

		if ( current_user_can('unfiltered_html') ) {
			$instance['widget_description'] =  $new_instance['widget_description'];
        }
		else {
			$instance['widget_description'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['widget_description']) ) ); // wp_filter_post_kses() expects slashed
        }

		$instance['color_scheme'] = strip_tags($new_instance['color_scheme']);

		$instance['show_name_input'] = isset( $new_instance['show_name_input'] ) ? (bool) $new_instance['show_name_input'] : false;
		$instance['show_phone_input'] = isset( $new_instance['show_phone_input'] ) ? (bool) $new_instance['show_phone_input'] : false;
		$instance['show_email_input'] = isset( $new_instance['show_email_input'] ) ? (bool) $new_instance['show_email_input'] : false;
		$instance['show_message_textarea'] = isset( $new_instance['show_message_textarea'] ) ? (bool) $new_instance['show_message_textarea'] : false;

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['lptw_contact_form_widget_options']) )
			delete_option('lptw_contact_form_widget_options');

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('lptw_contact_form_widget', 'widget');
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

    //if ( $_POST['admin_email'] == 'true' ) { $admin_email = get_bloginfo('admin_email'); }
    //else { $admin_email = $_POST['custom_email']; }

    if ( !empty($_POST['email']) ) {die();}

    $admin_email = base64_decode($_POST['admin_email']);
    $bcc_email = base64_decode($_POST['bcc_email']);

    $subject = _x( 'New message from', 'email subject', 'lptw_contact_form_domain' ).' '.get_bloginfo('name');

    $contacts_name = $_POST['your-name'];
    $contacts_phone = $_POST['your-phone'];
    $contacts_email = $_POST['your-email'];
    $contacts_message = $_POST['your-message'];

    $message = '';

    if (!empty($contacts_name)) {$message .= '<p>'._x( 'Name', 'email template', 'lptw_contact_form_domain' ).': '.$contacts_name.'</p>'."\r\n";}
    if (!empty($contacts_phone)) {$message .= '<p>'._x( 'Phone', 'email template', 'lptw_contact_form_domain' ).': '.$contacts_phone.'</p>'."\r\n";}
    if (!empty($contacts_email)) {$message .= '<p>'._x( 'E-mail', 'email template', 'lptw_contact_form_domain' ).': '.$contacts_email.'</p>'."\r\n";}
    if (!empty($contacts_message)) {$message .= '<p>'._x( 'Message', 'email template', 'lptw_contact_form_domain' ).': '.$contacts_message.'</p>'."\r\n";}

    wp_mail( $admin_email, $subject, $message );
    if ( !empty($bcc_email) ) { wp_mail( $bcc_email, $subject, $message ); }
    die();
}

?>