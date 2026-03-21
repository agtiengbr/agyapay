{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="Voltar ao Checkout">Checkout</a><span class="navigation-pipe">{$navigationPipe}</span>Pagamento via Débito em Conta
{/capture}

<h1 class="page-heading">
    Pagamento do Pedido
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('agyapay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post" id='agyapay_eft_form'>
    {if $error}
        <p class="alert alert-danger">
            Ocorreu um erro processando o pagamento do seu pedido. Se achar necessário, <a href="{$link->getPageLink('contact')}" target="_blank">entre em contato</a> com nossa equipe de atendimento ao cliente.
        </p>
    {/if}

	<input type="hidden" name="payment_mode" value="eft" />
	
    <div class="box cheque-box">
        <h3 class="page-subheading">
            Pagamento via Débito em Conta
        </h3>

        <p>Você pode pagar de maneira prática e segura com débito em conta. Se escolher pagar via boleto, o total de sua compra será de <strong>{$total_with_discount}</strong>.</p>

        <br/>
        <p>Escolha o banco abaixo:</p>
        <br/>
        <input type='hidden' id='agyapay_ticket_session' name='agyapay_ticket_session' value="{$session_id}" />
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
    </div><!-- .cheque-box -->        

    <input type='hidden' id='agyapay_credit_card_hash' name='agyapay_credit_card_hash'/>

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>Outras formas de pagamento
        </a>
        <button class="button btn btn-default button-medium" type="submit">
            <span>Confirmar a Compra<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
</form>
