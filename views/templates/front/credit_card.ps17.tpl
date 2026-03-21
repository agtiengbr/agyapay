<form action="{$form_action}" method="post" id="agyapay_credit_card">

    {if $credit_cards|count}
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <a class="nav-item nav-link active" id="" data-toggle="tab" href="#agyapay_tab_existent_credit_card" role="tab" aria-controls="nav-home" aria-selected="true" aria-expanded="true">Utilizar um cartão salvo</a>
                <a class="nav-item nav-link" id="" data-toggle="tab" href="#agyapay_tab_new_credit_card" role="tab" aria-controls="nav-home" aria-selected="true" aria-expanded="true">Utilizar um novo cartão</a>
            </div>
        </nav>
    {/if}

    {if $credit_cards|count}
        <div class="tab-content" id="agyapay_tabs_content">      
            <div class="tab-pane active" id="agyapay_tab_existent_credit_card" aria-expanded="true">
                <div id='agyapay_credit_card_selection' class=''>
                    {foreach from=$credit_cards item=credit_card}
                        <div class='agyapay_credit_card_container'>
                            <label for="agyapay_credit_card_{$credit_card->id}">Utilizar o cartão {$credit_card->cardnumber}.</label>
                            <input type="radio" name="agyapay_credit_card_id" value="{$credit_card->id}" id="agyapay_credit_card_{$credit_card->id}" />
                        </div>
                    {/foreach}
                    <input type="hidden" name="agyapay_use_existent_card" value="1" />
                </div>
            </div>

            <div class="tab-pane" id="agyapay_tab_new_credit_card" aria-expanded="false">
    {/if}
                <div  class="box cheque-box">
                    <div class="row">
                        <div id="cardholder" class="col-lg-10">
                            <label class="col-xs-12  sr-only">Nome do Proprietário</label>
                            <input type="text" name="agyapay_name" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="24" placeholder="Nome conforme exibido no cartão"/>
                        </div>
                    </div>

                    <div class="row">
                        <div id="cardnumber" class="col-xs-12 col-md-6 col-lg-7">
                            <label class="col-xs-12 sr-only">Número do cartão</label>
                            <input type="text" name="agyapay_cardnumber" id="agyapay_cardnumber" class="col-xs-12" tabindex="1" autocomplete="off"  placeholder="Número do Cartão"/>
                            <div id="agyapay_cardbanner"></div>
                        </div>
                        
                        <div class="col-xs-12 col-md-4 col-lg-3">
                            <label class="col-xs-12  sr-only">Cod. Segurança</label>
                            <input type="text" name="agyapay_cvv" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="4" placeholder="CVV"/>
                        </div>
                    </div>

                    <div class="row">          
                        <div class="col-xs-12 col-md-6 col-lg-7">
                            <label class="col-xs-12 sr-only">Validade:</label>
                            <select name="agyapay_month" class="is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
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
                        </div>

                        <div class="col-xs-12 col-md-6 col-lg-3">
                            <label class="col-xs-12 sr-only">Ano:</label>
                            <select name="agyapay_year" class=" is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                                <option value="-1">Ano</option>
                                {for $year=0 to 12}
                                    {$y = date('Y') + $year}
                                    <option value="{$y}">{$y}</option>
                                {/for}
                            </select>
                        </div>
                    </div>
                        
                    <div class='row' hidden>    
                        <div class="float-xs-left col-lg-1">
                            <span class="custom-checkbox">
                                <input id="agyapay_save_credit_card" name="agyapay_save_credit_card" type="checkbox" value="1">
                                <span><i class="material-icons rtl-no-flip checkbox-checked"></i></span>
                            </span>
                            <div class="condition-label">
                                <label class="" for="agyapay_save_credit_card">Desejo salvar os dados do meu cartão em ambiente seguro para facilitar a realização de novas compras.</label>
                            </div>
                        </div>
                    </div>

                    {if $credit_cards|count == 0}
                        <div class="row">
                            <div id="month" class="col-xs-12 col-md-6 col-lg-7">
                                <label class="col-xs-12  sr-only">Parcelamento</label>
                                <select name="agyapay_installment" class="col-xs-12  is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                                    <option value="-1">Escolha a quantidade de parcelas</option>
                                    {foreach from=$installments|default:[] item=installment key=key}
                                        <option value="{$key + 1}">{$key+1}x {$installment['installment_value']} (Total de {$installment['total']})</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    {/if}
                </div>
    {if $credit_cards|count}
        </div>
    {/if}
    
	<input type="hidden" name="payment_mode" value="credit_card" />
    
    {if $credit_cards|count}
        <div id="agyapay_installments" {($installments) ? '' : 'hidden'}>
            <h3>Parcelamento</h3>
            <div id="agyapay_installments_list">
                {foreach from=$installments item=installment key=key}
                    <div class='agyapay_installment_container'>
                        <label for="agyapay_installments_{$key}">{math equation="key + 1" key=$key}x {$installment['installment_value']} (Total de {$installment['total']})</label>
                        <input type="radio" name="agyapay_installment" value="{math equation="key + 1" key=$key}" id="agyapay_installments_{$key}" />
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
    {/if}
</form>