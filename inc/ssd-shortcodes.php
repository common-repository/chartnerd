<?php
class CNSD_Shotcodes
{
	private $ssd_apiRequest;
    private $notLoggedInUserMessage;		public function __construct()
    {
    }
    public static function init()
    {
		add_shortcode('refunds_monthly', array(__CLASS__, 'CNSD_refunds_monthly'));
		add_shortcode('monthly_recurring_revenue', array(__CLASS__, 'CNSD_monthly_recurring_revenue'));
    	add_shortcode('CNSD_customers', array(__CLASS__, 'CNSD_customers')); 
        add_shortcode('average_revenue_per_user', array(__CLASS__, 'CNSD_average_revenue_per_user'));
        add_shortcode('annual_recurring_revenue', array(__CLASS__, 'CNSD_annual_recurring_revenue')); 
        add_shortcode('user_churn_rate', array(__CLASS__, 'CNSD_user_churn_rate')); 		

        add_shortcode('paypal_revenue', array(__CLASS__, 'CNSD_paypalRevenue'));
    }

    public static function CNSD_refunds_monthly($atts ,$content = "" ){

        $return = '';
    	
    	extract(shortcode_atts(array(
						'month' =>date('m'),
						'year'  =>date('Y')
			    ), $atts));
    	
    	$args = array();
        $amount = 0;
        $return = $output_filter_text = '';
    	$ssd_apiRequest = new CNSD_SettingsPage();
        $watermark = $ssd_apiRequest->CNSD_chartnerd_watermark();

    	if($month =='' && $year ==''){
    		$trantype = 'all';
            $output_filter_text = 'total';
    	}else{
            
    		$trantype = 'retrieve';
    		$daterange = $ssd_apiRequest->CNSD_monthFirstLastday_timestamp($year,$month); 
    		$args['created'] = array(
    							'gte'=>$daterange['first'],
    								'lte'=>$daterange['last']
    						);
    	}

    	$amount = $ssd_apiRequest->CNSD_getRefunds($args); 
        
        if(is_numeric($amount )){
            $return = '$'. number_format($amount,2);
        }else{
            $return = '$'. number_format(0,2);
        }   

        $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();

        ob_start();
         _e( sprintf('<div style="%s">Monthly Refunds %s: %s '.$watermark.'</div>', $style,$output_filter_text, $return),'cnsd-chartnerd') ;
    	return ob_get_clean();

    }

    public static function CNSD_monthly_recurring_revenue($atts ,$content = "" ){
    	
        $return = '';
        $ssd_apiRequest = new CNSD_SettingsPage();
        $return = $ssd_apiRequest->CNSD_getMMR();
        $watermark = $ssd_apiRequest->CNSD_chartnerd_watermark();
        
        $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();

        ob_start();

        _e( sprintf('<div style="%s">MRR: $%s'.$watermark.'</div>',$style ,number_format($return,2)),'cnsd-chartnerd') ;
    	
    	return ob_get_clean();
    }

    public static function CNSD_customers($atts ,$content = "" ){
        
        $return = '';
        $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();

    	extract(shortcode_atts(array(
						'from' =>'',
						'to'  =>'',
						'limit'=>'all'
			    ), $atts));
        	
    	$args = array();
        $return = '';
    	$ssd_apiRequest = new CNSD_SettingsPage();

    	// time interval
    	if($from !='' && $to !=''){
    		$from_sep = explode('/',$from);
    		$to_sep = explode('/',$to);
    		$from_daterange = $ssd_apiRequest->CNSD_monthFirstLastday_timestamp($from_sep[1],$from_sep[0]);
    		$to_daterange = $ssd_apiRequest->CNSD_monthFirstLastday_timestamp($to_sep[1],$to_sep[0]);
    		
    		$args['created'] =  array(
    								'gte'=>$from_daterange['first'],
    								'lte'=>$to_daterange['last']
    						);
    	}

    	//limit
    	$args['limit'] = $limit;
    	
    	$customers = $ssd_apiRequest->CNSD_getCustomers($args); 
        $watermark = $ssd_apiRequest->CNSD_chartnerd_watermark();

        if(isset($customers)){  

        	ob_start();
            
            if(isset($args['created'])){
                _e(sprintf('<div style="%s">New customers from %s to %s - %d'.$watermark.'</div>',$style,$from,$to,count($customers)),'cnsd-chartnerd');
            }else{
                _e(sprintf('<div style="%s">New customers - %d'.$watermark.'</div>',$style,count($customers)),'cnsd-chartnerd');
            }

            $return = ob_get_clean();
    	
        }else{
             
             _e(sprintf('<div style="%s">No data found'.$watermark.'</div>',$style),'cnsd-chartnerd');
        }
        
    	return $return;
    }

    public static function CNSD_average_revenue_per_user($atts ,$content = "" ){

        $return = '';
        $average_revenue_per_user = 0;

        $ssd_apiRequest = new CNSD_SettingsPage();
        $mmr_res = $ssd_apiRequest->CNSD_getMMR();
        $watermark = $ssd_apiRequest->CNSD_chartnerd_watermark();
        $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();

        $ssd_getCustomers = $ssd_apiRequest->CNSD_getCustomers(array('total_customers'=>1));
        
        if($mmr_res !='' && isset($ssd_getCustomers[0]->total_customers)){

            $average_revenue_per_user =  $mmr_res / $ssd_getCustomers[0]->total_customers;
        }
        
        $return = '$'. number_format(round($average_revenue_per_user,2),2);
       
        ob_start();

        _e( sprintf('<div style="%s">ARPU: %s'.$watermark.'</div>', $style,$return),'cnsd-chartnerd') ;
        
        return ob_get_clean();
        
    }

    public static function CNSD_annual_recurring_revenue($atts ,$content = "" ){

        $return = '';
        $arr_amount = 0;
        $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();
        $ssd_apiRequest = new CNSD_SettingsPage();
        
        $watermark = $ssd_apiRequest->CNSD_chartnerd_watermark();
        $mmr_amount = $ssd_apiRequest->CNSD_getMMR();
        if( isset($mmr_amount) ){
    
           $arr_amount = $mmr_amount * 12;

        }
        $return = '$'. number_format(round($arr_amount,2),2);

        ob_start();

         _e( sprintf('<div style="%s"> ARR: %s '.$watermark.'</div>', $style,$return),'cnsd-chartnerd') ;

        return ob_get_clean();
    }

    public static function CNSD_user_churn_rate($atts ,$content = "" ){

        $return = '';
        $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();

            extract(shortcode_atts(array(
                            'month' =>date('m'),
                            'year'  =>date('Y'),            
                    ), $atts));

            $trantype = 'all';
            
            $return = '';
            $ssd_apiRequest = new CNSD_SettingsPage();
            $watermark = $ssd_apiRequest->CNSD_chartnerd_watermark();

            if($month =='' && $year==''){
                
                return 'Month and Year atts are required'; 
            }
            $daterange = $ssd_apiRequest->CNSD_monthFirstLastday_timestamp($year,$month); 

            $active_subscriptions = $ssd_apiRequest->CNSD_getSubscriptions();
            
                $canceled_subscription_time_filter = array();
                $active_subscription_at_starting   = array();
                $active_subscription_at_ending     = array();
                $customer = array();
                $amount = 0;
                
                if(isset($active_subscriptions)){   
                    foreach($active_subscriptions as $key=>$single_customer){ 
                 
                         if($single_customer->subscription_canceled_at >= $daterange['first'] && $single_customer->subscription_canceled_at <= $daterange['last'] && $single_customer->subscription_status =='canceled' ){
                                $canceled_subscription_time_filter[] = $single_customer->subscription_id;
                         }
                         if($single_customer->subscription_created <= $daterange['first']  && $single_customer->subscription_status =='active'){
                                $active_subscription_at_starting[] =$single_customer->subscription_id;
                          }
                          if($single_customer->subscription_created <= $daterange['last']  && $single_customer->subscription_status =='active'){
                                $active_subscription_at_ending[] = $single_customer->subscription_id;
                          }
                          if($single_customer->subscription_status =='active'){
                            
                            if($single_customer->subscription_plan_interval == 'year'){
                                $amount += $single_customer->subscription_plan_amount /12 ;
                            }elseif($single_customer->subscription_plan_interval == 'month'){
                                $amount += $single_customer->subscription_plan_amount;
                            }

                          }
  
                    } 
                }
                //   echo count($customer).'<pre>'; print_r($customer); echo '</pre>';
              
               $total_canceled_current_month      = count($canceled_subscription_time_filter);
               $total_subscription_at_starting    = count($active_subscription_at_starting);
               $total_subscription_at_ending      = count($active_subscription_at_ending);

                ob_start();
                
        	   if($total_subscription_at_starting == 0 ){
   
                     _e( sprintf('<div style="%s">%s',$style, 'No active subscription before this month'),'cnsd-chartnerd') ;

                }else{
            
                    $net_gain =  $total_subscription_at_ending - $total_subscription_at_starting;
                    $days_in_month          = date('t',$daterange['last']);
                    $customer_days_in_month      = ( $total_subscription_at_starting * 31 )+( 0.5 * $net_gain * 31 );
                    $churn_per_customer_monthly  = ( $total_canceled_current_month / $customer_days_in_month ) * 100 * 31;
                    
                    _e( sprintf('<div style="%s">Monthly Churn: %s - %s '.$watermark.'</div>', $style,date('M Y',$daterange['first']),round($churn_per_customer_monthly,2).'%' ,'cnsd-chartnerd') );
                   
                }

        return ob_get_clean();   
    }	

    public static function CNSD_paypalRevenue($atts ,$content = "" ){		
			$current_year = date('Y');		
            $current_month = date('m');				
            extract(shortcode_atts(array(						
                'from' =>"$current_year-$current_month-01",						
                'to'  =>"$current_year-$current_month-30",			    
            ), $atts));        	    	
            $args = array();        
            $return = '';    	
            $settingspage = new CNSD_SettingsPage();		
            $watermark = $settingspage->CNSD_chartnerd_watermark();		
            $style = CNSD_globalSettings::CNSD_getGeneralSettingsStyle();    	
            if($from !='' && $to !=''){    		
                $from_sep = explode('/',$from);    		
                $to_sep = explode('/',$to);    		
                $from_daterange = "$from_sep[1]-$from_sep[0]-01";    		
                $to_daterange = "$to_sep[1]-$to_sep[0]-30";    	
            }else{			
                $from_daterange = "$current_year-$current_month-01";    		
                $to_daterange = "$current_year-$current_month-30";		
            }		

            ob_start();		
            $paypal_revenue = $settingspage->CNSD_getPaypalRevenue($from_daterange,$to_daterange); 		   
            _e( sprintf('<div style="%s"> %s '.$watermark.'</div>', $style,$paypal_revenue),'cnsd-chartnerd') ;		
            return ob_get_clean();   	
    } 
}
?>