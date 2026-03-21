document.addEventListener('DOMContentLoaded', function(){
	var btn_generate_ticket = $('.agyapay_generate_ticket');
	var token = $('#agyapay_transaction_token').html();

	function generateTicket(e)
	{
		var overlay = loadingOverlay().activate();

		var that = this;
		
		$.ajax({
			'url' : 'index.php',
			'dataType' : 'json',
			'data': {
				'controller' : 'AdminAgYapayTransaction',
				'token' : token,
				'ajax' : true,
				'action' : 'CreateTicket',
				'id_order' : id_order,

				'expiration_date' : $('#agyapay-expiration-date').val(),
				'ticket_value' : $('#agyapay-ticket-value').val(),
			},
			success: function(data) {
				if (typeof data.ticket_url !== 'undefined') {
					location.reload();

					that.href = data.ticket_url;
					that.innerHTML = "<i class='icon-barcode'/> Imprimir Boleto";

					var win = window.open(data.ticket_url, '_blank');
					if (win) {
						win.focus();
					} else {
						loadingOverlay().cancel(overlay);
						alert('Um bloqueador de popup está impedindo o boleto de ser aberto.');
					}
				} else {
					loadingOverlay().cancel(overlay);
					error(data.error);
				}
			},
			error: function(){
				loadingOverlay().cancel(overlay);
				alert('Ocorreu um erro inesperado.');
			}
		});

		e.stopPropagation();
		e.preventDefault();
	}

	function sendTicket(e)
	{
		var overlay = loadingOverlay().activate();
		
		var that = this;
		
		$.ajax({
			'url' : 'index.php',
			'dataType' : 'json',
			'data': {
				'controller' : 'AdminAgYapayTransaction',
				'token' : token,
				'ajax' : true,
				'action' : 'SendTicket',
				'id_order' : id_order,
			},
			success: function(data) {
				if (typeof data.success !== 'undefined') {
					$.growl.notice({'title': '', 'message':'Boleto enviado com sucesso!'});
				} else {
					if (typeof data.error !== 'undefined')
					$.growl.error(data.error);
				}
			},
			error: function(){
				
				$.growl.error({title: '', message: 'Ocorreu um erro inesperado.'});
			},
			complete: function(){
				loadingOverlay().cancel(overlay);
			}
		});

		e.preventDefault();
		e.stopPropagation();
	}

	function repositionButtons()
	{
		$('.agyapay_print_ticket').appendTo('#content > .row > .col-lg-12 > .row > .col-lg-7 > .panel > .hidden-print ').removeClass('hidden');
		$('.agyapay_send_ticket').appendTo('#content > .row > .col-lg-12 > .row > .col-lg-7 > .panel > .hidden-print ').removeClass('hidden');
		$('.agyapay_generate_ticket').appendTo('#content > .row > .col-lg-12 > .row > .col-lg-7 > .panel > .hidden-print ').removeClass('hidden');
	}

	btn_generate_ticket.click(function(e){
		$('.modal.agyapay-create-ticket').modal('show');

		e.preventDefault();
		e.stopPropagation();
	});

	$('.modal.agyapay-create-ticket .btn-primary').click(generateTicket);
	$('.agyapay_send_ticket').click(sendTicket);

	repositionButtons();
});
