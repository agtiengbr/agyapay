document.addEventListener('DOMContentLoaded', () => {
    var btn_generate_ticket = $('.agyapay_generate_ticket');
    
    async function loadModalAjax()
    {
        let id_order = $('.title-row strong.text-muted').text().replace('#', '');
        
        return new Promise((resolve, reject) => {
            $.ajax({
                url: agyapay_transaction_url, // Alterado para usar a URL fornecida pelo base.php
                type: 'POST',
                dataType: 'JSON',
                data: {
					controller: 'AdminAgYapayTransaction',
					ajax: true,
					action: 'RenderGenerateTicketModal',
					token: agyapay_transaction_token,

                    id_order: id_order
                }
            })
            .then(function(data){
                if (data.success) {
                    resolve(data.modal);
                } else {
                    reject(data.error);
                }
            })
            .fail(function(){
				reject("Ocorreu um erro inesperado.")
			});
        });
	}
	
	async function displayModal()
	{
		overlay = loadingOverlay().activate();

		try {
			let modal = await loadModalAjax();

			$(modal).modal('show')
			.on('hidden.bs.modal', function(){
				$(this).remove();
			});

		} catch (error) {
			$.growl.error({title: '', message: error});
		}

		loadingOverlay().cancel(overlay);
	}

	async function generateTicketAjax()
	{
		let id_order = $('.title-row strong.text-muted').text().replace('#', '');

		return new Promise((resolve, reject) => {
			$.ajax({
				'url' : agyapay_transaction_url, // Alterado para usar a URL fornecida pelo base.php
				'dataType' : 'json',
				'data': {
					'controller' : 'AdminAgYapayTransaction',
					'token' : agyapay_transaction_token,
					'action' : 'CreateTicket',
					'id_order' : id_order,

					'expiration_date' : $('#agyapay-expiration-date').val(),
					'ticket_value' : $('#agyapay-ticket-value').val(),
				},
				success: function(data) {
					if (typeof data.ticket_url !== 'undefined') {
						resolve(data.ticket_url);
					} else {
						reject(data.error);
					}
				},
				error: function(){
					reject('Ocorreu um erro inesperado.');
				}
			});
		});
	}

	async function generateTicket()
	{
		overlay = loadingOverlay().activate();

		try {
			let ticket_url = await generateTicketAjax();
			var win = window.open(ticket_url, '_blank');
			if (win) {
				win.focus();
			} else {
				loadingOverlay().cancel(overlay);
				alert('Um bloqueador de popup está impedindo o boleto de ser aberto.');
			}

			$('.modal').modal('hide');
		} catch (error) {
			$.growl.error({title: '', message: error});
		}

		loadingOverlay().cancel(overlay);
	}

	async function sendTicketAjax()
	{
		let id_order = $('.title-row strong.text-muted').text().replace('#', '');
		
		return new Promise((resolve, reject) => {
			$.ajax({
				'url' : agyapay_transaction_url, // Alterado para usar a URL fornecida pelo base.php
				'dataType' : 'json',
				'data': {
					'controller' : 'AdminAgYapayTransaction',
					'token' : agyapay_transaction_token,
					'ajax' : true,
					'action' : 'SendTicket',
					'id_order' : id_order,
				},
				success: function(data) {
					if (data.success) {
						resolve();
					} else {
						reject(data.error);
					}
				},
				error: function(){
					reject('Ocorreu um erro inesperado.');
				}
			});
		});
	}

	async function sendTicket()
	{
		var overlay = loadingOverlay().activate();

		try {
			let ticket_url = await sendTicketAjax();
			$.growl.notice({'title' : '', 'message': 'E-mail enviado com sucesso'});
		} catch (error) {
			$.growl.error({title: '', message: error});
		}

		loadingOverlay().cancel(overlay);
	}

	btn_generate_ticket.click(e => {
		displayModal();
		return false;
	});

	$(document).on('click', '.modal.agyapay-create-ticket .btn-primary', generateTicket);

	$('.agyapay_send_mail').click(()=> {
		sendTicket();
		return false;
	});
});
