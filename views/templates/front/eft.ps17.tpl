<form action='{$form_action}' id='agyapay_eft_form'>
	<p>Você pode pagar de maneira prática e segura com débito em conta. Se escolher pagar via débito em conta, o total de sua compra será de <strong>{$total_with_discount}</strong>.</p>

	<p>Escolha o banco abaixo:</p>

	<input type='hidden' name='payment_mode' value='eft' />

	<div>
		<input type="radio" id='agyapay_bank_itau' name="agyapay_bank" value="ITAU" />
		<label for='agyapay_bank_itau'>Itaú</label>
	</div>

	<div>
		<input type="radio" id='agyapay_bank_bradesco' name="agyapay_bank" value="BRADESCO" />
		<label for='agyapay_bank_bradesco'>Bradesco</label>
	</div>

	<div>
		<input type="radio" id='agyapay_bank_brasil' name="agyapay_bank" value="BANCO_BRASIL" />
		<label for='agyapay_bank_brasil'>Banco do Brasil</label>
	</div>
</form>