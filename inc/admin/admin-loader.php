<?php

 class CNSD_Admin {

    static $validate_license_url = '/?wp_license_api=1';  

    function __construct() {
        add_action('init',array($this,'CNSD_admin_messages'));
        add_action('admin_head',array($this,'CNSD_adminloadFontfamily'),10);
        // this will create the admin menu page
        add_action('admin_menu', array($this, 'CNSDAdminMenu'));
        //ajax for saving plugin admin settings
        add_action('admin_post_save_cnsd_settings', array($this, 'CNSDHanldeActions'));
        add_action('cnsd_get_tab_content', array($this, 'CNSDLoadTabContent'));
        add_action('wp_ajax_CNSD_getShortvodePreview',array($this,'CNSD_getShortvodePreview'));
        

    }

    function CNSD_admin_messages(){

        if(get_transient('CNSD_admin_notice_license_validate_success') == 'set'){
            add_action( 'admin_notices', array($this,'CNSD_admin_notice_license_validate_success'));
            delete_transient('CNSD_admin_notice_license_validate_success');
        }
        if(get_transient('CNSD_admin_notice_license_validate_error') != ''){
            add_action( 'admin_notices', array($this,'CNSD_admin_notice_license_validate_error'));
            
        }

    }

    function CNSD_adminloadFontfamily(){
        $font_array = array();
        
        foreach(explode(',',CNSD_globalSettings::$googlefonts) as $fonts){
                $font_array[] =  str_replace(' ','+',$fonts);
        }
        if(!empty($font_array)){
            $font_array = implode('|', $font_array);
            echo '<link href="https://fonts.googleapis.com/css?family='.$font_array.'" rel="stylesheet">';
        }
        
    }

        //add setting page link to menu
    function CNSDAdminMenu() {
        $menu = add_menu_page("ChartNerd Settings", "ChartNerd Integration","manage_options", "chartnerd_settings", array($this, 'CNSDSettingsTabs'));

         add_action('admin_print_styles-' . $menu, array($this, 'CNSD_load_css'));

         add_action('admin_print_scripts-' . $menu, array($this, 'CNSD_load_js'));
    }



    function CNSDHanldeActions() {
        global $wpdb;
        if (!empty($_POST['save_post'])) { 
            $settings = $_POST;
            $current_tab_data = get_option($settings['current_tab_id'],true);
            unset($settings['current_tab_id']);
            unset($settings['action']);
            unset($settings['save_post']);

            

            if( isset($settings['CNSD_pro_license_key']) &&  '' != $settings['CNSD_pro_license_key']){

                $validate_args = array('license_key'=>$settings['CNSD_pro_license_key'],'domain'=>site_url());

                if(isset($current_tab_data['CNSD_pro_license_key']) && $current_tab_data['CNSD_pro_license_key'] !='' ){
                    $validate_args['api_request'] =   'validate';
                    
                    if(get_option('api_activation_id',true) != ''){
                        $validate_args['api_activation_id'] =  get_option('api_activation_id',true);
                    }else{
                        $validate_args['api_request'] =   'activate';
                    }
                }else{
                    $validate_args['api_request'] =   'activate';
                }

                $cnsdValidate_keys = $this->CNSD_validate_licenseKey($validate_args);

                if($cnsdValidate_keys === false){

                    $settings['CNSD_pro_license_key'] = '';

                }

            }
            $this->saveOption($_POST['current_tab_id'], $settings);


            $url = $_SERVER['HTTP_REFERER'];
            wp_safe_redirect($url);
            exit();
        }
    }

    
    function CNSDSettingsTabs() {

        if (empty($_GET['tab'])) {
            $current_tab_id = CNSD_globalSettings::$default_tab;
        } else {
            $current_tab_id = $_GET['tab'];
        }

        require_once CNSD_DIR.'inc/admin/admin-page.php';
        return;
    }


    function CNSDLoadTabContent($current_tab_id) {

        $options = $this->getTabSettings($current_tab_id);
        $administratorCredentials = $this->CNSD_administratorWith_chartnedCredentials();
        
        //GET TAB CONTENT
        $tab_content_file = CNSD_DIR . "inc/admin/tab-templates/admin-tab-template-" . $current_tab_id . ".php";

        if (file_exists($tab_content_file)) {

            ob_start(); // start output buffer
            include $tab_content_file;
            $tab_content = ob_get_contents(); // get contents of buffer
            ob_end_clean();

        } else {
            $tab_content = "No Content :)";
        }
        echo $tab_content;
        return;
    }


    function getTabSettings($tab_id = '', $sub_tab = '') {
         

        $settings = array();
        if($tab_id == '') {
            foreach (CNSD_globalSettings::$tabs as $tab_id => $tab_heading) {
                $option_key = self::getTabOptionKey($tab_id);
                $tab_option_data = get_option($option_key);
                if(!empty($tab_option_data)) {
                    $settings = array_merge($settings, $tab_option_data);
                }
            }
        } else {
            $option_key = self::getTabOptionKey($tab_id, $sub_tab);
            $settings = get_option($option_key);
        }
        
        return $settings;
    }


    function getTabOptionKey($tab_id, $sub_tab = '') {

        if ($sub_tab != '') {
            $sub_tab = "_" . $sub_tab;
        }

        return CNSD_globalSettings::$page_id . "_" . $tab_id . $sub_tab . "_options";
    }

    function saveOption($tab_id = '', $data) {

        if($tab_id == '') {
            $tab_id = CNSD_globalSettings::$default_tab;
        }
        $option_key = $this->getTabOptionKey($tab_id);

       update_option($option_key, $data, 'no');
    }

    
    function CNSD_load_css() {
        if (empty($_GET['tab'])) {
            $current_tab_id = CNSD_globalSettings::$default_tab;
        } else {
            $current_tab_id = $_GET['tab'];
        }
        wp_enqueue_style( 'cnsd_admin_colorpicker_css', CNSD_URL . '/assets/css/colorpicker.css' );
        wp_enqueue_style( 'cnsd_admin_ssd_css', CNSD_URL . '/assets/css/ssd_stripe.css' );
        
    }

    function CNSD_load_js() {  
        if (empty($_GET['tab'])) {
            $current_tab_id = CNSD_globalSettings::$default_tab;
        } else {
            $current_tab_id = $_GET['tab'];
        }

        if (strpos($current_tab_id, 'settings')) {

            wp_enqueue_script('cnsd-admin-colorpicker-js', CNSD_URL . "/assets/js/colorpicker.js");
            wp_enqueue_script('cnsd-admin-settings-js', CNSD_URL . "/assets/js/admin-settings.js",array('jquery'),'1.0.0.10',true);

            wp_localize_script('cnsd-admin-settings-js', 'cnsd_admin_settings_js', array(
                                                                'ajax_url' => admin_url( 'admin-ajax.php' ),
                                                                'ajax_nonce' => wp_create_nonce('CNSD_ajax_preview') 
                                                            )
                                );

        }
    }

    function CNSD_getShortvodePreview(){
       
       check_ajax_referer( 'CNSD_ajax_preview', 'sjknjfkd' );

       $shortcode = (string) $_POST['shortcode'];
       if($shortcode !=''){
            $shortcode = str_replace('~','"',$shortcode);

            ob_start();
                echo do_shortcode($shortcode) ;
            $return = ob_get_clean();
            

       }else{
          
         $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();
         if(gettype($style) !== 'string'){
                $style = '';
         }
        
         $return = '<div style="'.$style.'">'. CNSD_globalSettings::$demo_preview_text .CNSD_SettingsPage::CNSD_chartnerd_watermark() .'</div>';
       }

       echo $return;

        wp_die();

    }

    private function CNSD_validate_licenseKey($license_data){

        if(!empty($license_data)){
            $key_data = $this->CNSD_getserver_url().self::$validate_license_url;
            // foreach($license_data as $key=>$data){
            //     //$key_data .= '&'.$key.'='. urlencode($data);
            // }

             $remote = wp_remote_post( $key_data, array(
                        'body'    => $license_data,
                        'timeout' => 60,
                        'method'  => 'POST',
                        'headers' => array(
                            'Accept' => 'application/json'
                        ) )
                    );
            if($remote){
                $remote = json_decode($remote['body']);
                if(isset($remote->download_url) && $remote->download_url != '' ){
                    
                    //update tables if required 
                     $this->CNSD_updateTables();
                    
                    $source_url = $remote->download_url;
                    $desti_url = CNSD_DIR.'/';
                    $newfile = 'tmp_file.zip';
                    
                    if(copy($source_url,$newfile))
                     {
                        $zip = new ZipArchive(); 
                        if ($zip->open($newfile)) { 
                            for ($i = 0; $i < $zip->numFiles; $i++) {
                                
                                if ($zip->extractTo($desti_url, array($zip->getNameIndex($i)))) { 
                                   // echo 'File extracted to ' . $desti_url . $zip->getNameIndex($i);
                                }
                            }
                            $zip->close();
                            // Clear zip from local storage:
                            unlink($desti_url . $newfile);
                        }
                     }

                     if(isset($remote->body->data->activation_id)){
                        update_option('api_activation_id',$remote->body->data->activation_id,'no');
                     }
                     
                     set_transient( 'CNSD_admin_notice_license_validate_success', 'set', 10 );
                     update_meta('cnsd_chartnerd_pvrion','yes','no'); 

                     

                     return true;
              }elseif(isset($remote->download_url) && $remote->download_url == ''){
					              set_transient( 'CNSD_admin_notice_license_validate_success', 'set', 10 );        
              }else{
                    $logger = new CNSD_Logger();
                    $wc_log_path = WP_CONTENT_DIR . "/uploads/chartnerd-logs/chartnerds_logs.log";
                    $logger->lfile($wc_log_path);
                    if($remote->body.$remote->msg =='')
                        $cc_rps = 'License key not found';
                    
                    $logger->lwrite(' Error => ' . stripslashes(json_encode($cc_rps)));
                    set_transient( 'CNSD_admin_notice_license_validate_error',$cc_rps,  20 );
                    
                   // echo '<pre>'; print_r($remote); echo '</pre>';
                   //  die('die');
                    return false;
                }
            }
            return false;
        }else{
            return false;
        }

    }

    public function CNSD_admin_notice_license_validate_success(){
         ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'License validated!', 'cnsd-chartnerd' ); ?></p>
        </div>
        <?php
        
    }
    public function CNSD_admin_notice_license_validate_error(){
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( get_transient('CNSD_admin_notice_license_validate_error'), 'cnsd-chartnerd' ); ?></p>
        </div>
        <?php
        delete_transient('CNSD_admin_notice_license_validate_error');
    }

    public function CNSD_administratorWith_chartnedCredentials(){
         $args = array(
                'fields' => array('id','display_name'),
                'role' => 'administrator',
                'meta_query'   =>
                    array(
                        'key' => 'stripe_keys',
                        'value' => '',
                        'compare' => "!=",

                    ),
            );

           return get_users( $args );        
    }

    public function CNSD_updateTables(){
        global $wpdb;

        $tables = array(
                    $wpdb->prefix . "chartnerd_customers",
                    $wpdb->prefix . "chartnerd_subscription",
                    $wpdb->prefix . "chartnerd_refunds"
                    );
        $user_id = (int)get_current_user_id();
        foreach($tables as $table_name){
            $customers_column = $wpdb->get_results( $wpdb->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s ", $table_name, 'user_id') );
            
            if ( empty( $customers_column ) ) {
               $re = $wpdb->query("ALTER TABLE `$table_name` ADD `user_id` INT(128) NOT NULL");
               $de = $wpdb->query("UPDATE `$table_name` SET `user_id` =  $user_id");
            }
        }
    } 
     
private function CNSD_getserver_url(){
        $url = file_get_contents('https://raw.githubusercontent.com/chartnerds/cnurl/master/url.txt');
        return trim($url);
    }


 }

 new CNSD_Admin();
 
