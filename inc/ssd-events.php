<?php
// Retrieve the request's body and parse it as JSON:
$input = @file_get_contents('php://input');
$event_json = json_decode($input);
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );
//&& isset($event_json->livemode) && $event_json->livemode == true 
if(isset($event_json->type) && isset($event_json->livemode) && $event_json->livemode == true){
	global $wpdb;

	switch ($event_json->type) {
		case 'customer.created':
			$chartnerd_customers = $wpdb->prefix . "chartnerd_customers";
			if( $wpdb->get_var("show tables like '". $chartnerd_customers . "'") === $chartnerd_customers ) { 

				$customer = $event_json->data->object; 
				$sql = "INSERT INTO {$wpdb->prefix}chartnerd_customers (`customer_id`,`customer_created`,`customer_email`,`delinquent`) VALUES ('%s','%s','%s','%s') ON DUPLICATE KEY UPDATE customer_created = '%s',delinquent = '%s'";
                    
                $sql = $wpdb->prepare($sql,$customer->id,$customer->created,$customer->email,$customer->delinquent,$customer->created,$customer->delinquent);
                
                $result = $wpdb->query($sql);
			}
			
			break;
		case 'customer.updated':
			$chartnerd_customers = $wpdb->prefix . "chartnerd_customers";
			if( $wpdb->get_var("show tables like '". $chartnerd_customers . "'") === $chartnerd_customers ) { 
				
				$customer = $event_json->data->object;
				$sql = "UPDATE {$wpdb->prefix}chartnerd_customers  SET `customer_created`='%s',`customer_email`='%s',`delinquent`='%s'  WHERE `customer_id` = '%s'";
                    
                $sql = $wpdb->prepare($sql,$customer->created,$customer->email,$customer->delinquent,$customer->id);
                
                $result = $wpdb->query($sql);
			}
			break;
			
		case 'customer.deleted':
			$chartnerd_customers = $wpdb->prefix . "chartnerd_customers";
			if( $wpdb->get_var("show tables like '". $chartnerd_customers . "'") === $chartnerd_customers ) { 
				
				$customer = $event_json->data->object;
				$sql = "DELETE FROM {$wpdb->prefix}chartnerd_customers WHERE `customer_id` = '%s'";
                    
                $sql = $wpdb->prepare($sql,$customer->id);
                
                $result = $wpdb->query($sql);
			}
			break;

		case 'customer.subscription.created':
			$chartnerd_subscription = $wpdb->prefix . "chartnerd_subscription";
			if( $wpdb->get_var("show tables like '". $chartnerd_subscription . "'") === $chartnerd_subscription ) { 

				$subscription = $event_json->data->object;
				if(isset($subscription->discount->coupon->amount_off)){ 
					$discount = $subscription->discount->coupon->amount_off; 
				}else{ 
					$discount=''; 
				}
                if(isset($subscription->discount->coupon->percent_off)){ 
                	$percent_off = $subscription->discount->coupon->percent_off; 
                }else{ 
                	$percent_off=''; 
                }
                  
                     
                $sql = "INSERT INTO {$wpdb->prefix}chartnerd_subscription (`subscription_id`,`customer_id`,`subscription_created`,`subscription_canceled_at`,`subscription_discount`,`subscription_percent_off`,`subscription_start`,`subscription_status`,`subscription_quantity`,`subscription_plan_id`,`subscription_plan_active`,`subscription_plan_amount`,`subscription_plan_interval`) VALUES ('%s','%s','%d','%d','%s','%s','%s','%s','%d','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE subscription_plan_amount = %s, subscription_discount = %s, subscription_percent_off = %s";
                
                $sql = $wpdb->prepare($sql,$subscription->id,$subscription->customer,$subscription->created,$subscription->canceled_at,$discount,$percent_off,$subscription->start,$subscription->status,$subscription->quantity,$subscription->plan->id,$subscription->plan->active,$subscription->plan->amount,$subscription->plan->interval,$subscription->plan->amount,$discount,$percent_off);
                
                $result = $wpdb->query($sql);

			}
			break;

		case 'customer.subscription.updated':
			$chartnerd_subscription = $wpdb->prefix . "chartnerd_subscription";
			if( $wpdb->get_var("show tables like '". $chartnerd_subscription . "'") === $chartnerd_subscription ) { 

				$subscription = $event_json->data->object;
				if(isset($subscription->discount->coupon->amount_off)){ 
					$discount = $subscription->discount->coupon->amount_off; 
				}else{ 
					$discount=''; 
				}
                if(isset($subscription->discount->coupon->percent_off)){ 
                	$percent_off = $subscription->discount->coupon->percent_off; 
                }else{ 
                	$percent_off=''; 
                }
                  
                     
                $sql = "UPDATE {$wpdb->prefix}chartnerd_subscription SET `customer_id`='%s',`subscription_created`='%d',`subscription_canceled_at`='%d',`subscription_discount`='%s',`subscription_percent_off`='%s',`subscription_start`='%s',`subscription_status`='%s',`subscription_quantity`='%d',`subscription_plan_id`='%s',`subscription_plan_active`='%s',`subscription_plan_amount`='%s',`subscription_plan_interval`='%s' WHERE `subscription_id`='%s'";
                
                $sql = $wpdb->prepare($sql,$subscription->customer,$subscription->created,$subscription->canceled_at,$discount,$percent_off,$subscription->start,$subscription->status,$subscription->quantity,$subscription->plan->id,$subscription->plan->active,$subscription->plan->amount,$subscription->plan->interval,$subscription->id);
                
                $result = $wpdb->query($sql);

			}
			break;

		case 'customer.subscription.deleted':
			$chartnerd_subscription = $wpdb->prefix . "chartnerd_subscription";
			if( $wpdb->get_var("show tables like '". $chartnerd_subscription . "'") === $chartnerd_subscription ) { 
				
				$subscription = $event_json->data->object;
				$sql = "DELETE FROM {$wpdb->prefix}chartnerd_subscription WHERE `subscription_id` = '%s'";
                    
                $sql = $wpdb->prepare($sql,$subscription->id);
                
                $result = $wpdb->query($sql);
			}
			break;	
		case 'charge.refunded':
			
			$chartnerd_refunds = $wpdb->prefix . "chartnerd_refunds";
			if( $wpdb->get_var("show tables like '". $chartnerd_refunds . "'") === $chartnerd_refunds ) { 

				$refunds = $event_json->data->object->refunds->data; 
				if(is_array($refunds) && !empty($refunds)){
					foreach($refunds as $refund){
						$sql = "INSERT INTO {$wpdb->prefix}chartnerd_refunds (`id`,`refund_amount`,`refund_charge`,`refund_created`,`refund_status`) VALUES ('%s','%s','%s','%s','%s') ON DUPLICATE KEY UPDATE refund_amount = %s, refund_created = %s";
		                
		                $sql = $wpdb->prepare($sql,$refund->id,$refund->amount,$refund->charge,$refund->created,$refund->status,$refund->amount,$refund->created);
		                
		                $result = $wpdb->query($sql);
	            	}
            	}
			}
			break;

		case 'charge.refund.updated':
			$chartnerd_refunds = $wpdb->prefix . "chartnerd_refunds";
			if( $wpdb->get_var("show tables like '". $chartnerd_refunds . "'") === $chartnerd_refunds ) { 

				$refund = $event_json->data->object; 
				$sql = "UPDATE {$wpdb->prefix}chartnerd_refunds SET `refund_amount`=%d,`refund_charge`='%s',`refund_created`='%s',`refund_status`='%s' WHERE `id`='%s'";
                
                $sql = $wpdb->prepare($sql,$refund->amount,$refund->charge,$refund->created,$refund->status,$refund->id);
                
                $result = $wpdb->query($sql);
			}
			break;	
		
		default:
			# code...
			break;
	}

}

http_response_code(200); // PHP 5.4 or greater