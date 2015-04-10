<?php
/**
 * Plugin Name: Current Promotions Display
 * Description: This nifty WordPress plugin allows you to create and display advertisements during various site interaction sequences like newsletter signup, blog article display, etc. The promotion is shown for a pre-set amount of time, and the interaction sequence continues.
 * Version: 1.0
 * Author: Bobcares 
 * Author URI: http://bobcares.com
 * License: GPL2
 */

//Defining the custom value
define(CUSTOM_PLAN_POST_TYPE, 'thankyou_page');

//calling the hooks
add_action('init', 'thankyou_page_init');
add_action('add_meta_boxes', 'add_thankyou_page_meta_boxes', 10, 2);
add_action('save_post', 'save_thankyou_page_meta');
add_action('wp_head', 'thankyou_page_meta_redirect', 0);

//We are setting the template file
add_filter( 'template_include', 'planPromotionTemplate', 1 );

/*
 * fucntion to display contents in the webpage
* @param null
* @return display contents in a webpage
*/


if (!function_exists('writeLog')) {

	/**
	 * Function to add the plugin log to wordpress log file, added by BDT
	 * @param object $log
	 */
	function writeLog($log, $line = "",$file = "")  {

		if (WP_DEBUG === true) {

			$pluginLog = $log ." on line [" . $line . "] of [" . $file . "]\n";

			if ( is_array( $pluginLog ) || is_object( $pluginLog ) ) {
				print_r( $pluginLog, true );
			} else {
				error_log( $pluginLog );
			}

		}
	}

}


/**
 * Initializing the the plugin
 * @author Bobcares
 */
function thankyou_page_init() {
    register_post_type(CUSTOM_PLAN_POST_TYPE, array(
        'labels' => array(
            'name' => __('Plan Promoter'),
            'label' => __('Plan Promoter'),
            'menu_name' => __('Plan Promoter'),
            'name_admin_bar' => __('Add New Page'),
            'all_items' => __('All Pages'),
            'add_new' => __('Add New'),
            'add_new_item' => __('Add New Promotion'),
            'edit_item' => __('Edit page'),
            'new_item' => __('New page'),
            'view_item' => __('Plan Promoter'),
            'not_found' => __('No Pages found'),
        ),
        'description' => __('Promotion Page template'),
        'public' => true,
        'has_archive' => true,
        'menu_position' => 25,
        'supports' => array('title', 'editor')
    ));
}

/**
 * Adding meta boxes
 * @author Bobcares
 */
function add_thankyou_page_meta_boxes() {
    add_meta_box(
            'thankyou_redirect_delay_box', __('Redirect Delay (seconds)'), 'render_thankyou_redirect_delay_box', CUSTOM_PLAN_POST_TYPE, 'side', 'default'
    );

    add_meta_box(
            'thankyou_redirect_url', __('Redirect URL'), 'render_thankyou_redirect_url', CUSTOM_PLAN_POST_TYPE, 'side', 'default'
    );
}

/**
 * Prints the delay box content.
 * @param WP_Post $post The object for the current post/page.
 * @author Bobcares
 */
function render_thankyou_redirect_delay_box($post) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field('thankyou_redirect_delay_box', 'thankyou_redirect_delay_box_nonce');

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $delay = get_post_meta($post->ID, 'thankyou_redirect_delay', true);

    echo '<label for="thankyou_redirect_delay">';
    __('Time delay before redirect');
    echo '</label> ';
    echo '<input type="text" id="thankyou_redirect_delay" name="thankyou_redirect_delay" value="' . esc_attr($delay) . '" placeholder ="eg: 5" size="25" />';
}

/**
 * Prints the URL box content.
 * @param WP_Post $post The object for the current post/page.
 * @author Bobcares
 */
function render_thankyou_redirect_url($post) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field('thankyou_redirect_url_box', 'thankyou_redirect_url_box_nonce');

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $url = get_post_meta($post->ID, 'thankyou_redirect_url', true);

    echo '<label for="thankyou_redirect_url">';
    __('Redirect URL');
    echo '</label> ';
    echo '<input type="text" id="thankyou_redirect_url" name="thankyou_redirect_url" value="' . esc_attr($url) . '" placeholder ="eg: http://www.example.com" size="25" />';
}

/**
 * When the post is saved, saves our custom data.
 * @param int $post_id The ID of the post being saved.
 * @author Bobcares
 */
function save_thankyou_page_meta($post_id) {

    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */

    // Check if our nonce is set.
    if (!isset($_POST['thankyou_redirect_delay_box_nonce']) || !isset($_POST['thankyou_redirect_url_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['thankyou_redirect_delay_box_nonce'], 'thankyou_redirect_delay_box') || !wp_verify_nonce($_POST['thankyou_redirect_url_box_nonce'], 'thankyou_redirect_url_box')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (isset($_POST['post_type']) && CUSTOM_PLAN_POST_TYPE == $_POST['post_type']) {

        // check edit permission
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {

        // check edit permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }


    // Make sure that it is set.
    if (!isset($_POST['thankyou_redirect_delay']) || !isset($_POST['thankyou_redirect_url'])) {
    	writeLog("The redirection delay and url not set", basename(__LINE__), basename(__FILE__));
    	return;
    }

    // Sanitize user input.
    $delay = sanitize_text_field($_POST['thankyou_redirect_delay']);
    $url = sanitize_text_field($_POST['thankyou_redirect_url']);

    // save the delay only if it is numeric
    $delay = (is_numeric($delay)) ? $delay : '';
    writeLog("The redirection delay is ".$delay, basename(__LINE__), basename(__FILE__));
    
    // Update the meta field in the database.
    update_post_meta($post_id, 'thankyou_redirect_delay', $delay);
    update_post_meta($post_id, 'thankyou_redirect_url', $url);
}

/**
 * Add meta redirect tag
 * @global type $post
 * @author Bobcares
 */
function thankyou_page_meta_redirect() {
    global $post;
    
    // only for thank you pages
    if ($post->post_type == CUSTOM_PLAN_POST_TYPE) {
        $url = get_post_meta($post->ID, 'thankyou_redirect_url', true);
        $delay = get_post_meta($post->ID, 'thankyou_redirect_delay', true);

        // valid delay time and URL are required
        if (!empty($url) && !empty($delay) && is_numeric($delay)) {
            echo '<meta http-equiv="refresh" content="' . $delay . '; url=' . $url . '" />';
        }
    } else {
    	writeLog(" Thank you page redirection is set for  ".CUSTOM_PLAN_POST_TYPE.", current post type is ".$post->post_type, basename(__LINE__), basename(__FILE__));
    }
}

/**
 * Function to specify the template file
 * @author :Bobcares
 * @Date   :03/12/2014
 * @param  :$template_path, path to the template file
 * @return :returns the template file
 */
function planPromotionTemplate( $template_path ) {

    //Setting the template file only if the post type is 'video_mapping'.
    if ( get_post_type() == CUSTOM_PLAN_POST_TYPE ) {
        if ( is_single() ) {

            //Checks if the file exists in the theme first,
            //Otherwise serve the file from the plugin
            if ( $theme_file = locate_template( array ( 'promotionTemplate.php' ) ) ) {
                $template_path = $theme_file;
                writeLog("The promotion display template is within the theme file". "$template_path", basename(__LINE__), basename(__FILE__));
            } else {
                $template_path = plugin_dir_path( __FILE__ ) . '/promotionTemplate.php';
                writeLog("The promotion display template is from the plugin file". "$template_path", basename(__LINE__), basename(__FILE__));
            }
        }
    }
    return $template_path;
}

/**
 * Function for removing the DB entries on plugin deactivation
 * @global type $wpdb, to access the database
 * @return : remove all the entries created by the plugin
 */
function planPromoterDeactivation() {
        global $wpdb;
        
        //Setting the table name
        $table = $wpdb->prefix . "posts";
	$postType = "thankyou_page";

        //Delete any options thats stored also?
        //delete_option('wp_yourplugin_version');
        $wpdb->query("DELETE FROM $table WHERE post_type = '".CUSTOM_PLAN_POST_TYPE."'");
}

add_action('deactivate_current_promotions_display/current_promotions_display.php', 'planPromoterDeactivation' );
