<?php

/*
Plugin Name: WP Async CSS
Plugin URI: 
Description: This plugin will hook onto the WordPress style handling system and load the selected stylesheets asynchronous.
Version: 1.1
Text Domain: async-css-frontend
Author: Robert Sæther
Author URI: https://github.com/roberts91
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Pro tip: Whitelisted stylesheets = stylesheets that will be loaded asynchronously

if( ! defined( 'ABSPATH' ) ) exit;

class Async_CSS_frontend {
    
    // The list of stylesheets we want to load async
    var $whitelisted_handles = array();
    
    // The list of all registered stylesheet handles
    var $all_handles = array();
    
    // Constructor
    function __construct()
    {
        
        // Load translation-folder
        load_plugin_textdomain( 'async-css-frontend', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
        
        // Gets all the whitelisted stylesheets from the database
        $this->get_stylesheet_whitelist();
        
        // Gets all the whitelisted stylesheets from the database
        $this->get_frontend_handles();
        
        // Check if we are in WP Admin
        if( is_admin() )
        {

            // Add optionspage
            add_action( 'admin_menu', array($this, 'add_options_page') );
            
        }
        // This is a front-end request
        else
        {
            
            // Adds loadCSS to the head-portion of the page
            add_action('wp_head', array($this, 'loadcss_init'), 7);
            
            // This filter edits 
            add_filter('style_loader_tag', array($this, 'custom_style_loader'), 9999, 3);
            
            // This function caches all the handles of the stylesheets that are loaded during av front-end request
            add_action('wp_print_styles', array($this, 'cache_frontend_style_handles'), 9999);
            
        }
        
    }
    
    // Gets all the whitelisted stylesheets from the database
    private function get_stylesheet_whitelist()
    {
        
        // Get whitelisted handles
        $whitelisted_handles = array_keys(get_option('async_css_frontend_whitelisted_stylesheet_handles', array()));
        
        // Update object variable
        $this->whitelisted_handles = $whitelisted_handles;
        
    }
    
    // Caches the stylesheets that are loaded during a front-end request
    public function cache_frontend_style_handles()
    {
        // Get global var
        global $wp_styles;
        
        // Update stylesheet-handles
        update_option('async_css_frontend_stylesheet_handles', $wp_styles->queue);
        
    }
    
    // Gets all cached stylesheet handles
    public function get_frontend_handles()
    {
    
        // Get all handles, default empty array
        $all_handles = get_option('async_css_frontend_stylesheet_handles', array());
    
        // Set class variable
        $this->all_handles = $all_handles;
     
    }
    
    // Add loadCSS polyfill to head inline and outside WP's asset handling
    public function loadcss_init ()
    {
        // Get loadCSS-file
        $loadcss_file = plugins_url() . '/assets/scripts/loadCSS.js';
        
        // Fetch content
        $content = file_get_contents($loadcss_file);
        
        // Print out in head
        echo '<script>' . $content . '</script>';
        
    }
    
    // Edit stylesheet-inclusion method
    public function custom_style_loader( $html, $handle, $href )
    {
        // We do not touch this stylesheet if not in array
        if( ! in_array( $handle, $this->whitelisted_handles ) ) return $html;
    
        // Try to catch media-attribute in HTML-tag
        preg_match('/media=\'(.*)\'/', $html, $match);
    
        // Extract media-attribute, default all
        $media = (isset($match[1]) ? $match[1] : 'all');
    
        // Return new markup
        //return '<!-- ' . $handle . '-->' . "\n" . '<script>loadCSS("' . $href . '",0,"' . $media . '");</script>' . "\n";
        return '<script>loadCSS("' . $href . '",0,"' . $media . '");</script>' . "\n";
    
    }
    
    /* SETTINGS */
    
    // Register optionspage and settings
    public function add_options_page()
    {
        // Register optionspage
        add_options_page( 'WP Async CSS', 'WP Async CSS', 'manage_options', 'async-css-frontend', array($this, 'options_page_view'));
        
        // Register settings
        add_action( 'admin_init', array($this, 'register_settings') );
    }
    
    // Register settings
    function register_settings()
    {
        // Register setting for whitelisted stylesheet handles
        register_setting( 'async-css-frontend-settings-group', 'async_css_frontend_whitelisted_stylesheet_handles' );
    }
    
    // Formview for optionspage
    public function options_page_view()
    {
        ?>
        <div class="wrap">
            <h2>WP Async CSS</h2>
            <form method="post" action="options.php">
                <p>
                    <?php _e('Here you can select which stylesheets you want to load asynchronously.', 'async-css-frontend'); ?><br>
                    <?php _e('Note: If you cannot find the handle you are looking for please visit your frontpage to update the handle-list.', 'async-css-frontend'); ?></p>
                <?php settings_fields( 'async-css-frontend-settings-group' ); ?>
                <?php do_settings_sections( 'async-css-frontend-settings-group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Select handles', 'async-css-frontend'); ?></th>
                        <td>
                        	<fieldset>
                                <legend class="screen-reader-text"><span><?php _e('Select handles', 'async-css-frontend'); ?></span></legend>
                                <?php foreach($this->all_handles as $handle): ?>
                                <label><input type="checkbox" name="async_css_frontend_whitelisted_stylesheet_handles[<?php echo $handle; ?>]" value="1"<?php if(in_array($handle, $this->whitelisted_handles)) echo ' checked="checked"'; ?> /> <span class="date-time-text format-i18n"><?php echo $handle; ?></span></label><br />
                                <?php endforeach; ?>    
                        	</fieldset>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
}

// Initalize class
new Async_CSS_frontend;