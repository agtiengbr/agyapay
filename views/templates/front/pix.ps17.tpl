<form action='{$form_action}' id="agyapay_ticket" method="post">
	<p>Os pedidos via PIX podem levar até 30 minutos para serem aprovados. O valor do PIX será de <strong>{$total_with_discount}</strong>.</p>

	<input type='hidden' name='payment_mode' value='pix' />
</form>