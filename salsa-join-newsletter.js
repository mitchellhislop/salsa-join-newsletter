var salsaJoinElements;

(function($) {

salsaJoinElements = {

	init : function() {
		this.attachListeners();
	},

	attachListeners : function() {
		var that = this;
		$('#salsa-send').click(function() {
			that.ajaxSubmit();
		});
	},

	ajaxSubmit : function() {
		var that = this;
		$.post('/wp-admin/admin-ajax.php',
			that.getData(),
			function(response)
			{
				if(response <= 0)
				{
					alert('Got response: ' + response);
				}
				else
				{
					alert('Thank you');
				}
			}
		);
	},

	getData : function()
	{
		return {
			action : 'salsa_join',
			email : $('#salsa-email').val(),
		};
	},
	
	
};

$(document).ready(function($){ salsaJoinElements.init(); });

})(jQuery);
