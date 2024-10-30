<?php  get_header(); ?>
<div class="wrap form-main-div">
	<?php if( is_user_logged_in() ){

		

		$user_id = intval(get_current_user_id());
		if(get_user_meta($user_id,'stripe_keys',true)  !='' ){
			
			$keys = @unserialize( get_user_meta($user_id,'stripe_keys',true));
		}  

		?>	
		
		<div class="ssd_stripe_wrapper">
			<span class="ssd_notification notice form-heading">
				<?php echo ( isset($_REQUEST['stripe_keys']) ) ? 'API Keys Updated.' : ''; ?>
			</span>

			<div class="form-div"> 
				<div class="form-image ssd-stripe-form-content"><img src="<?php echo CNSD_URL."/assets/images/icon-256x256.png"; ?>"></div>
				<h2 class="form-heading"><?php _e('Connect with Stripe & Paypal','cnsd-chartnerd'); ?></h2>
				<div class="ssd_stripe_form ssd-stripe-form-content">
					<form method="post" action="<?php site_url().'/stripe-dashboard/' ?>">
						<div class="ssd_stripe_form_fields">
							<label ><?php _e('Stripe Secret API Key','cnsd-chartnerd') ?> </label>
							<input type="password" id="apikey" name="stripe_keys[apikey]" value="<?php echo isset($keys['apikey']) ? $keys['apikey'] : ''; ?>" />
						</div>
						<div class="ssd_stripe_form_fields">
							<label ><?php _e('Stripe Publishable API Key','cnsd-chartnerd') ?></label>
							<input type="password" id="apiprikey" name="stripe_keys[apiprikey]" value="<?php echo isset($keys['apiprikey']) ? $keys['apiprikey'] : ''; ?>" />
						</div>
						<div class="ssd_stripe_form_fields">
							<label ><?php _e('PayPal API Username','cnsd-chartnerd') ?> </label>
							<input type="password" id="paypal_username" name="stripe_keys[paypal_username]" value="<?php echo isset($keys['paypal_username']) ? $keys['paypal_username'] : ''; ?>" />
						</div>
						<div class="ssd_stripe_form_fields">
							<label ><?php _e('PayPal API password','cnsd-chartnerd') ?> </label>
							<input type="password" id="paypal_passsword" name="stripe_keys[paypal_passsword]" value="<?php echo isset($keys['paypal_passsword']) ? $keys['paypal_passsword'] : ''; ?>" />
						</div>
						<div class="ssd_stripe_form_fields password-field">
							<label ><?php _e('PayPal API Signature Key','cnsd-chartnerd') ?> </label>
							<input type="password" id="paypal_signature" name="stripe_keys[paypal_signature]" value="<?php echo isset($keys['paypal_signature']) ? $keys['paypal_signature'] : ''; ?>" />
						</div>
						<?php wp_nonce_field( 'ssd_stripe_keys_nonce', 'ssd_stripe_keys_nonce_field' ); ?>
						<input type="submit" value="Submit">

					</form>
				</div>
			</div>
			<?php if( isset($keys['apikey']) && '' != ($keys['apikey'])  || isset($keys['paypal_username']) && '' != ($keys['paypal_username']) ) { ?>
				<div class="ssd_stripe_syncr_wrapper">
					<div class="ssd_stripe_syncr_button" style="">
						<div class="synchronise_chardnerd_data_left">
							<input type="submit" value="Synchronise Stripe Data" id="synchronise_stripe_data" />
						</div>
						<?php  if( isset($keys['paypal_username']) && '' != ($keys['paypal_username'])  &&  '' != ($keys['paypal_passsword']) && '' != ($keys['paypal_signature'])){ ?>
							<div class="synchronise_chardnerd_data_right">
								<input type="submit" value="Synchronise Paypal Data" id="synchronise_paypal_data" />
							</div>
							<?php }	?>

						</div>
						<div class="ssd_notification_stripe_syncr_section">
							<textarea id="ssd_notification_stripe_syncr"  rows="6" cols="50" readonly></textarea>
						</div>
					</div>
				<?php } ?>
			</div>


		<?php  }else{ ?>
			<span class="ssd_notification notice"><?php esc_html_e('Please log in as administrator to access this feature','cnsd-chartnerd'); ?></span> 
		<?php } ?>

	</div> 
	<?php get_footer(); ?>