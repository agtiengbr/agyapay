document.addEventListener('DOMContentLoaded', function(){
	var list = document.querySelector('#history #content table');

	//se não estivermos na página de histórico de pedidos aborta
	if (list === null) return;

	//pedido que está sendo pago atualmente
	var current_order;

	//lista de pedidos
	var orders = list.querySelectorAll('tbody tr');

	var payment_modal;

	var alert;

	function addButtons()
	{
		for (var i=0; i<orders.length; i++) {
			var td_status = orders[i].querySelector('td .label.label-pill').closest('td');
			var td_links = orders[i].querySelector('td.order-actions');

			if (td_status.innerText.trim() != agyapay.status_pay_closed_order) {
				continue;
			}

			let link = $(td_links).find('a')[0];
			let href = $(link).prop('href');
			let id_order = href.match(/id_order=([0-9]*)/)[1];
			
			
			var new_link = document.createElement('a');
			new_link.href = agyapay.links.payorder + `?id_order=${id_order}`;
			new_link.classList.add('agyapay-pay-order');
			new_link.innerText = 'Pagar';

			td_links.appendChild(new_link);
		}
	}

	function init()
	{
		if (agyapay.credit_card_active || agyapay.ticket_active || agyapay.pix_active) {
			addButtons();
		}
	}

	init();
});