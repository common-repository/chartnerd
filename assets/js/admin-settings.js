(function($){

	$(document).ready(function(){	

		var get_color = '';
		if($('#CNSD_color').val() ==''){
			get_color = '#000000';
		}else{
			get_color = $('#CNSD_color').val();
		}
		 
			
		$('#CNSD_color').ColorPicker({
		    color: get_color,
		   
		    onChange: function (hsb, hex, rgb) {
		        $('#CNSD_color').val('#' + hex);
		        $('#CNSD_preview div').css('color','#' + hex);
		    }
		});

	}).bind('keyup', function(){
		$(this).ColorPickerSetColor(this.value);
		$('#CNSD_preview div').css('color',this.value);
	});

	$(document).ready(function(){
		$('#CNSD_shortcodes').on('change',function(e){
			var selected_val = $(this).val(); 
			var data = {
			'action': 'CNSD_getShortvodePreview',
			'shortcode': selected_val,
			'sjknjfkd': cnsd_admin_settings_js.ajax_nonce
			};
			jQuery.post(cnsd_admin_settings_js.ajax_url, data, function(response) {
					$('#CNSD_preview').html(response);
			}).fail(function(response) {
					$('#CNSD_preview').html()
		    		alert(response);
		});

		});

		$(document).on('input','#CNSD_fontsize',function(e){
			$('#CNSD_preview div').css('font-size',$(this).val()+'px');
		});
		
		$(document).on('change','#CNSD_googlefont',function(){
			$('#CNSD_preview div').css('font-family',$(this).val().replace('+',' ') );
		});
		
	});
	
})(jQuery);