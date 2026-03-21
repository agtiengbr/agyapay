<div class="agyapay_print_ticket">
	<div class="hidden" id='agyapay_transaction_token'>{$token}</div>
	{if $print_ticket_link|default}
		<a target="_blank" href="{$print_ticket_link}" class="agyapay_print_ticket btn btn-default"><i class="icon-barcode"></i> Imprimir Boleto do YaPay</a>

		<a target="_blank" href="" class="agyapay_send_ticket btn btn-default"><i class="icon-envelope"></i> Enviar Boleto por E-mail</a>
	{/if}

	{if $remaining_value > 0}
		<a target="_blank" href="" class="agyapay_generate_ticket btn btn-default"><i class="icon-barcode"></i> Gerar Boleto pelo YaPay</a>	
	{/if}
</div>