{if $ticket_active || $credit_card_active}
	<div class="row">
		{if $ticket_active}
			<div class="col-xs-12">
				<p class="payment_module agyapay ticket">
					<a href="{$link->getModuleLink('agyapay', 'ticket')|escape:'html'}" title="{$ticket_text}">
						<img src="{$image_boleto}" alt="{$ticket_text}"/>
						<span>{$ticket_text}</span>
					</a>
				</p>
			</div>
		{/if}

		{if $eft_active}
			<div class="col-xs-12">
				<p class="payment_module agyapay eft">
					<a href="{$link->getModuleLink('agyapay', 'eft')|escape:'html'}" title="{$eft_text}">
						<img src="{$image_eft}" alt="{$eft_text}"/>
						<span>{$eft_text}</span>
					</a>
				</p>
			</div>
		{/if}


		{if $credit_card_active}
			<div class="col-xs-12">
				<p class="payment_module agyapay credit_card">
					<a href="{$link->getModuleLink('agyapay', 'creditCard')|escape:'html'}" title="{$image_credit_card}">
						<img src="{$image_credit_card}" alt="{$eft_text}"/>
						<span>{$credit_card_text}</span>
					</a>
				</p>
			</div>
		{/if}
	</div>
{/if}