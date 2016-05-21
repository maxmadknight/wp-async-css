=== WP Async CSS ===
Contributors: robsat91
Donate link: http://donatenow.wc.lt/?donate=robert.sather@outlook.com&method=PayPal
Tags: stylesheet, css, async, asynchronous
Requires at least: 3.5
Tested up to: 4.5.2
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will hook into the WordPress style handling system and load the selected stylesheets asynchronous.

== Description ==

When loading a webpage with a lot of CSS-files the loading time can go through the roof!
With this plugin you can choose which files to load asynchronously and therefore cut down the time before the page starts to show.

The plugin uses a <a href="https://remysharp.com/2010/10/08/what-is-a-polyfill" target="_blank">polyfill</a> called <a href="https://github.com/filamentgroup/loadCSS" target="_blank">loadCSS</a>.
This allows us to use JavaScript to load selected CSS-files after the page have started to appear on screen.

The recommended use of this is to load your main stylesheet synchronously and then select non-vital CSS-files to load after (async).
Non-vital CSS-files can be for example fonts, icons, template-specific CSS, plugin CSS etc.

If you find a bug or have an idea for improvement please do post to a thread in the support section!
Would be much appreaciated!

Thanks :)

== Installation ==

1. Copy the `wp-async-css` folder into your `wp-content/plugins` directory.
2. Activate the `WP Async CSS` plugin via the plugins admin page.
3. Navigate to Settings and `WP Async CSS`. Then follow the instructions to select which stylesheets you want to load asynchronous.
4. Navigate to front-end and control that the page loads correctly.

== Screenshots ==

1. Editing which CSS-file that should be loaded asynchronously.
2. The result of having the main stylesheet load synchronously while the additional stylesheets loads asynchronously.

== Frequently Asked Questions ==

= I have now installed and activated the plugin. What now? =

Navigate to the settings page and select the desired css-files to load asynchronously.

= Which files should be loaded asynchronously? =

Your main stylesheet should not be loaded async because we need it from the early point of rendering. But for example FontAwesome or other additional stuff can be loaded later. But the bottom line is to test what that works or not.

= My webpage looks broken for a brief moment when loading the page. Whats up? =

You have probably selected som vital CSS-files to load async. This is not good because they are loaded to late and the page is displayed to early.

= How do i know which file to select/unselect? =

Here you either need to be a developer or ask one for help.

== Changelog ==

= 1.1 =
* Imported plugin to Git and WordPress SVN repository.

= 1.2 =
* Removed async CSS loading on login and registration form screen.

== Upgrade Notice ==