{extends file='page.tpl'}
{block name="page_title"}
{/block}


{block name="page_content"}
    <div id='agyapay_existent_order'>
        {if $fatal_error|default != ''}
            <div class='alert alert-danger'>{$fatal_error}</div>
        {else}
            {if $error}
                <p class="alert alert-danger">
                    Ocorreu um erro processando o pagamento do seu pedido. Se achar necessário, <a href="{$link->getPageLink('contact')}" target="_blank">entre em contato</a> com nossa equipe de atendimento ao cliente.
                </p>
            {/if}
            
            <p>Você está pagando o seu pedido <strong>{$order_reference}</strong> no valor de <strong>{$total}</strong>. Se você já tiver pago o pedido via boleto bancário, por favor aguarde até o próximo dia útil para que nossa loja seja notificada sobre o pagamento.</p>
            
            <hr>

            <form action="{$form_action}" method="post" id="agyapay_credit_card">
            	<input type="hidden" name="payment_mode" value="credit_card" />
                <input type="hidden" name="action" value="pay_existent_order" />
                <input type="hidden" name="id_order" value="{$id_order}" />
                
            	
                <div class="box cheque-box">
                    <div class="row">
                        <div id="cardnumber" class="col-xs-12 col-md-6">
                            <label class="col-xs-12 sr-only">Número do cartão</label>
                            <input type="text" name="agyapay_cardnumber" class="col-xs-12" tabindex="1" autocomplete="off"  placeholder="Número do Cartão"/>
                            <div id="agyapay_cardbanner"></div>
                        </div>
                        
                        <div class="col-xs-12 col-md-2">
                            <label class="col-xs-12  sr-only">Cod. Segurança</label>
                            <input type="text" name="agyapay_cvv" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="4" placeholder="CVV"/>
                        </div>
                    </div>

                    <div class="row">
                        <div id="cardholder" class="col-md-8">
                            <label class="col-xs-12  sr-only">Nome do Proprietário</label>
                            <input type="text" name="agyapay_name" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="24" placeholder="Nome conforme exibido no cartão"/>
                        </div>
                    </div>

                    <div class="row">
                        <div id="month" class="col-xs-12 col-md-4">
                            <label class="col-xs-12  sr-only">Parcelamento</label>
                            <select name="agyapay_installment" class="col-xs-12  is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                                <option value="-1">Escolha a quantidade de parcelas</option>
                                {if $installments|count}
                                    {foreach from=$installments|default:[] item=installment key=key}
                                        <option value="{$key + 1}">{$key+1}x {$installment['installment_value']} (Total de {$installment['total']})</option>
                                    {/foreach}
                                {/if}
                            </select>
                        </div>
                        
                        <div class="col-xs-12 col-md-4 row pr-0">
                            <label class="col-xs-12  sr-only">Validade:</label>
                            <span class="col-6 pr-0">
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

                            <span class="col-6 pr-0">
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

                    <hr>

                    <div class='row justify-content-end'>
                        <div class='buttons'>
                            <button class="btn btn-default">
                                <a href='{$cancel_link}'>Cancelar</a>
                            </button>

                            <button class="btn btn-primary" type='submit'>Confirmar</button>
                        </div>
                    </div>

                    <div class="row">
                </div>
            </form>
        {/if}
    </div>
{/block}
