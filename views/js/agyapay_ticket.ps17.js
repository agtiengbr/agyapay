$(function(){
	var div_ps16 = document.getElementById('module-agyapay-ticket');

	
	function submitClicked()
	{
		var spinHandle = loadingOverlay().activate();
	}

	if (div_ps16 !== null) {
		var btn = document.getElementById('cart_navigation').querySelector('button').addEventListener('click', submitClicked);
	}	

	$('#agyapay_ticket').submit(function(){
		var input_fingerprint = $('<input/>', {
			type: 'hidden',
			name: 'fingerprint'
		});

		input_fingerprint.val($('#agyapay_fingerprint_form [name=finger_print]').val());

		$(this).closest('form').append(input_fingerprint);

		return true;
	});
});