{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="Voltar ao Checkout">Checkout</a><span class="navigation-pipe">{$navigationPipe}</span>Pagamento via Cartão de Crédito
{/capture}

<h1 class="page-heading">
    Pagamento do Pedido
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('agyapay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post" id="agyapay_credit_card">
    {if $error}
        <p class="alert alert-danger">
            Ocorreu um erro processando o pagamento do seu pedido. Se achar necessário, <a href="{$link->getPageLink('contact')}" target="_blank">entre em contato</a> com nossa equipe de atendimento ao cliente.
        </p>
    {/if}

    <input type="hidden" name="payment_mode" value="credit_card" />
    
    <div class="box cheque-box">
        <h3 class="page-subheading">
            Pagamento via Cartão de Crédito
        </h3>
                <div class="row">
            <div id="cardnumber" class="col-xs-12 col-md-6 col-lg-7">
                <label class="col-xs-12 sr-only">Número do cartão</label>
                <input type="text" name="agyapay_cardnumber" class="col-xs-12" tabindex="1" autocomplete="off"  placeholder="Número do Cartão"/>
                <div id="agyapay_cardbanner"></div>
            </div>
            
            <div class="col-xs-12 col-md-4 col-lg-3">
                <label class="col-xs-12  sr-only">Cod. Segurança</label>
                <input type="text" name="agyapay_cvv" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="4" placeholder="CVV"/>
            </div>
        </div>

        <div class="row">
            <div id="cardholder" class="col-lg-10">
                <label class="col-xs-12  sr-only">Nome do Proprietário</label>
                <input type="text" name="agyapay_name" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="24" placeholder="Nome conforme exibido no cartão"/>
            </div>
        </div>

        <div class="row">
            <div id="installment" class="col-xs-12 col-md-6 col-lg-7">
                <label class="col-xs-12  sr-only">Parcelamento</label>
                <select name="agyapay_installment" class="col-xs-12  is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                    <option value="-1">Escolha a quantidade de parcelas</option>

                    {foreach from=$installments item=installment key=i}
                        {$qtt = $i+1}
                        <option value="{$qtt}">{$qtt} x {$installment['installment_value']} ({$installment['total']})</option>
                    {/foreach}
                </select>
            </div>
            
            <div class="col-xs-12 col-md-4 col-lg-5" id="card_expiration">
                <label class="col-xs-12  sr-only">Validade:</label>
                <span>
                    <select name="agyapay_month" class="col-xs-7  is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                        <option value="-1">Mês</option>
                        <option value="01">Janeiro</option>
                        <option value="02">Fevereiro</option>
                        <option value="03">Março</option>
                        <option value="04">Abril</option>
                        <option value="05">Maio</option>
                        <option value="06">Junho</option>
                        <option value="07">Julho</option>
                        <option value="08">Agosto</option>
                        <option value="09">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </span>

                <span>
                    <select name="agyapay_year" class="col-xs-5 is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                        <option value="-1">Ano</option>
                        {for $year=0 to 12}
                            {$y = date('Y') + $year}
                            <option value="{$y}">{$y}</option>
                        {/for}
                    </select>
                </span>
            </div>
        </div>
    </div><!-- .cheque-box -->        

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>Outras formas de pagamento
        </a>
        <button class="button btn btn-default button-medium" type="submit">
            <span>Confirmar a Compra<i class="icon-chevron-right right"></i></span>
        </button>
    </p>

    <div class="hidden">
        <textarea id="agyapay_public_key">{$agyapay_public_key}</textarea>
        <input id="agyapay_card_hash" name="agyapay_card_hash"></input>
    </div>
</form>
