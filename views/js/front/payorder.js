window.addEventListener('load', function(){
    let id = $('#checkout').attr('data-order');
    
    $('.payment-options form').each(function(){
        $(this).attr('action', $(this).attr('action') + `?id_order=${id}&action=pay_existent_order`);
    });

    $(document).on('submit', '#pay-with-payment-option-pix-form #agyapay_ticket,#pay-with-payment-option-ticket-form #agyapay_ticket', function(){
		var overlay = loadingOverlay().activate();


		$.ajax({
				url: $("#agyapay_ticket").attr('action'),
				dataType: 'JSON',
				method: 'POST',
				data:  {
					payment_mode: $(this).find('input[name="payment_mode"]').val()
				}
			})
			.then(function(data){
				loadingOverlay().cancel(overlay);

				if (data.success){
					window.location.href = data.url;
				}else{
					$("#notifications .container").empty().append(`
					<article class="alert alert-danger" role="alert" data-alert="danger">
						<ul>
							<li>`+data.error+`</li>
						</ul>
				  </article>`);
		  
				}
				
			});
		return false;
	});
})