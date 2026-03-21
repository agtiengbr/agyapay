<form action='{$form_action}' id="agyapay_ticket" method="post">
	<p>Você pode pagar o boleto em qualquer banco ou casa lotérica. Se escolher pagar via boleto, o total de sua compra será de <strong>{$total_with_discount}</strong>.</p>

	<input type='hidden' name='payment_mode' value='ticket' />
</form>