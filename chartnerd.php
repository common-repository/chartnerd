<?php
   /*
   Plugin Name: ChartNerd
   Plugin URI: www.chartnerds.com
   description: ChartNerd is a data analytics plugin for online marketers and entrepreneurs that would like to display their Stripe revenue and various other metrics on their website for social proof, self-accountability, and marketing.
   Version: 1.1
   Author: Travis R and & Jeff H
   Author URI: http://saaspnr.io/
   License: GPL2
   Text Domain: cnsd-chartnerd
   */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('CNSD_DIR', plugin_dir_path(__FILE__));
define('CNSD_URL', plugin_dir_url(__FILE__));

class CNSD_SettingsPage
{
    
    private $options;
    private $time_conditions;
    private $logger;


    /**
     * Start up
     */
    public function __construct()
    {

        register_activation_hook(   __FILE__, array( $this, 'CNSD_on_activation' ) );
        register_deactivation_hook( __FILE__, array( $this, 'CNSD_on_deactivation') );
        add_action( 'wp_enqueue_scripts', array($this,'CNSD_enqueue_scripts'));    
        add_action( 'init', array( $this, 'CNSD_init_internal' ), 1);
        add_filter( 'query_vars', array( $this, 'CNSD_query_vars') );
        add_action( 'parse_request', array( $this, 'CNSD_parse_request'),12,1 );

        $this->time_conditions = array('gt'=>'>','lt'=>'<','gte'=>'>=','lte'=>'<=');
      
        add_action( 'init', array($this,'CNSD_stripe_api_data'), 10, 0);
        add_action( 'init', array($this,'CNSD_include_requiredFiles'),15,0);
        add_action( 'wp_ajax_CNSD_synchronise_data', array($this,'CNSD_synchronise_data' ));
        add_action( 'wp_ajax_nopriv_CNSD_synchronise_data', array($this,'CNSD_synchronise_data' ));
        add_action( 'init', array('CNSD_Shotcodes','init'), 30);
        add_action( 'wp_footer',array($this,'CNSD_wp_site_loader'));

        if (is_admin()) {
            require_once CNSD_DIR.'inc/admin/admin-loader.php';
        }

        add_action('wp_head',array($this,'CNSD_loadFontfamily'),10);
      
    
    }

   public function CNSD_init_internal()
    {
        add_rewrite_rule( '^stripe-dashboard?', 'index.php?ssdapi=1', 'top' );
       
    }

    public function CNSD_query_vars( $query_vars )
    {
        $query_vars[] = 'ssdapi'; 
        return $query_vars;
    }

    public function CNSD_parse_request( &$wp )
    {
        global $wp_version;
      if ( array_key_exists( 'ssdapi', $wp->query_vars ) ) {
               include 'ssd-template.php';
               exit();
          }elseif ( in_array( 'stripe-dashboard', $wp->query_vars ) ) {
               include 'ssd-template.php';
               exit();
        }
        return;
    }

    public function CNSD_enqueue_scripts(){

        wp_enqueue_style( 'CNSD_stripecss', plugin_dir_url( __FILE__ ) . 'assets/css/ssd_stripe.css',array(),'1.1.0.0' );
        wp_enqueue_script( 'CNSD_stripejs', plugin_dir_url( __FILE__ ) . 'assets/js/ssd_stripe.js', array('jquery'), '1.0.0', true );
        // Localize the script with new data
        $translation_array = array(
                                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                                'ajax_nonce' => wp_create_nonce('CNSD_ajax_check'),
                            );
        wp_localize_script( 'CNSD_stripejs', 'ssd_object', $translation_array );
    }

    public function CNSD_stripe_api_data()
    {      
        if( isset($_REQUEST['stripe_keys'] ) && (  isset( $_POST['ssd_stripe_keys_nonce_field'] ) ||  wp_verify_nonce( $_POST['ssd_stripe_keys_nonce_field'], 'ssd_stripe_keys_nonce' )) ) {
            if( is_user_logged_in() ){

                $current_user_id  = intval(get_current_user_id());

                if(isset($_REQUEST['stripe_keys']['apikey']) && $_REQUEST['stripe_keys']['apikey'] !='' ){
                    $updated_api = false;
                    update_user_meta($current_user_id,'stripe_old_keys',get_user_meta($current_user_id, 'stripe_keys',true));

                    if( get_user_meta($current_user_id, 'stripe_keys',true) == null){

                           $wc_log_path = WP_CONTENT_DIR . "/uploads/chartnerd-logs/chartnerds_logs.log";
                            if (!file_exists($wc_log_path) ) { 

                                if(!is_dir(WP_CONTENT_DIR . "/uploads/chartnerd-logs/")){
                                    mkdir(WP_CONTENT_DIR . "/uploads/chartnerd-logs/", 0777, true);
                                }
                                if (is_writable(WP_CONTENT_DIR . "/uploads/chartnerd-logs/")) {
                                    touch($wc_log_path);
                                }
                            }
                            $updated_api = true;
               
                    }else{


                        // stripe api check before deleting the data
                        $keys = unserialize( get_user_meta($current_user_id,'stripe_keys',true) );
                        if(isset($keys['apikey']) && $keys['apikey'] != $_REQUEST['stripe_keys']['apikey'] ){

                            //remove old api webhooks
                            add_action('init',array($this,'CNSD_webhookendpoinds_delete'),15);

                            global $wpdb;
                            $dbtables = array("{$wpdb->prefix}chartnerd_subscription","{$wpdb->prefix}chartnerd_customers","{$wpdb->prefix}chartnerd_refunds");
                            foreach ($dbtables as $key => $value) {
                               if($wpdb->get_var("show tables like '". $value . "'") === $value){
                                    $wpdb->query("DELETE FROM $value");
                                }
                            }
                            $keys = @unserialize( get_user_meta($current_user_id,'stripe_keys',true));
                            if($_REQUEST['stripe_keys']['apikey'] != $keys['apikey']){
                                    $updated_api = true;
                            }
                        }
                        
                    }

                    $keys = $this->CNSD_recursive_sanitize_text_field($_REQUEST['stripe_keys']);
                    update_user_meta( $current_user_id,'stripe_keys', serialize($keys));

                    //add new API webhooks if API key is not same as old one
                    if($updated_api == true ){
                        add_action('init',array($this,'CNSD_webhookendpoinds_init'),50);
                    }
                }

            }
        }
    }

    public function CNSD_include_requiredFiles()
    {   
        require_once( plugin_dir_path( __FILE__ ) . 'stripephp/init.php' );
        include_once( plugin_dir_path( __FILE__ ) . 'ssd-settings.php' );
        include_once( plugin_dir_path( __FILE__ ) . 'inc/ssd-shortcodes.php' );
        include_once( plugin_dir_path( __FILE__ ) . 'inc/ssd-logger.php' );
    }

    public function CNSD_check_userAccess(){
        $keys = unserialize( get_option('stripe_keys') );
        if(isset($keys['apikey']) && $keys['apikey'] !='' && isset($keys['apiprikey']) && $keys['apiprikey'] !=''){
            return true;
        }else{
            return false;
        }
    }

    public function CNSD_get_userStripeKeys()
    {   
        $current_user_id = $this->CNSD_isLoggedUser_notAdministrator();
        $keys = unserialize( get_user_meta($current_user_id,'stripe_keys',true));
        if(isset($keys['apikey']) && $keys['apikey'] !='' && isset($keys['apiprikey']) && $keys['apiprikey'] !=''){
            return $keys;
        }else{
            return false;
        }
    }

    public function CNSD_get_userStripe_OldKeys()
    {   
        $current_user_id = $this->CNSD_isLoggedUser_notAdministrator();
        $keys = unserialize( get_user_meta($current_user_id,'stripe_old_keys',true));
        if(isset($keys['apikey']) && $keys['apikey'] !='' && isset($keys['apiprikey']) && $keys['apiprikey'] !=''){
            return $keys;
        }else{
            return false;
        }
    }

    public function CNSD_set_stripAPI() {

        
        $keys = $this->CNSD_get_userStripeKeys(); 
        if($keys['apikey'] !='' ){
            \Stripe\Stripe::setApiKey($keys['apikey']); 

            return true;
        }else{ 
            return false;
        }
    }

    public function CNSD_set_stripeAPI_oldKeys(){
        
        $keys = $this->CNSD_get_userStripe_OldKeys(); 

        if($keys['apikey'] !='' ){
            \Stripe\Stripe::setApiKey($keys['apikey']); 

            return true;
        }else{ 
            return false;
        }
    }

    public function CNSD_webhookendpoinds_init()
    {
        //creating new API events

       $parms =  array(
              "url" => site_url().'/wp-content/plugins/chartnerd/inc/ssd-events.php',
              "enabled_events" => array("customer.created", "customer.updated","customer.deleted","customer.subscription.created","customer.subscription.deleted","customer.subscription.updated","charge.refunded","charge.refund.updated")
            );
        $webhook_response = $this->CNSD_get_stripeApiData('webhookendpoint','create',$parms );
         
        if(isset($webhook_response->id)){
            update_option('webhookendpoint',$webhook_response->id);
        }
    }

    public function CNSD_webhookendpoinds_delete()
    { 
      //  deleting old events
        set_transient( 'cnsd_useOldStripeKeys', '1', 5 );
        if(get_option('webhookendpoint',true) == true){
                $webhook_id = get_option('webhookendpoint',true);
                $webhook_retrieve = $this->CNSD_get_stripeApiData('webhookendpoint','retrieve',$webhook_id);
                
                if(gettype($webhook_retrieve) === 'object'){ 
                    $webhook_retrieve->delete();
                }

                return true;
        }else{
                return false;
        }
        
    }

    public function CNSD_get_stripeApiData($case='',$trantype='',$parms)
    {
       
       if(get_transient( 'cnsd_useOldStripeKeys') === false){
         $set_stripeAPI = 'CNSD_set_stripAPI';
       }else{
         $set_stripeAPI = 'CNSD_set_stripeAPI_oldKeys';
         delete_transient('cnsd_useOldStripeKeys');
       }

        if( $this->$set_stripeAPI() == true){
 
            if( ( $trantype !='all' || $trantype !='retrieve' ) && $trantype ==''){
                 $trantype = 'all';
            }

            switch ($case) {
                case 'balance':
                    if(  $trantype == 'all' ){
                        $apiUrl =  "\Stripe\BalanceTransaction";
                        return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    }else{ 
                        $apiUrl =  "\Stripe\Balance";
                        return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    }
                    break;
                case 'charge': 
                    $apiUrl =  "\Stripe\Charge";
                    return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    break;
                case 'customer':
                    $apiUrl =  "\Stripe\Customer";
                    return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    break;
                case 'dispute':
                    $apiUrl =  "\Stripe\Dispute";
                    return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    break;
                case 'refund':
                    $apiUrl =  "\Stripe\Refund";
                    return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    break;
                case 'subscription' :
                    $apiUrl = "\Stripe\Subscription";
                    return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    break; 
                case 'webhookendpoint' : 
                    $apiUrl = "\Stripe\WebhookEndpoint";
                    return $this->CNSD_make_stripeRequest($apiUrl,$trantype,$parms);
                    break; 

                default:
                    return $case." case not found";
                    break;
            }

        }else{
            _e('No API key Found!!','cnsd-chartnerd');
        }
    }

    public function CNSD_make_stripeRequest($apiUrl,$transtype,$parms){
        
        if(isset($apiUrl) && $apiUrl != null){  

            try {  
                return  $apiUrl::$transtype($parms); 
            } catch(\Stripe\Error\Card $e) {
              echo $e->getMessage();
              $body = $e->getJsonBody();
              $err  = $body['error'];

              $res = 'Status is:' . $e->getHttpStatus() . "\n";
              $res .= 'Type is:' . $err['type'] . "\n";
              $res .= 'Code is:' . $err['code'] . "\n";
              
              $res .= 'Param is:' . $err['param'] . "\n";
              $res .= 'Message is:' . $err['message'] . "\n";
              return $res;

            } catch (\Stripe\Error\RateLimit $e) {
                echo $e->getMessage();
                return "Too many requests made to the API too quickly";
            } catch (\Stripe\Error\InvalidRequest $e) {
                echo $e->getMessage();
                return "Invalid parameters were supplied to Stripe's API";
            } catch (\Stripe\Error\Authentication $e) {
                echo $e->getMessage();
                return "Authentication with Stripe's API failed";
            } catch (\Stripe\Error\ApiConnection $e) {
                echo $e->getMessage();
                return "Network communication with Stripe failed";
            } catch (\Stripe\Error\Base $e) {
               echo $e->getMessage();
              return "Display a very generic error to the user, and maybe send";

            } catch (Exception $e) {
                return "Something else happened, completely unrelated to Stripe";
            }
        }else{
            return false;
        }
    }

    public function CNSD_monthFirstLastday_timestamp($year,$month)
    {
        $return = array();
        if(isset($month) && $month !='' && isset($year) && $year !=''){
            $return['first'] = strtotime( date('Y-m-d',strtotime("$year-$month-01")) );
            $return['last']  = strtotime( date('Y-m-t',strtotime("$year-$month-01")) );
        }
        return $return;
    }

    public function CNSD_getMMR(){
        
        global $wpdb;
        $current_user_id = $this->CNSD_isLoggedUser_notAdministrator();
        $return = 0; 

        $results = $wpdb->get_results($wpdb->prepare("SELECT 
                        sum((CASE
                            WHEN subscription_percent_off != '' THEN (subscription_plan_amount - (subscription_plan_amount * subscription_percent_off /100))
                            WHEN subscription_discount != '' THEN (subscription_plan_amount-subscription_discount)
                            ELSE subscription_plan_amount
                            END)/100) AS mrr_amount 
                        FROM {$wpdb->prefix}chartnerd_subscription WHERE subscription_status ='%s' AND user_id='%s'",'active',$current_user_id) );
        $return  = round($results[0]->mrr_amount,2);
    
        return $return;
    }

    public function CNSD_getCustomers($args){

        global $wpdb;
        $current_user_id = $this->CNSD_isLoggedUser_notAdministrator();
        $return = 0;

        if(isset($args) && isset($args['total_customers']) && $args['total_customers']==true){ 
             $qry = "SELECT count(customer_id) as total_customers FROM {$wpdb->prefix}chartnerd_customers WHERE user_id='".$current_user_id."'";
        }else{

            $qry = "SELECT * FROM {$wpdb->prefix}chartnerd_customers ";

            if(!empty($args)){
                if(isset($args['created']) && is_array($args['created']) && !empty($args['created']) ) {
                    
                    $qry .= ' WHERE ';
                    $condition_type_array = array();
                    foreach($args['created'] as $key=>$date){
                        $condition_type = $this->time_conditions[$key];

                         $condition_type_array[] .= $wpdb->prepare(" customer_created $condition_type %d",$date);
                    }
                    if(!empty($condition_type_array)){
                        $qry .= implode(' AND ', $condition_type_array);
                    }

                }
                $qry .= " AND user_id='".$current_user_id."' ";
                if(isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ){
                        $qry .= $wpdb->prepare(" LIMIT %d,%d",0,$args['limit']);
                        
                }
            }
        }

        $results = $wpdb->get_results($qry);
        return $results;

    }

    public function CNSD_getRefunds($args){

        global $wpdb;
        $current_user_id = $this->CNSD_isLoggedUser_notAdministrator();
        $amount = 0;

        $qry = "SELECT SUM(refund_amount)/100 as amount FROM {$wpdb->prefix}chartnerd_refunds WHERE user_id='".$current_user_id."'";

        if(!empty($args)){
            if(isset($args['created']) && is_array($args['created']) && !empty($args['created']) ) {
                $qry .= " WHERE ";
                $condition_type_array  = array();
                foreach($args['created'] as $key=>$date){
                    $condition_type = $this->time_conditions[$key];
                    $condition_type_array[] = $wpdb->prepare(" refund_created $condition_type %d",$date);
                }
                if(!empty($condition_type_array)){
                    $qry .= implode(' AND ', $condition_type_array);
                }

            }
            $qry .= " AND user_id='".$current_user_id."' ";
            if(isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ){
                $qry .= $wpdb->prepare(" LIMIT %d,%d",0,$args['limit']);
                    
            }
        }
        
        $refunds = $wpdb->get_results($qry);
   
        if(isset($refunds[0]->amount)){
            $amount = $refunds[0]->amount;
        }
        return $amount;
    }

    public function CNSD_getSubscriptions(){

        global $wpdb;
        $qry = "SELECT * FROM {$wpdb->prefix}chartnerd_subscription WHERE user_id='".$current_user_id."'";
        $results = $wpdb->get_results($qry);
        return $results;
    }


    public function CNSD_recursive_sanitize_text_field($array) {
        foreach ( $array as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = $this->CNSD_recursive_sanitize_text_field($value);
            }
            else {
                $value = sanitize_text_field( $value );
            }
        }
        return $array;
    }

    public function CNSD_on_activation(){

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
 
        $sql = array();

        //stripe customers table
        $chartnerd_customers = $wpdb->prefix . "chartnerd_customers";
        if( $wpdb->get_var("show tables like '". $chartnerd_customers . "'") !== $chartnerd_customers ) { 

            $sql[] = "CREATE TABLE ". $chartnerd_customers . "     (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(128) NOT NULL,
            customer_id varchar(128) UNIQUE NOT NULL,
            customer_created varchar(128) NOT NULL,
            customer_email varchar(500) NOT NULL,
            delinquent varchar(5) NOT NULL,
            PRIMARY KEY  (id)
            ) ";
        }

       //stripe subscription table
        $chartnerd_subscription = $wpdb->prefix . "chartnerd_subscription";
        if( $wpdb->get_var("show tables like '". $chartnerd_subscription . "'") !== $chartnerd_subscription ) { 

            $sql[] = "CREATE TABLE ". $chartnerd_subscription . "   (
            subscription_id varchar(128) NOT NULL UNIQUE,
            user_id int(128) NOT NULL,
            customer_id varchar(128) NOT NULL,
            subscription_created varchar(128) NOT NULL,
            subscription_canceled_at varchar(500),
            subscription_discount varchar(50),
            subscription_percent_off varchar(50),
            subscription_start varchar(50) NOT NULL,
            subscription_status varchar(50) NOT NULL,
            subscription_quantity int(50) NOT NULL,
            subscription_plan_id varchar(50) NOT NULL,
            subscription_plan_active varchar(50) NOT NULL,
            subscription_plan_amount varchar(50) NOT NULL,
            subscription_plan_interval varchar(50) NOT NULL,
            PRIMARY KEY  (subscription_id)
            ) ";
        }

        //stripe refund table
        $chartnerd_refunds = $wpdb->prefix . "chartnerd_refunds";
        if( $wpdb->get_var("show tables like '". $chartnerd_refunds . "'") !== $chartnerd_refunds ) { 

            $sql[] = "CREATE TABLE ". $chartnerd_refunds . "   (
            id varchar(128) NOT NULL ,
            user_id int(128) NOT NULL,
            refund_amount varchar(128) NOT NULL,
            refund_charge varchar(128),
            refund_created varchar(128) NOT NULL,
            refund_status varchar(500) NOT NULL,
            PRIMARY KEY  (id)
            ) ";
         }
        
        if ( !empty($sql) ) {

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql);
            add_option("CNSD_db_version", 2);

        }


    }

    public function CNSD_on_deactivation(){

        
    }

    public function CNSD_synchronise_data() {
        global $wpdb; 

        if( is_user_logged_in() ){
            check_ajax_referer( 'CNSD_ajax_check', 'ZpbeVLU7f' );

                $last_id = sanitize_text_field($_POST['last_id']);

                if(isset($_POST['reqtype']) && $_POST['reqtype'] !=''){
                    $args = array('last_id'=>$last_id,'reqtype'=>$_POST['reqtype']);
                }else{
                    $args = array('last_id'=>$last_id);
                }
                $reqtype = $_POST['reqtype'];
                $return  = $this->CNSD_synchronise_CustSubsDis($args) ;
            
                echo json_encode($return);
               
        }else{
               echo 'User not logged in';
        }
        wp_die(); 
    }

    public function CNSD_synchronise_CustSubsDis($args=array()){

        $current_user_id  = $this->CNSD_isLoggedUser_notAdministrator();

        $return = array(); 
        $format = '';

        if( $this->CNSD_set_stripAPI() == true){

            global $wpdb;
            $wpdb->hide_errors();

            $this->logger = new CNSD_Logger();

            $wc_log_path = WP_CONTENT_DIR . "/uploads/chartnerd-logs/chartnerds_logs.log";
            $this->logger->lfile($wc_log_path);
            
            if(!empty($args) && isset($args['last_id']) && $args['last_id'] !=''){

                $params = array( 
                        'starting_after' => $args['last_id'],
                        'limit'=>100,
                        'include[]'=>'total_count'
                    );
            }else{
                $params = array( 
                        'limit'=>100,
                        'include[]'=>'total_count'
                    );
            }

            if(isset($args['reqtype']) && $args['reqtype'] !=''){
                $reqtype = $args['reqtype'];
                if($reqtype == 'customer'){

                    $format = array('%s','%d','%s','%s','%s');
                }elseif ($reqtype == 'subscription') {
                   
                    $format = array('%s','%d','%s','%d','%d','%s','%s','%s','%s','%d','%s','%s','%s','%s');
                }
            }else{
                $reqtype = 'customer';
                
                $format = array('%s','%d','%s','%s','%s');
            }

            $customers = $this->CNSD_get_stripeApiData($reqtype,'all',$params); 
            
            $customer_count=0; $c='';
            $customer_data = $error = $total_counts = array();
            $total_counts = $customers->total_count;
            foreach ($customers->data as $key=>$customer){
                if($reqtype == 'customer'){ $c = $customer;
      
                    $sql = "INSERT INTO {$wpdb->prefix}chartnerd_customers (`customer_id`,`customer_created`,`customer_email`,`delinquent`,`user_id`) VALUES ('%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE customer_created = '%s',delinquent = '%s'";
                    
                   $sql = $wpdb->prepare($sql,$customer->id,$customer->created,$customer->email,$customer->delinquent,$current_user_id ,$customer->created,$customer->delinquent);
                    
                    $result = $wpdb->query($sql);
                    
                    if($wpdb->last_error !== '') {
                            $error[] = $wpdb->last_error .'sql-'.$sql;
                            $this->logger->lwrite(' => ' . stripslashes($wpdb->last_error));
                    }

                }elseif ($reqtype == 'subscription') {
                    //if($customer->canceled_at){   print_r($customer); }

                    if(isset($customer->discount->coupon->amount_off)){ $discount = $customer->discount->coupon->amount_off; }else{ $discount=''; }
                    if(isset($customer->discount->coupon->percent_off)){ $percent_off = $customer->discount->coupon->percent_off; }else{ $percent_off=''; }
                  
                    $sql = "INSERT INTO {$wpdb->prefix}chartnerd_subscription (`subscription_id`,`user_id`,`customer_id`,`subscription_created`,`subscription_canceled_at`,`subscription_discount`,`subscription_percent_off`,`subscription_start`,`subscription_status`,`subscription_quantity`,`subscription_plan_id`,`subscription_plan_active`,`subscription_plan_amount`,`subscription_plan_interval`) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE subscription_plan_amount = '%s', subscription_discount = '%s', subscription_percent_off = '%s'";
                    
                    $sql = $wpdb->prepare($sql,$customer->id,$current_user_id,$customer->customer,$customer->created,$customer->canceled_at,$discount,$percent_off,$customer->start,$customer->status,$customer->quantity,$customer->plan->id,$customer->plan->active,$customer->plan->amount,$customer->plan->interval,$customer->plan->amount,$discount,$percent_off);
                    
                    $result = $wpdb->query($sql);
                    if($wpdb->last_error !== '') {
                            $error[] = $wpdb->last_error.'sql-'.$sql;
                            $this->logger->lwrite(' => ' . stripslashes($wpdb->last_error));
                    }

                }elseif ($reqtype == 'refund') {

                    $sql = "INSERT INTO {$wpdb->prefix}chartnerd_refunds (`id`,`user_id`,`refund_amount`,`refund_charge`,`refund_created`,`refund_status`) VALUES ('%s','%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE refund_amount = '%s', refund_created = '%s'";
                    
                    $sql = $wpdb->prepare($sql,$customer->id,$current_user_id,$customer->amount,$customer->charge,$customer->created,$customer->status,$customer->amount,$customer->created);
                    
                    $result = $wpdb->query($sql);
                    if($wpdb->last_error !== '') {
                            $error[] = $wpdb->last_error.'sql-'.$sql;
                            $this->logger->lwrite('refund => ' . stripslashes($wpdb->last_error));
                    }

                }
              $customer_count++;  
            }

            $customer_data = array(); 
            while ($customers->has_more){

              $customers = $this->CNSD_get_stripeApiData($reqtype,'all',array("limit" => 100, "starting_after" => $customer->id,'include[]'=>'total_count'));
              
              foreach ($customers->data as $customer){
                 if($reqtype == 'customer'){
                    
                    $sql = "INSERT INTO {$wpdb->prefix}chartnerd_customers (`customer_id`,`user_id`,`customer_created`,`customer_email`,`delinquent`) VALUES ('%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE customer_created = '%s',delinquent = '%s'";
                    $sql = $wpdb->prepare($sql,$customer->id,$current_user_id,$customer->created,$customer->email,$customer->delinquent,$customer->created,$customer->delinquent);
                    
                    $result = $wpdb->query($sql);
                    if($wpdb->last_error !== '') {
                            $error[] = $wpdb->last_error.'sql-'.$sql;
                            $this->logger->lwrite(' => ' . stripslashes($wpdb->last_error)); 
                    }
                
                }elseif ($reqtype == 'subscription') {  
                   // if($customer->canceled_at){   print_r($customer); }

                    if(isset($customer->discount->coupon->amount_off)){ $discount = $customer->discount->coupon->amount_off; }else{ $discount=''; }
                    if(isset($customer->discount->coupon->percent_off)){ $percent_off = $customer->discount->coupon->percent_off; }else{ $percent_off=''; }
                    
                     $sql = "INSERT INTO {$wpdb->prefix}chartnerd_subscription (`subscription_id`,`user_id`,`customer_id`,`subscription_created`,`subscription_canceled_at`,`subscription_discount`,`subscription_percent_off`,`subscription_start`,`subscription_status`,`subscription_quantity`,`subscription_plan_id`,`subscription_plan_active`,`subscription_plan_amount`,`subscription_plan_interval`) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE subscription_plan_amount = '%s', subscription_discount = '%s', subscription_percent_off = '%s'";
                    
                    $sql = $wpdb->prepare($sql,$customer->id,$current_user_id,$customer->customer,$customer->created,$customer->canceled_at,$discount,$percent_off,$customer->start,$customer->status,$customer->quantity,$customer->plan->id,$customer->plan->active,$customer->plan->amount,$customer->plan->interval,$customer->plan->amount,$discount,$percent_off);
                    
                    $result = $wpdb->query($sql);
                    if($wpdb->last_error !== '') {
                            $error[] = $wpdb->last_error.'sql-'.$sql;
                            $this->logger->lwrite(' => ' . stripslashes($wpdb->last_error));
                    }

                }elseif ($reqtype == 'refund') {

                    $sql = "INSERT INTO {$wpdb->prefix}chartnerd_refunds (`id`,`user_id`,`refund_amount`,`refund_charge`,`refund_created`,`refund_status`) VALUES ('%s','%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE refund_amount = '%s', refund_created = '%s'";
                    
                    $sql = $wpdb->prepare($sql,$customer->id,$current_user_id,$customer->amount,$customer->charge,$customer->created,$customer->status,$customer->amount,$customer->created);
                    
                    $result = $wpdb->query($sql);
                    if($wpdb->last_error !== '') {
                            $error[] = $wpdb->last_error.'sql-'.$sql;
                            $this->logger->lwrite('refund => ' . stripslashes($wpdb->last_error));
                    } 

                }
                $customer_count++;
              }


              if($customer_count==300 || $customers->has_more == false){
                return array('last_id'=>$customer->id, 'has_more'=>$customers->has_more,'reqtype'=>$reqtype,'count'=>$customer_count,'error'=>$error,'total_counts'=>$total_counts);
              }

            }
        }
    }

    public static function CNSD_chartnerd_watermark(){
        
        $w = sprintf(PHP_EOL.' <a class="cnsd_watermark" style="font-size:0.6em;display:block;color:#78818f;" target="_blank" href="https://wordpress.org/plugins/chartnerd/">%s<span style="color: #df83d0;"> %s</span><span style="color: #5b60d5;">%s</span></a>','financials by','Chart','Nerds');
        return $w;
    }

    public function CNSD_wp_site_loader()
    { echo 'dddd'. $this->CNSD_isLoggedUser_notAdministrator(); ?>
        <div class="ssd_wp_site_loader_wrapper" style="display: none;">
            <div class="ssd_wp_site_loader"></div>
            <div class="ssd_wp_myprogress_wrapper" style="display: none;">
                <h3></h3>
                <div class="ssd_wp_myprogress">
                  <div class="sd_wp_mybar" id="sd_wp_mybar"></div>
                </div>
            </div>
        </div>
    <?php
    }

    public function CNSD_getDefaultSettings(){
        $defalt_option_key = CNSD_globalSettings::$page_id.'_'.CNSD_globalSettings::$default_tab.'_options';
        return get_option($defalt_option_key,true);

    }

    public function CNSD_loadFontfamily(){
         $font_array = array();
        
        foreach(explode(',',CNSD_globalSettings::$googlefonts) as $fonts){
                $font_array[] =  str_replace(' ','+',$fonts);
        }
        if(!empty($font_array)){
            $font_array = implode('|', $font_array);
            echo '<link href="https://fonts.googleapis.com/css?family='.$font_array.'" rel="stylesheet">';
        }
    }
    
    public function CNSD_getPaypalRevenue($from_daterange,$to_daterange){
        $from_daterange = $from_daterange.'T05:38:48Z';
        $to_daterange   = $to_daterange.'T05:38:48Z';
        
        $keys = $this->CNSD_get_userStripeKeys(); 
        
        if(isset($keys['paypal_username']) && $keys['paypal_username'] != '' && isset($keys['paypal_passsword']) && $keys['paypal_passsword'] != '' && isset($keys['paypal_signature']) && $keys['paypal_signature'] != ''){
                
            $info = 'USER='.$keys["paypal_username"].'&PWD='.$keys["paypal_passsword"] .'&SIGNATURE='.$keys["paypal_signature"].'&METHOD=TransactionSearch&TRANSACTIONCLASS=RECEIVED&STARTDATE='.$from_daterange.'&ENDDATE='.$to_daterange.'&VERSION=94';                   

            $curl = curl_init('https://api-3t.paypal.com/nvp');
            curl_setopt($curl, CURLOPT_FAILONERROR, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($curl, CURLOPT_POSTFIELDS,  $info);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);

            $result = curl_exec($curl);

            # Bust the string up into an array by the ampersand (&)
            # You could also use parse_str(), but it would most likely limit out
            $result = explode("&", $result);

            # Loop through the new array and further bust up each element by the equal sign (=)
            # and then create a new array with the left side of the equal sign as the key and the right side of the equal sign as the value
            
            $final_r=0;
            foreach($result as $value){
                $value = explode("=", $value);
                $temp[$value[0]] = urldecode($value[1]);

                if(preg_match('/L_NETAMT/',$value[0])){
                    $final_r += urldecode($value[1]);   
                }
            }
            
            return ('Paypal Revenue: ' . round($final_r,2));
        }else{
            return ('Paypal credentials not found!');
        }
        
        
    }

    public function CNSD_isLoggedUser_notAdministrator(){
        
        if(get_option('cnsd_chartnerd_pvrion',true) !== false ){
        
            if(is_user_logged_in()){
                $current_user_id  = intval(get_current_user_id());
            }else{
                $options = $this->CNSD_getDefaultSettings();
                if(isset($options['CNSD_default_userid_notloggedin']) && '' != $options['CNSD_default_userid_notloggedin']){
                       $current_user_id =  $options['CNSD_default_userid_notloggedin'];
                }else{
                    $current_user_id = $this->CNSD_get_Admini_id();
                }
            }
        }else{
            $current_user_id = $this->CNSD_get_Admini_id();
        }
        return  $current_user_id ;
    }

    public function CNSD_get_Admini_id(){
        $args = array(
                'fields' => 'ids',
                'role' => 'administrator',
                'number' => 1,
                'meta_query'   =>
                    array(
                        'key' => 'stripe_keys',
                        'value' => '',
                        'compare' => "!=",

                    ),
            );

            $users = get_users( $args ); 
            if(isset($users[0]))
                return $users[0];
            else
                return '';
    }

    

}

$settings_page = new CNSD_SettingsPage();
