=== Plugin Name ===
Contributors: geilt, sudravirodhin
Donate link: http://www.esotech.org/
Tags: forms, mail, email, smtp
Requires at least: 3.3.2
Tested up to: 3.3.2
Stable tag: trunk

Under "Settings" enables "Forms" panel to customize forms to use with a shortcode. Enables "Email" panel to config SMTP Settings. 

== Description ==

This plugin is designed to create an admin panel called "Forms" under "Settings" create a form with required fields. It also enables "Email" under "Settings" allowing for SMTP e-mails from your wordpress site. The plugin works by using a shortcode on the desired form front end post. It allows a user to use their own form to post into the proper processing page as long as the required fields are sent. 

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory or search for it and install.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your mail settings under "Settings" -> "Email".
4. Configure your form settings under "Settings" -> "Forms".
5. Place [simpul_forms] on the contact page/post you wish to display and process the form.

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Changelog ==
= 1.03 = 
Updated script queuing for efficiency and to prevent any conflict with other plugins, including other simpul plugins.

= 1.02 =
Fixed jQuery file not loading to prevent value boxes from appearing when not needed.

= 1.01 =
Changed maximum amount of numbers in phone number to 15 according to E.164 standard. All spaces, dashes, and non-numbers are removed so only numbers are counted.

= 1.0 =
* First Upload
