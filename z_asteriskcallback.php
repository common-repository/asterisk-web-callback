<?php if ( ! defined( 'ABSPATH' ) ) die( 'Forbidden' );
/**
 * Plugin Name: Asterisk Web Callback
 * Description: A widget that make call back to visitor via Asterisk (Freepbx)
 * Version: 0.1
 * Author: Eugene Moiseenko
 * Author URI: https://eugenemoiseenko.ru/
 * License: GPLv2 or later
 * Text Domain: z_asteriskcallback
 * Domain Path: /languages/ 
 */

add_action( 'widgets_init', 'z_asteriskcallback_widget' );
add_action( 'plugins_loaded', 'zacw_load_plugin_textdomain' );
 
function zacw_load_plugin_textdomain() {
	load_plugin_textdomain( 'z_asteriskcallback', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

function z_asteriskcallback_widget() {
	register_widget( 'zacw_Widget' );
}

function zacw_send_error_notification($notifyemail, $telnum, $errcode)
{
	// remove filters, that can change header $headers
	remove_all_filters( 'wp_mail_from' );
	remove_all_filters( 'wp_mail_from_name' );

	$headers = 'From: Asterisk Web Callback Plugin <asteriskwebcallback@yoursite.com>' . "\r\n";	
	$res = wp_mail($notifyemail, __('Asterisk Web Callback plugin notification', 'z_asteriskcallback'), __('Callback unsuccessful! Tel number: ', 'z_asteriskcallback') . ' ' . $telnum . PHP_EOL . __('Error code: ', 'z_asteriskcallback') . $errcode, $headers);
	return $res;
}

class zacw_Widget extends WP_Widget {

	function zacw_Widget() {
		$widget_ops = array( 'classname' => 'z_asteriskcallback', 'description' => __('A widget that make call back to visitor via Asterisk (Freepbx)', 'z_asteriskcallback') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'z_asteriskcallback-widget' );
		$this->WP_Widget( 'z_asteriskcallback-widget', __('Asterisk Web Callback', 'z_asteriskcallback'), $widget_ops, $control_ops );
		wp_enqueue_style( 'z_asteriskcallback', plugins_url('css/z_asteriskcallback.css', __FILE__) );
	}
	
	function widget( $args, $instance ) {
		extract( $args );

		//Our variables from the widget settings.
		$title = apply_filters('widget_title', $instance['title'] );
		
		//Asterix connection settings		
		$strHost			= $instance['host'];
		$strPort 			= $instance['port'];
		$strUser 			= $instance['user'];
		$strSecret 			= $instance['secret'];
		$strCallerIdPrefix	= $instance['calleridprefix'];
		$strNumLenght 		= $instance['telnumlen'];
		$strRegionCode 		= $instance['regioncode'];
		$strChannel 		= $instance['channel'];
		$strContext 		= $instance['context'];
		$strWaitTime 		= $instance['waittime'];
		$strPriority 		= $instance['priority'];
		$strNotifyEmail		= $instance['notifyemail'];

		// for tel number value
		$strExten			= 0;

		echo $before_widget;

		//<!-- Callback button -->
		echo '<script>function hidecall() {document.getElementById("asteriskcallback-widget-popup_toggle").style.display="none";}</script>';
		echo '<script>function changestate() {document.getElementById("asteriskcallback-widget-button").disabled=(document.getElementById("asteriskcallback-widget-button").disabled ? false : true); return false;}</script>';
		echo '<a href="#call" onclick="hidecall();" id="asteriskcallback-widget-popup_toggle">';
		echo '<div class="asteriskcallback-widget-circle_phone" style="transform-origin: center;"></div>';
		echo '<div class="asteriskcallback-widget-circle_fill" style="transform-origin: center;"></div>';
		echo '<div class="asteriskcallback-widget-img_circle" style="transform-origin: center;">';
		echo '<div class="asteriskcallback-widget-img_circle_block" style="transform-origin: center;"></div>';
		echo '</div>';
		echo '</a>';
		//<!-- Callback button -->  

		//<!-- Callback begin -->    
		echo '<script>if (document.location.hash=="#call") {hidecall();}</script>';

		echo '<div id="call" class="asteriskcallback-widget-block">';

		if ( $title )
			echo '<div class="asteriskcallback-widget-title"><span> ' . $before_title . $title . $after_title . ' </span></div>';

		$strExten = sanitize_text_field($_POST['phonenumber']);

		if ( !isset($strExten) || $strExten == '' )
		{
			$strExten = sanitize_text_field($_POST['txtphonenumber']);
		}

		$res = ''; // callback result value
		if ( isset($strExten) && is_numeric($strExten) && strlen($strExten) == $strNumLenght && substr($strExten, 0, strlen($strRegionCode)) == $strRegionCode)
		{

			// for set CallerId value
			$strCallerId = "<$strCallerIdPrefix>:<$strExten>";

			$oSocket = fsockopen($strHost, $strPort, $errnum, $errdesc);
			if (!$oSocket) 
			{
				$res = __('Callback not avaible. Try again later.', 'z_asteriskcallback');
				if (isset($strNotifyEmail)) {
					if (zacw_send_error_notification($strNotifyEmail, $strExten, $errnum)) {
						$res = __('We will callback you at working hours.', 'z_asteriskcallback');
					}
				}
			}
			else
			{
				echo '<p class="asteriskcallback-widget-progressmessage">' . __('Wait for callback!', 'z_asteriskcallback') . '</p>';
				echo '<p class="asteriskcallback-widget-trymessage">' . __('If callback does not arrive, try again.', 'z_asteriskcallback') . '</p>';
  				echo '<form class="asteriskcallback-widget-form" action="' . $_SERVER['REQUEST_URI'] . '#call" method="get" onsubmit="changestate()">';
    			echo '<input type="submit" id="asteriskcallback-widget-button" class="asteriskcallback-widget-button" value="' . __('Try again', 'z_asteriskcallback') . '">';
  				echo '</form>';

				fputs($oSocket, "Action: login\r\n");
				fputs($oSocket, "Events: off\r\n");
				fputs($oSocket, "Username: $strUser\r\n");
				fputs($oSocket, "Secret: $strSecret\r\n\r\n");
				fputs($oSocket, "Action: originate\r\n");
				fputs($oSocket, "Channel: $strChannel\r\n");
				fputs($oSocket, "Timeout: $strWaitTime\r\n");
				fputs($oSocket, "CallerId: $strCallerId\r\n");
				fputs($oSocket, "Exten: $strExten\r\n");
				fputs($oSocket, "Context: $strContext\r\n");
				fputs($oSocket, "Priority: $strPriority\r\n\r\n");
				fputs($oSocket, "Action: Logoff\r\n\r\n");
				sleep (1);

				fclose($oSocket);
			}
		}
		else
		{
			echo '<p class="asteriskcallback-widget-inputmessage">' . __('Input your mobile', 'z_asteriskcallback') . '</p>';
			echo '<form class="asteriskcallback-widget-form" action="' . $_SERVER['REQUEST_URI'] . '#call" method="post" onsubmit="changestate()">';
			echo '<input type="text" class="asteriskcallback-widget-input" onkeypress="hidecall();" size="20" maxlength="' . $strNumLenght . '" name="txtphonenumber" placeholder="' . str_pad($strRegionCode, $strNumLenght , 'X') . '" pattern="' . $strRegionCode . '[0-9]{' . ($strNumLenght-strlen($strRegionCode)) . '}"><p></p>';
			echo '<input type="submit" id="asteriskcallback-widget-button" class="asteriskcallback-widget-button" onclick="hidecall();" value="' . __('Call me!', 'z_asteriskcallback') . '">';
			echo '</form>';
		}
		echo '<p class="asteriskcallback-widget-errormessage">' . $res . '</p>';
		echo '</td></tr>';
		echo '</table>';
		echo '</div>';
		//<!-- Callback - end -->   

		echo $after_widget;
	}

	//Update the widget 
	 
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		//Strip tags from title and name to remove HTML

		// title
		$title = sanitize_text_field( $new_instance['title'] );
		if ( isset($title) )
			$instance['title']			= $title;
		// host
		$host = sanitize_text_field( $new_instance['host'] );
		if ( isset($host) )
			$instance['host']			= $host;

		// port
		$port = sanitize_text_field( $new_instance['port'] );
		if ( isset($port) && is_numeric($port) )
			$instance['port']			= $port;

		// user
		$user = sanitize_text_field( $new_instance['user'] );
		if ( isset($user) )
			$instance['user']			= $user;

		// secret
		$secret = sanitize_text_field( $new_instance['secret'] );
		if ( isset($secret) )
			$instance['secret']			= $secret;

		// calleridprefix
		$calleridprefix = sanitize_text_field( $new_instance['calleridprefix'] );
		if (isset($calleridprefix))
			$instance['calleridprefix'] = $calleridprefix;

		// telnumlen
		$telnumlen = sanitize_text_field( $new_instance['telnumlen'] );
		if ( isset($telnumlen) && is_numeric($telnumlen) )
			$instance['telnumlen']		= $telnumlen;

		// regioncode
		$regioncode = sanitize_text_field( $new_instance['regioncode'] );
		if ( isset($regioncode) && is_numeric($regioncode) )
			$instance['regioncode']		= $regioncode;

		// channel
		$channel = sanitize_text_field( $new_instance['channel'] );
		if ( isset($channel) )
			$instance['channel']		= $channel;

		// context
		$context = sanitize_text_field( $new_instance['context'] );
		if ( isset($context) )
			$instance['context']		= $context;

		// waittime
		$waittime = sanitize_text_field( $new_instance['waittime'] );
		if ( isset($waittime) && is_numeric($waittime) )
			$instance['waittime']		= $waittime;

		// priority
		$priority = sanitize_text_field( $new_instance['priority'] );
		if ( isset($priority) && is_numeric($priority) )
			$instance['priority']		= $priority;

		// notifyemail
		$notifyemail = sanitize_email( $new_instance['notifyemail'] );
		if ( isset($notifyemail) )
			$instance['notifyemail']	= $notifyemail;

		return $instance;
	}

	
	function form( $instance ) {

		//Set up some default widget settings.
		$defaults = array( 	'title' 			=> __('Callback', 'z_asteriskcallback'), 
							'host' 				=> 'XXX.XXX.XXX.XXX',
							'port' 				=> 'XXXX',
							'user' 				=> '',
							'secret' 			=> '',
							'calleridprefix'	=> 'web',
							'telnumlen' 		=> 12,
							'regioncode' 		=> '+7',
							'channel' 			=> __('Local SIP channel', 'z_asteriskcallback'),
							'context' 			=> __('Webcall SIP context', 'z_asteriskcallback'),
							'waittime' 			=> 60000,
							'priority' 			=> 1,
							'notifyemail'		=> __('E-mail for notification, or blank for disable', 'z_asteriskcallback'),
				);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<div style="display: inline-flex;">
			<div style="display: block;">
				<p><!-- Widget Title: Text Input. -->
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_textarea($instance['title']); ?>" style="width:100%;" />
				</p>
				<p><!-- calleridprefix (text input)-->
					<label for="<?php echo $this->get_field_id( 'calleridprefix' ); ?>"><?php _e('CallerId Prefix:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'calleridprefix' ); ?>" name="<?php echo $this->get_field_name( 'calleridprefix' ); ?>" value="<?php echo esc_textarea($instance['calleridprefix']); ?>" style="width:100%;" />
				</p>
				<p><!-- telnumlen (text input)-->
					<label for="<?php echo $this->get_field_id( 'telnumlen' ); ?>"><?php _e('Tel number Lenght:', 'z_asteriskcallback'); ?></label>
					<input type="text" pattern="[0-9]{,2}" id="<?php echo $this->get_field_id( 'telnumlen' ); ?>" name="<?php echo $this->get_field_name( 'telnumlen' ); ?>" value="<?php echo esc_textarea($instance['telnumlen']); ?>" style="width:100%;" />
				</p>
				<p><!-- regioncode (text input)-->
					<label for="<?php echo $this->get_field_id( 'regioncode' ); ?>"><?php _e('Region Code:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'regioncode' ); ?>" name="<?php echo $this->get_field_name( 'regioncode' ); ?>" value="<?php echo esc_textarea($instance['regioncode']); ?>" style="width:100%;" />
				</p>		
				<p><!-- waittime (text input)-->
					<label for="<?php echo $this->get_field_id( 'waittime' ); ?>"><?php _e('Wait time, ms:', 'z_asteriskcallback'); ?></label>
					<input type="text" pattern="[0-9]{,6}" id="<?php echo $this->get_field_id( 'waittime' ); ?>" name="<?php echo $this->get_field_name( 'waittime' ); ?>" value="<?php echo esc_textarea($instance['waittime']); ?>" style="width:100%;" />
				</p>
				<p><!-- notifyemail (text input)-->
					<label for="<?php echo $this->get_field_id( 'notifyemail' ); ?>"><?php _e('Notify e-mail:', 'z_asteriskcallback'); ?></label>
					<input type="email" id="<?php echo $this->get_field_id( 'notifyemail' ); ?>" name="<?php echo $this->get_field_name( 'notifyemail' ); ?>" value="<?php echo esc_textarea($instance['notifyemail']); ?>" style="width:100%;" />
				</p>
			</div>
			<div style="display: inline-block; width: 20px;"></div>
			<div style="display: inline-block;">
				<p>	<!-- host (text input)-->
					<label for="<?php echo $this->get_field_id( 'host' ); ?>"><?php _e('SIP host:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'host' ); ?>" name="<?php echo $this->get_field_name( 'host' ); ?>" value="<?php echo esc_textarea($instance['host']); ?>" style="width:100%;" />
				</p>
				<p><!-- port (text input)-->
					<label for="<?php echo $this->get_field_id( 'port' ); ?>"><?php _e('SIP port:', 'z_asteriskcallback'); ?></label>
					<input type="text" pattern="[0-9]{,6}" id="<?php echo $this->get_field_id( 'port' ); ?>" name="<?php echo $this->get_field_name( 'port' ); ?>" value="<?php echo esc_textarea($instance['port']); ?>" style="width:100%;" />
				</p>
				<p><!-- user (text input)-->
					<label for="<?php echo $this->get_field_id( 'user' ); ?>"><?php _e('User name:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'user' ); ?>" name="<?php echo $this->get_field_name( 'user' ); ?>" value="<?php echo esc_textarea($instance['user']); ?>" style="width:100%;" />
				</p>
				<p><!-- password (text input)-->
					<label for="<?php echo $this->get_field_id( 'secret' ); ?>"><?php _e('Password:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'secret' ); ?>" name="<?php echo $this->get_field_name( 'secret' ); ?>" value="<?php echo esc_textarea($instance['secret']); ?>" style="width:100%;" />
				</p>
				<p><!-- channel (text input)-->
					<label for="<?php echo $this->get_field_id( 'channel' ); ?>"><?php _e('SIP channel:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'channel' ); ?>" name="<?php echo $this->get_field_name( 'channel' ); ?>" value="<?php echo esc_textarea($instance['channel']); ?>" style="width:100%;" />
				</p>
				<p><!-- context (text input)-->
					<label for="<?php echo $this->get_field_id( 'context' ); ?>"><?php _e('SIP context:', 'z_asteriskcallback'); ?></label>
					<input id="<?php echo $this->get_field_id( 'context' ); ?>" name="<?php echo $this->get_field_name( 'context' ); ?>" value="<?php echo esc_textarea($instance['context']); ?>" style="width:100%;" />
				</p>
				<p><!-- priority (text input)-->
					<label for="<?php echo $this->get_field_id( 'priority' ); ?>"><?php _e('SIP Priority:', 'z_asteriskcallback'); ?></label>
					<input type="text" pattern="[0-9]{,6}" id="<?php echo $this->get_field_id( 'priority' ); ?>" name="<?php echo $this->get_field_name( 'priority' ); ?>" value="<?php echo esc_textarea($instance['priority']); ?>" style="width:100%;" />
				</p>
			</div>
		</div>
	<?php
	}
}
?>