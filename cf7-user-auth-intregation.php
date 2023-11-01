<?php
/**
 * Plugin Name: CF7 UserAuth Integration
 * Description: Integrate user authentication with Contact Form 7 for WordPress.
 * Version: 1.0
 * Author: Ekram Mallick
 * Author URI: http://www.ekrammallick.com
 * Developer: Ekram Mallick
 * Developer E-Mail: ekrammallick18@gmail.com
 * Text Domain: cf7-user-auth
 * Domain Path: /languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CF7_UserAuth_Integration {

    public function __construct() {
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('wpcf7_skip_mail', array($this, 'skip_mail'), 10, 2);
        add_filter('wpcf7_editor_panels', array($this, 'editor_panels'));
        add_action('wpcf7_before_send_mail', array($this, 'create_user_from_registration'), 1, 2);
        add_action('wpcf7_save_contact_form', array($this, 'save_reg_contact_form'));
    }

    public function check_dependencies() {
        require_once(ABSPATH . '/wp-admin/includes/plugin.php');

        if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            deactivate_plugins(plugin_basename(__FILE__));
            add_action('admin_notices', array($this, 'admin_notice'));
        }
    }

    public function admin_notice() {
        echo '<div class="error"><p>Plugin deactivated. Please activate Contact Form 7 plugin!</p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('user-auth-integration', $this->plugin_url('assets/css/style.css'), array(), time(), 'all');
    }

    public function skip_mail($skip_mail, $contact_form) {
        $post_id = sanitize_text_field($_POST['_wpcf7']);
        $enablemail = get_post_meta($post_id, 'enablemail_registration_cf7uai', true);

        if ($enablemail == 1) {
            $skip_mail = true;
        }

        return $skip_mail;
    }

    public function editor_panels($panels) {
        $new_page = array(
            'Error' => array(
                'title' => __('UserAuth Settings', 'contact-form-7'),
                'callback' => array($this, 'admin_additional_settings_callback')
            )
        );

        $panels = array_merge($panels, $new_page);

        return $panels;
    }

    public function admin_additional_settings_callback($cf7) {
       	
	$post_id = sanitize_text_field($_GET['post']);
	$tags = $cf7->scan_form_tags();
	
	$cf7uai_enable = get_post_meta($post_id, "cf7uai_enable_registration", true);
	$cf7uai_user = get_post_meta($post_id, "cf7uai_user", true);
	$cf7uai_email = get_post_meta($post_id, "cf7uai_email", true);
	$cf7uai_pass = get_post_meta($post_id, "cf7uai_pass", true);
	$cf7uai_cpass = get_post_meta($post_id, "cf7uai_cpass", true);
	$cf7uai_ur = get_post_meta($post_id, "cf7uai_ur", true);
    
    $cf7uai_enablemail= get_post_meta($post_id, "enablemail_registration_cf7uai", true);
	$cf7uai_autologinfield = get_post_meta($post_id, "autologinfield_reg_cf7uai", true);
	$cf7uai_loginurlmail = get_post_meta($post_id, "loginurlmail_reg_cf7uai", true);
	$cf7uai_loginurlformail = get_post_meta($post_id, "loginurlformail_reg_cf7uai", true);
	$selectedrole = $cf7uai_ur;
	if(!$selectedrole)
	{
		$selectedrole = 'subscriber';
	}
	if ($cf7uai_enable == "1") { $cf7uai_checked = "CHECKED"; } else { $cf7uai_checked = ""; }
	if ( $cf7uai_enablemail == "1") { $checkedmail = "CHECKED"; } else { $checkedmail = ""; }
	if ($cf7uai_autologinfield== "1") {$cf7uai_autologinfield = "CHECKED"; } else { $cf7uai_autologinfield = ""; }
	if ($cf7uai_loginurlmail == "1") {$cf7uai_loginurlmail = "CHECKED"; } else { $cf7uai_loginurlmail = ""; }
	if ($cf7uai_loginurlformail != "") { $cf7uai_loginurlformail = $cf7uai_loginurlformail; } else { $cf7uai_loginurlformail = ""; }
	
	$selected = "";
	$admin_tab_output = "";
	
		$admin_tab_output .= "<div id='additional_settings-sortables' class='meta-box'><div id='additionalsettingsdiv'>";
			$admin_tab_output .= "<h2 class='hndle ui-sortable-handle'><span>UserAuth Settings:</span></h2>";
			$admin_tab_output .= "<div class='inside'>";
			
			$admin_tab_output .= "<div class='mail-field pretty p-switch p-fill'>";
			$admin_tab_output .= "<input name='cf7uai_enable_registration' value='1' type='checkbox' $cf7uai_checked>";
			$admin_tab_output .= "<div class='state'><label>Enable Registration on this form</label></div>";
			$admin_tab_output .= "</div>";

			$admin_tab_output .= "<div class='mail-field pretty p-switch p-fill'>";
			$admin_tab_output .= "<input name='enablemail_registration_cf7uai' value='' type='checkbox' $checkedmail>";
			$admin_tab_output .= "<div class='state'><label>Skip Contact Form 7 Mails ?</label></div>";
			$admin_tab_output .= "</div>";

			$admin_tab_output .= "<div class='mail-field pretty p-switch p-fill'>";
            $admin_tab_output .= "<input name='autologinfield_reg_cf7uai' value='' type='checkbox' $cf7uai_autologinfield>";
            $admin_tab_output .= "<div class='state'><label>Enable auto login after registration? </label></div>";
            $admin_tab_output .= "</div>";

            $admin_tab_output .= "<div class='mail-field pretty p-switch p-fill'>";
            $admin_tab_output .= "<input name='loginurlmail_reg_cf7uai' value='' type='checkbox' $cf7uai_loginurlmail>";
            $admin_tab_output .= "<div class='state'><label>Enable sent Login URL in Mail. </label></div>";
            $admin_tab_output .= "</div>";

            $admin_tab_output .= "<div class='mail-field'>";
            $admin_tab_output .= "<br/><div class='state'><label>Set Custom Login URL for email :</label></div>";
            $admin_tab_output .= "<input name='loginurlformail_reg_cf7uai' value='".$cf7uai_loginurlformail."' type='text' ><br/>";
            $admin_tab_output .= "</div>";

			$admin_tab_output .= "<table>";

			$admin_tab_output .= "<div class='handlediv' title='Click to toggle'><br></div><h2 class='hndle ui-sortable-handle'><span>Frontend Fields Settings:</span></h2>";

			$admin_tab_output .= "<tr><td>Selected Field Name For User Name :</td></tr>";
			$admin_tab_output .= "<tr><td><select name='cf7uai_user'>";
			$admin_tab_output .= "<option value=''>Select Field</option>";
			foreach ($tags as $key => $value) {
				if($cf7uai_user ==$value['name']){$selected='selected=selected';}else{$selected = "";}			
				$admin_tab_output .= "<option ".$selected." value='".$value['name']."'>".$value['name']."</option>";
			}
			$admin_tab_output .= "</select>";
			$admin_tab_output .= "</td></tr>";
            
			$admin_tab_output .= "<tr><td>Selected Field Name For Email :</td></tr>";
			$admin_tab_output .= "<tr><td><select name='cf7uai_email'>";
			$admin_tab_output .= "<option value=''>Select Field</option>";
			foreach ($tags as $key => $value) {
				if($cf7uai_email==$value['name']){$selected='selected=selected';}else{$selected = "";}
				$admin_tab_output .= "<option ".$selected." value='".$value['name']."'>".$value['name']."</option>";
			}
			$admin_tab_output .= "</select>";
			$admin_tab_output .= "</td></tr>";
			$admin_tab_output .= "<tr><td>";
            //
            $admin_tab_output .= "<tr><td>Selected Field Name For PassWord:</td></tr>";
			$admin_tab_output .= "<tr><td><select name='cf7uai_user'>";
			$admin_tab_output .= "<option value=''>Select Field</option>";
			foreach ($tags as $key => $value) {
				if($cf7uai_pass ==$value['name']){$selected='selected=selected';}else{$selected = "";}			
				$admin_tab_output .= "<option ".$selected." value='".$value['name']."'>".$value['name']."</option>";
			}
			$admin_tab_output .= "</select>";
			$admin_tab_output .= "</td></tr>";
            //
             //
             $admin_tab_output .= "<tr><td>Selected Field Name For Confirm PassWord:</td></tr>";
             $admin_tab_output .= "<tr><td><select name='cf7uai_user'>";
             $admin_tab_output .= "<option value=''>Select Field</option>";
             foreach ($tags as $key => $value) {
                 if($cf7uai_cpass ==$value['name']){$selected='selected=selected';}else{$selected = "";}			
                 $admin_tab_output .= "<option ".$selected." value='".$value['name']."'>".$value['name']."</option>";
             }
             $admin_tab_output .= "</select>";
             $admin_tab_output .= "</td></tr>";
             //

			$admin_tab_output .= "<input type='hidden' name='email' value='2'>";
			$admin_tab_output .= "<input type='hidden' name='post' value='$post_id'>";
			$admin_tab_output .= "</td></tr>";
			$admin_tab_output .= "<tr><td>Selected User Role:</td></tr>";
			$admin_tab_output .= "<tr><td>";
			$admin_tab_output .= "<select name='cf7uai_ur'>";
			$editable_roles = get_editable_roles();
		    foreach ( $editable_roles as $role => $details ) {
		     $name = translate_user_role($details['name'] );
		         if ( $selectedrole == $role ) // preselect specified role
		             $admin_tab_output .= "<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
		         else
		             $admin_tab_output .= "<option value='" . esc_attr($role) . "'>$name</option>";
		    }
		    $admin_tab_output .="</select>";
			$admin_tab_output .= "</td></tr>";
			$admin_tab_output .="</table>";
			$admin_tab_output .= "</div>";
			$admin_tab_output .= "</div>";
		$admin_tab_output .= "</div>";

	echo $admin_tab_output;
    }

    public function create_user_from_registration($cfdata) {
        $post_id = sanitize_text_field($_POST['_wpcf7']);
	$cf7uai_enable = get_post_meta($post_id, "cf7uai_enable_registration", true);
	$cf7uai_user = get_post_meta($post_id, "cf7uai_user", true);
	$cf7uai_email = get_post_meta($post_id, "cf7uai_email", true);
	$cf7uai_pass = get_post_meta($post_id, "cf7uai_pass", true);
	$cf7uai_cpass = get_post_meta($post_id, "cf7uai_cpass", true);
	$cf7uai_ur = get_post_meta($post_id, "cf7uai_ur", true);
    
    $cf7uai_enablemail= get_post_meta($post_id, "enablemail_registration_cf7uai", true);
	$cf7uai_autologinfield = get_post_meta($post_id, "autologinfield_reg_cf7uai", true);
	$cf7uai_loginurlmail = get_post_meta($post_id, "loginurlmail_reg_cf7uai", true);
	$cf7uai_loginurlformail = get_post_meta($post_id, "loginurlformail_reg_cf7uai", true);
	if($cf7uai_enable[0]!=0)
	{
		    if (!isset($cfdata->posted_data) && class_exists('WPCF7_Submission')) {
		        $submission = WPCF7_Submission::get_instance();
		        if ($submission) {
		            $formdata = $submission->get_posted_data();
		        }
		    } elseif (isset($cfdata->posted_data)) {
		        $formdata = $cfdata->posted_data;
		    } 
        $password = $formdata["".$cf7uai_pass.""];
        $cpassword = $formdata["".$cf7uai_cpass.""];
        if($password!=$cpassword){
            return false;
        }
        $email = $formdata["".$cf7uai_email.""];
        $name = $formdata["".$cf7uai_user.""];
        // Construct a username from the user's name
        $username = strtolower(str_replace(' ', '', $name));
        $name_parts = explode(' ',$name);
        if ( !email_exists( $email ) ) 
        {
            // Find an unused username
            $username_tocheck = $username;
            $i = 1;
            while ( username_exists( $username_tocheck ) ) {
                $username_tocheck = $username . $i++;
            }
            $username = $username_tocheck;
            // Create the user
            $userdata = array(
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'nickname' => reset($name_parts),
                'display_name' => $name,
                'first_name' => reset($name_parts),
                'last_name' => end($name_parts),
                'role' => $cf7uai_ur
            );
            $user_id = wp_insert_user( $userdata );
            if ( !is_wp_error($user_id) ) {
                // Email login details to user
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
                $message = "Welcome! Your login details are as follows:" . "\r\n";
                $message .= sprintf(__('Username: %s'), $username) . "\r\n";
                $message .= sprintf(__('Password: %s'), $password) . "\r\n";
                if($cf7uai_loginurlmail ){
                    if($cf7uai_loginurlformail != ""){
                        $message .= $cf7uai_loginurlformail . "\r\n";
                    }else{
                        $message .= wp_login_url() . "\r\n";    
                    }
                }
                wp_mail($email, sprintf(__('[%s] Your username and password'), $blogname), $message);
	        }
            if ($cf7uai_autologinfield == "1" && !is_wp_error($user_id)) {

                $user = get_user_by( 'id', $user_id );

                if( $user ) {
                    wp_set_current_user( $user_id, $user->user_login );
                    wp_set_auth_cookie( $user_id );
                    do_action( 'wp_login', $user->user_login, $user );
                }

            }
	        
	    }

	}
    return $cfdata;
    }

    private function plugin_url($path = '') {
        $url = plugins_url($path, __FILE__);

        if (is_ssl() && substr($url, 0, 5) == 'http:') {
            $url = 'https:' . substr($url, 5);
        }

        return $url;
    }


   public function save_reg_contact_form( $cf7 ) 
    {
    
            $tags = $cf7->scan_form_tags();
    
            
            $post_id = sanitize_text_field($_POST['post_ID']);
            
            if (!empty($_POST['cf7uai_enable_registration'])) {
                $enable = sanitize_text_field($_POST['cf7uai_enable_registration']);
                update_post_meta($post_id, "cf7uai_enable_registration", $enable);
            } else {
                update_post_meta($post_id, "cf7uai_enable_registration", 0);
            }
            if (isset($_POST['enablemail_registration_cf7uai'])) {
                update_post_meta($post_id, "enablemail_registration_cf7uai", 1);
            } else {
                update_post_meta($post_id, "enablemail_registration_cf7uai", 0);
            }
    
            if (isset($_POST['autologinfield_reg_cf7uai'])) {
                update_post_meta($post_id, "autologinfield_reg_cf7uai", 1);
            } else {
                update_post_meta($post_id, "autologinfield_reg_cf7uai", 0);
            }
    
            if (isset($_POST['loginurlmail_reg_cf7uai'])) {
                update_post_meta($post_id, "loginurlmail_reg_cf7uai", 1);
            } else {
                update_post_meta($post_id, "loginurlmail_reg_cf7uai", 0);
            }
    
            if (isset($_POST['loginurlformail_reg_cf7uai'])) {
                update_post_meta($post_id, "loginurlformail_reg_cf7uai", sanitize_text_field($_POST['loginurlformail_reg_cf7uai']));
            } else {
                update_post_meta($post_id, "loginurlformail_reg_cf7uai", "");
            }
    
            $key = "cf7uai_user";
            $vals = sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $vals);
    
            $key = "cf7uai_email";
            $vals = sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $vals);	
    
            $key = "cf7uai_ur";
            $vals = sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $vals);	
            $key = "cf7uai_pass";
            $vals = sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $vals);	
            $key = "cf7uai_cpass";
            $vals = sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $vals);	
    }
}

new CF7_UserAuth_Integration();
