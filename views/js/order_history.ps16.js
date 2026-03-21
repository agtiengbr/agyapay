document.addEventListener('DOMContentLoaded', function(){
	var list = document.querySelector('#history #content table, #order-list');

	//se não estivermos na página de histórico de pedidos aborta
	if (list === null) return;

	//pedido que está sendo pago atualmente
	var current_order;

	//lista de pedidos
	var orders = list.querySelectorAll('tbody tr');

	var payment_modal;

	var alert;

	function displayMessage(message)
	{
		alert.innerText = message;

		alert.classList.remove('hidden');
	}

	function createModal()
	{
		var container = document.createElement('div');
		container.id = 'agyapay-pay-existent-order-modal';

		alert = document.createElement('div');
		alert.classList.add('alert');
		alert.classList.add('alert-danger');
		alert.classList.add('hidden');
		container.appendChild(alert);

		$('<p><strong>Atenção!</strong> Cuidado para não realizar o pagamento do mesmo pedido mais de uma vez. Caso você tenha pago o pedido via boleto bancário aguarde até o próximo dia útil para que a loja seja notificada de seu pagamento.</p>').appendTo(container);

		if (agyapay.ticket_active) {
			var btn = document.createElement('button');

			btn.classList.add('btn');
			btn.classList.add('btn-default');
			btn.innerText = 'Gerar Boleto Bancário';

			btn.addEventListener('click', function(e){
				generateTicket();
			});

			container.appendChild(btn);
		}


		if (agyapay.credit_card_active) {
			var btn = document.createElement('button');
			btn.classList.add('btn');
			btn.classList.add('btn-default');
			btn.classList.add('credit_card');
			container.appendChild(btn);
		}


		var a = document.createElement('a');	
		a.innerText = 'Pagar por Cartão de Crédito';
		btn.appendChild(a);

		payment_modal = new AgModal({
			content : container
		});
	}

	function openModal()
	{
		var a = current_order.querySelector('.history_detail .btn-default');
		var search_params = new URLSearchParams(a.href.split('\'')[1]);
		var id_order = search_params.get('id_order');

		document.querySelector('#agyapay-pay-existent-order-modal button.credit_card a').href = agyapay.links.form_closed_order_cc + '?id_order=' + id_order;
		payment_modal.open();
	}

	function generateTicket()
	{
		alert.classList.add('hidden');

		var spinHandle = loadingOverlay().activate();

		//busca o ID do pedido que está sendo pago
		var a = current_order.querySelector('.history_detail .btn-default');
		var search_params = new URLSearchParams(a.href);
		var id_order = search_params.get('id_order');

		$.ajax({
			url: agyapay.links.validation,
			data : {
				action  : 'pay_existent_order',
				payment_mode : 'ticket',
				id_order: id_order
			},
			dataType: 'json',
			success : function(data){
				loadingOverlay().cancel(spinHandle);

				if (data.success) {
					window.open(data.ticket_link);
				} else if (data.error) {
					displayMessage(data.error);
				} else {
					displayMessage('Ocorreu um erro inesperado.');
				}
			},
			error: function(data){
				loadingOverlay().cancel(spinHandle);
				displayMessage('Ocorreu um erro inesperado.');
			}
		})
	}

	function addButtons()
	{
		for (var i=0; i<orders.length; i++) {
			var td_status = orders[i].querySelector('td.history_state');
			var td_links = orders[i].querySelector('td.history_detail');

			if (td_status.innerText.trim() != agyapay.status_pay_closed_order) {
				continue;
			}

			var new_link = document.createElement('a');
			new_link.href = '#';
			new_link.classList.add('agyapay-pay-order');
			new_link.innerText = 'Pagar';

			new_link.addEventListener('click', function(e){
				current_order = this.closest('tr');
				openModal();

				e.preventDefault();
				e.stopPropagation();

				return false;
			});

			td_links.appendChild(new_link);
		}
	}

	function init()
	{
		if (agyapay.credit_card_active || agyapay.ticket_active) {
			createModal();
			addButtons();
		}
	}

	init();
});