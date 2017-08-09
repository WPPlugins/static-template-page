=== static template page  ===
Contributors: Oren Kolker
Requires at least: 3.0
Tested up to: 3.1.4
Stable tag: 1.0.6
Tags: cms


Create Static Pages

== Description ==

This plugin  Allowes the theme writer to add a single page  with a single template,

and refer to it from code,

 without user configuration !!!

== Installation ==

1. Unzip the "kaluto-static-template-page" archive and put the folder into your plugins folder (/wp-content/plugins/).
2. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= What is this good for? =

1. Alternative for a static homepage, where the user can add it to the menu.
2. Special templates that need to be refered from code like:
    - few steps wizard
    - Second Home page

= How to use ? =

1. Register php templates with :
    register_static_template_page( 'My Home Page' , 'myhomepage.php');
2.  Get the Permalink:
     get_static_template_page_permalink( 'myhomepage.php')

== Changelog ==


= 0.1 =
* initial release


