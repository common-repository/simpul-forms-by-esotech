jQuery(document).ready( function() {
	
	function showValues() {
		var theid = jQuery(this).attr('id');
		theid = theid.replace('simpul-form-type-', '');
		
		if( this.value != "dropdown") {
			
			jQuery("#simpul-form-values-" + theid).hide();
		}
		else{
			jQuery("#simpul-form-values-" + theid).show();
		}
	}
	jQuery(".simpul-form-type").click( showValues );
	
	jQuery(".simpul-form-type").each( showValues );
	
	// This doesn't work, sadface. :(
	/*jQuery('input').focus( function() {
		var oldValue = jQuery(this).val();
		jQuery(this).val("");
	});
	
	jQuery('input').blur( function() {
		jQuery(this).val(oldValue);
	});*/
	
	
});
