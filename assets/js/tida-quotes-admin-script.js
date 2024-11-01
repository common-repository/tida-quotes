jQuery(document).ready(function(){
    
    if (typeof tidaquotes_params === 'undefined') {
        return;
    }
    
    jQuery('.tida-quote-enable').on('click', function() {
        var nonce_key = tidaquotes_params.enable_field_nonce;
        var enabled = jQuery(this).is(':checked');
        var post_id = jQuery(this).data('post');
        
        jQuery.ajax({
        	type: 'POST',
        	url: tidaquotes_params.ajaxurl,
        	data: {
        		action: 'change_quote_enable_status',
        		nonce_key: nonce_key,
        		post_id: post_id,
        		enabled: enabled ? 1 : 0
        	},
        	dataType: 'json',
        	success: function(response) {
        		if( response.success && enabled )
        		{
        			// Uncheck all checkboxes except user checked
        			jQuery('.tida-quote-enable').each(function() {
        				if (jQuery(this).data('post') !== post_id) {
        					jQuery(this).prop('checked', false);
        				}
        			});
        		}
        	},
        	error: function(error_log) {
        		console.log(error_log);
        	}
        });
    });
});