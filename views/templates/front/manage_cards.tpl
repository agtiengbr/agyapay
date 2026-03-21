{extends file='page.tpl'}

{block name='page_title'}
    Meus Cartões
{/block}

{block name='page_content'}
    <div class = 'alert alert-info'>
        Esses são os cartões de crédito salvos em seu cofre. Nós não armazenamos diretamente o número do seu cartão, mas sim junto ao cofre disponibilizado por nosso meio de pagamento. Para excluir um dos cartões, clique sobre ele.
    </div>

    <div class="">
        <button id="agapay-new-card" class="pull-right btn btn-primary">Cadastrar Cartão</button>
    </div>

    <div class='hidden' id="agyapay-modal">
    
        <div class="alert alert-info">
            Os dados do seu cartão de crédito ficarão armazenados em ambiente seguro, junto a um cofre disponibilizado pelo nosso meio de pagamento. Ele poderá ser utilizado para a realização de compras em um clique ou para cobranças automáticas, desde que autorizadas por você.
        </div>

        <div class="box cheque-box">
            <div class="row">
                <div id="cardholder" class="col-lg-10">
                    <label class="col-xs-12  sr-only">Nome do Proprietário</label>
                    <input type="text" name="agyapay_name" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="24" placeholder="Nome conforme exibido no cartão"/>
                </div>
            </div>

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
                
            {* <div class='row'>    
                <div class="float-xs-left col-lg-1">
                    <span class="custom-checkbox">
                        <input id="agyapay_save_credit_card" name="agyapay_save_credit_card" type="checkbox" value="1" checked>
                        <span><i class="material-icons rtl-no-flip checkbox-checked"></i></span>
                    </span>
                    </div><div class="condition-label">
                    <label class="" for="agyapay_save_credit_card">Desejo salvar os dados do meu cartão em ambiente seguro para facilitar a realização de novas compras.</label>
                    </div>
                </div>
            </div> *}
        </div>

        <button id="agapay-submit-card" class="btn btn-primary">Cadastrar Cartão</button>
    </div>
    
    <div class="cards" style="margin: auto">
        {foreach from=$cards item=card}
            {include file="module:agyapay/views/templates/front/card.tpl" card=$card}
        {/foreach}
    </div>
{/block}