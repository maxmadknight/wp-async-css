<?php

/*
Plugin Name: WP Async CSS
Plugin URI: 
Description: This plugin will hook onto the WordPress style handling system and load the selected stylesheets asynchronous.
Version: 1.2
Text Domain: wp-async-css
Author: Robert Sæther
Author URI: https://github.com/roberts91
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Pro tip: Whitelisted stylesheets = stylesheets that will be loaded asynchronously

if( ! defined( 'ABSPATH' ) ) exit;

// Define class (No shit, sherlock!)
class WP_Async_CSS {
    
    // The list of stylesheets we want to load asynchronous
    var $whitelisted_handles;
    
    // The list of all registered stylesheet handles
    var $all_handles;
    
    // The plugin url
    var $plugin_url;
    
    // Plugin name
    var $plugin_name = 'WP Async CSS';
    
    // Plugin slug
    var $plugin_slug = 'wp-async-css';
    
    // Set developer mode
    var $dev_mode = false;
    
    // Constructor
    function __construct()
    {
        
        // Set the plugin url
        $this->plugin_url = plugin_dir_url( __FILE__ );
        
        // Load translation-folder
        load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
        
        // Gets all the whitelisted stylesheets from the database
        $this->get_whitelisted_stylesheets();
        
        // Gets all the whitelisted stylesheets from the database
        $this->get_cached_stylesheet_handles();
        
        // Check if we are in WP Admin
        if( is_admin() )
        {

            // Add optionspage
            add_action( 'admin_menu', array($this, 'add_options_page') );
         
            // Add admin CSS
            add_action( 'admin_enqueue_scripts', array($this, 'admin_styles') );
            
        }
        // This is a front-end request
        else
        {
            
            // Make sure that we don't load this on login screen (sorry to the guys that actually need this btw. Contact me and i'll fix it for ya!)
            if(!$this->is_login_page())
            {
                // Adds loadCSS to the head-portion of the page
                add_action('wp_head', array($this, 'loadcss_init'), 7);
            
                // This filter edits 
                add_filter('style_loader_tag', array($this, 'custom_style_loader'), 9999, 3);
            
                // This function caches all the handles of the stylesheets that are loaded during av front-end request
                add_action('wp_print_styles', array($this, 'cache_stylesheet_handles'), 9999);
            }
            
        }
        
    }
    
    // Check if we are on the login og registraion page
    private function is_login_page()
    {
        return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
    }
    
    // Add styles to admin
    public function admin_styles()
    {
        // Add styles to WP-admin
        wp_enqueue_style( $this->plugin_slug . '-style', $this->plugin_url . 'assets/styles/main' . ($this->dev_mode ? '' : '.min') . '.css' );
        
    }
    
    // Add loadCSS polyfill to head inline and outside WP's asset handling
    public function loadcss_init()
    {
        // Get loadCSS-file
        $loadcss_file = $this->plugin_url . 'assets/scripts/loadCSS' . ($this->dev_mode ? '' : '.min') . '.js';
        
        // Fetch content
        $content = file_get_contents($loadcss_file);
        
        // Print out in head
        echo '<script>' . $content . '</script>' . "\n";
        
    }
    
    // Caches the stylesheets that are loaded during a front-end request
    public function cache_stylesheet_handles()
    {
        // Get global var
        global $wp_styles;
        
        // Abort if not queue is set
        if(!isset($wp_styles->queue)) return;
        
        // Parse style to include sub-dependencies
        $parsed_styles = $this->parse_styles($wp_styles->queue);
        
        // Merge styles with any existing and remove any duplicates
        $parsed_styles = $this->merge_styles($parsed_styles);
        
        // Update stylesheet-handles
        update_option($this->plugin_slug . '_stylesheet_handles', $parsed_styles);
        
    }
    
    // Parse style dependencies and add any sub-dependiencies
    public function merge_styles($styles)
    {
        
        // Merge the styles we found know with the ones we have cached
        $styles = array_merge($styles, $this->all_handles);
        
        // Remove any duplicates
        $styles = array_unique($styles);
        
        // Return
        return $styles;
        
    }
    
    // Parse style dependencies and add any sub-dependiencies
    public function parse_styles($raw_styles)
    {
        // Get global var
        global $wp_styles;
        
        // Create style array
        $all_styles = array();
        
        // Get all registered stye dependencies
        $registered = $wp_styles->registered;
        
        // Loop through
        foreach($raw_styles as $style_handle)
        {
            
            // Add to array
            $all_styles[] = $style_handle;
            
            // Check if we got a dependency object
            if(!isset($registered[$style_handle])) continue;
                
            // Get dependency object
            $style_dependency_item = $registered[$style_handle];
            
            // Add sub-dependecies to array
            $all_styles = array_merge($all_styles, $style_dependency_item->deps);
            
        }
        
        // Remove any duplicates
        $all_styles = array_unique($all_styles);
        
        // Return array
        return $all_styles;
        
    }
    
    // Gets all the whitelisted stylesheets from the database
    private function get_whitelisted_stylesheets()
    {   
        // Get whitelisted handles
        $whitelisted_handles = get_option($this->plugin_slug . '_whitelisted_stylesheet_handles', array());
        
        // Get correct format
        $whitelisted_handles = array_keys($whitelisted_handles);
        
        // Update object variable
        $this->whitelisted_handles = $whitelisted_handles;
        
    }
    
    // Gets all cached stylesheet handles
    public function get_cached_stylesheet_handles()
    {
    
        // Get all handles, default empty array
        $all_handles = get_option($this->plugin_slug . '_stylesheet_handles', array());
        
        // Sort alphabetically
        sort($all_handles);
    
        // Set class variable
        $this->all_handles = $all_handles;
     
    }
    
    // Edit stylesheet-inclusion method
    public function custom_style_loader( $html, $handle, $href )
    {
        // We do not touch this stylesheet if not in whitelist array
        if( ! in_array( $handle, $this->whitelisted_handles ) ) return $html;
    
        // Try to catch media-attribute in HTML-tag
        preg_match('/media=\'(.*)\'/', $html, $match);
    
        // Extract media-attribute, default all
        $media = (isset($match[1]) ? $match[1] : 'all');
    
        // Return new markup
        return '<script>loadCSS("' . $href . '",0,"' . $media . '");</script><!-- ' . $handle . '-->' . "\n";
    
    }
    
    /* SETTINGS */
    
    // Register optionspage and settings
    public function add_options_page()
    {
        // Register optionspage
        add_options_page( $this->plugin_name, $this->plugin_name, 'manage_options', $this->plugin_slug, array($this, 'options_page_view'));
        
        // Register settings
        add_action( 'admin_init', array($this, 'register_settings') );
    }
    
    // Register settings
    function register_settings()
    {
        // Register setting for whitelisted stylesheet handles
        register_setting( $this->plugin_slug . '-settings-group', $this->plugin_slug . '_whitelisted_stylesheet_handles' );
    }
    
    // Formview for optionspage
    public function options_page_view()
    {
        // Check if we got any handles
        $has_handles = (is_array($this->all_handles) AND count($this->all_handles) > 0);
        ?>
        
        <div class="wrap" id="wpac-wrapper">
            
            <h2><?php echo $this->plugin_name; ?></h2>
            
            <div id="wpac-donate-box">
                <p class="left-marg"><?php _e('If you found this plugin useful and want to support it you can donate with PayPal here :)', $this->plugin_slug); ?></p>
                <?php $this->donate_button(); ?>
            </div>
            
            <form method="post" action="options.php">
                
                <p><?php _e('Here you can select which stylesheets you want to load asynchronously.', $this->plugin_slug); ?><br>
                <?php if($has_handles) _e('Note: If you cannot find the handle you are looking for please visit your frontpage to update the handle-list.', $this->plugin_slug); ?></p> 
                    
                <?php if($has_handles): ?>
                    
                <?php settings_fields( $this->plugin_slug . '-settings-group' ); ?>
                <?php do_settings_sections( $this->plugin_slug . '-settings-group' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Select handles', $this->plugin_slug); ?></th>
                        <td>
                        	<fieldset>
                                <legend class="screen-reader-text"><span><?php _e('Select handles', $this->plugin_slug); ?></span></legend>
                                <?php foreach($this->all_handles as $handle): ?>
                                <label><input type="checkbox" name="<?php echo $this->plugin_slug; ?>_whitelisted_stylesheet_handles[<?php echo $handle; ?>]" value="1"<?php if(in_array($handle, $this->whitelisted_handles)) echo ' checked="checked"'; ?> /> <span class="date-time-text format-i18n"><?php echo $handle; ?></span></label><br />
                                <?php endforeach; ?>    
                        	</fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
                
                <?php else: ?>
                    
                <p><strong><?php _e('Please navigate to your frontpage to fetch the list of available handles.', $this->plugin_slug); ?></strong></p>
                
                <?php endif; ?>
                
            </form>
            
        </div>

        <?php
    }
    
    // PayPal donate button HTML
    private function donate_button()
    {
        ?>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
        ">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <!--<img alt="" border="0" src="https://www.paypalobjects.com/no_NO/i/scr/pixel.gif" width="1" height="1">-->
        </form>
        <?php
    }
    
}

// Initalize class
new WP_Async_CSS;