=== Asterisk Web Callback ===
Contributors: eugenemoiseenko 
Donate link: https://paypal.me/eugenemoiseenko
Tags: asterisk, freepbx, callback, web, call, back, sip, trunk
Requires at least: 4.8
Tested up to: 4.9
Requires PHP: 5.5
Stable tag: 0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A widget that make call back to visitor via Asterisk

== Description ==

A widget that allows you to make a callback to a client number via your Asterisk from your site.

If you have own site and Asterisk for SIP calls, and need to make callback to your customer, use Asterisk Web Callback widget!
It's simple solution to add callback function at web site.

Major features in Asterisk Web Callback include:

1. Make a callback to a customer number via Asterisk Manager Interface (AMI) at working time;

2. Pattern validate customer number when input (notify on the site page);

3. Notification that a callback is in progress (message on the site page);

4. Sending notifications to your e-mail if the customer requested a callback when:

	* working day is off or holyday;
	* Asterisk was unavailable for callback.

5. Easy navigation to the form of a callback with a floating button.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the 'Plugins' screen in WordPress

3. Use the Appearance->Widgets to add Asterisk Web Callback widget

4. Activate Asterisk AMI, eg:

	/etc/asterisk/manager.conf
	[general]
	enabled = yes
	port = 5038
	bindaddr = 0.0.0.0

5. Add Asterisk AMI user, eg:

	/etc/asterisk/manager.conf
	[c2call]
	secret=VeryStrongPassword
	deny=0.0.0.0/0.0.0.0
	permit=YourWebServerIP
	read=system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate
	write=system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate

6. Reload your Asterisk, eg:
	
	asterisk -rx "module reload manager"

7. Enable fsockopen() php-method on your web server, eg:
	php.ini:
	allow_url_fopen = On

8. Set widget parameters:
	* Title: web form title;
	* SIP host: external ip or name of your SIP Asterisk server;
	* SIP port: external port for connect to Asterisk AMI;
	* User name: Asterisk AMI user name;
	* Password: Asterisk AMI user password (set strong value);
	* CallerID prefix: prefix for CallerID value (eg: web);
	* Tel number lenght: digits count of customer number (used to exclude the entry of unwanted numbers), eg: 11 for Russia;
	* Region code: customer number prefix (used to exclude the entry of unwanted numbers), eg: 89 - for mobile numbers in Russia;
	* SIP channel: your Asterisk SIP channel, used for incoming web calls;
	* SIP context: your Asterisk SIP context, used for incoming web calls;
	* Wait time: count of ms answer waiting, eg: 60000 - for 1 minute answer waiting;
	* SIP priority: your Asterisk SIP priority for callback function;
	* Notify e-mail: address for receiving notifications of attempts to callback outside office hours.

== Frequently Asked Questions ==

= What version of Asterisk i can use for Web Callback? =

You can use any version of Asterisk, which supports AMI.

= Is the access to AMI secure? =

You must use access to AMI from dedicated web-server's ip.
Strong password for AMI user - one more condition.

== Screenshots ==

1. Add an AMI Asterisk user.

2. Set the widget parameters.

3. A simple web page with an Asterisk Web Callback widget.

4. Notification of the customer about the beginning of a callback.

5. Notify the customer that the callback will be made during business hours.

== Changelog == 
	
	This is first version of Asterisk Web CallBack

== Upgrade Notice ==

	This is first version of Asterisk Web CallBack