(function($){
	
	$.fn.focusToEnd = function() {
	   return this.each(function() {
	       var v = $(this).val();
	       $(this).focus().val("").val(v);
	   });
	};

	var ssdprogressbar_inter;
	
	$('#synchronise_stripe_data').on('click',function(){
		
		$('.ssd_wp_site_loader_wrapper').show();
		var reqtype = ["refund"];
		var reqtype = ["subscription","customer","refund"];
		
		var requestTimeTaken= new Date().getTime();
		CNSD_get_stripe_ajaxdata(true,'',reqtype,0,requestTimeTaken,0);

	});

	function CNSD_get_stripe_ajaxdata(hasmore='',last_id='',reqtype,progressbar_init,requestTimeTaken,totalRecords){

		if(hasmore == false && jQuery.isEmptyObject(reqtype) === true){ 
			
			$('#ssd_notification_stripe_syncr').text($('#ssd_notification_stripe_syncr').text()+'\n All Done...'); 
			$('.ssd_wp_site_loader_wrapper').hide();  
			return 'success';  
		}

		var req_type = reqtype;

		//console.log('progressbar_init-'+progressbar_init+' requestTimeTaken- '+requestTimeTaken+' totalRecords- '+totalRecords);
		
		 if(progressbar_init==1 && jQuery.isEmptyObject(reqtype) == false && totalRecords > 0 ){
			
			$('.ssd_wp_myprogress_wrapper h3').text('Fetching '+req_type[0]+'.....');
			$('.ssd_wp_myprogress_wrapper').show(); 

			var totalTime = ( new Date().getTime()-requestTimeTaken - 3000 ) / 1000; 
			var  interval_ime = CNSD_synchronise_progressbar_interval(totalRecords,totalTime); 
			if(interval_ime > 0) {
				CNSD_synchronise_progressbar(interval_ime);
			}

		}

		var data = {
			'action': 'CNSD_synchronise_data',
			'last_id': last_id,
			'reqtype': req_type[0],
			'ZpbeVLU7f': ssd_object.ajax_nonce
		};
		
		requestTimeTaken= new Date().getTime();

		jQuery.post(ssd_object.ajaxurl, data, function(response) {
		
	       try
	       {
	            var data_res = $.parseJSON(response);
	            progressbar_init++;
	            if( $.isNumeric(data_res.total_counts) == true){
	            	totalRecords = data_res.total_counts;
	            }else{
	            	totalRecords = 0;
	            }
	            
				if(data_res.error[0]!=''){
					jQuery('#ssd_notification_stripe_syncr').text(jQuery('#ssd_notification_stripe_syncr').text()+'\n'+data_res.error+'...');
				}
				jQuery('textarea').focusToEnd(); 

				if(data_res.has_more == true){
					CNSD_get_stripe_ajaxdata(true,data_res.last_id,req_type,progressbar_init,requestTimeTaken,totalRecords);
				}else{

					clearInterval(ssdprogressbar_inter);
					$('#sd_wp_mybar').attr('style','width:100%;');

					req_type.splice(0,1);
					if(jQuery.isEmptyObject(reqtype) == true){	
						CNSD_get_stripe_ajaxdata(false,data_res.last_id,req_type,progressbar_init,requestTimeTaken,totalRecords);
					}else{
						CNSD_get_stripe_ajaxdata(true,'',req_type,0,requestTimeTaken,totalRecords);
					}
				}
	       }
	       catch(err)
	       {
	             $('.ssd_wp_site_loader_wrapper').hide();
	             $('#ssd_notification_stripe_syncr').text(jQuery('#ssd_notification_stripe_syncr').text()+'\n Error: '+response+'...');
		   } 

		}).fail(function(response) {
			$('.ssd_wp_site_loader_wrapper').hide();
		    $('#ssd_notification_stripe_syncr').text(jQuery('#ssd_notification_stripe_syncr').text()+'\n Error: '+response+'...');
		});

	}

	function CNSD_synchronise_progressbar(intervalTime) {
	  var elem = document.getElementById("sd_wp_mybar");   
	  var width = 1;
	  ssdprogressbar_inter = setInterval(function () {
	    if (width >= 100) {
	      clearInterval(ssdprogressbar_inter);
	    } else {
	      width++; 
	      elem.style.width = width + '%'; 
	    }
	  }, intervalTime); 
	}

	function CNSD_synchronise_progressbar_interval(totalRecords='',requestTime=''){
		if(totalRecords > 0 && requestTime > 0 ){
			 
			 return  ( ( Math.ceil(totalRecords / 300)  ) * requestTime ) * 10;
		}else{
			 return 0;
		}
	}
	
})(jQuery);