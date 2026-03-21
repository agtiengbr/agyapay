{extends file="page.tpl"}

{block name="content_wrapper"}
<div id="checkout" data-order="{$order->id}" class="agyapay-payorder pb-1">
    <div class="row">
        <div class="col-md-8 cart-grid-body">
            <div class="card cart-container">
                <div class="card-block">
                    <div class="px-1">
                        <h2>Olá, {$payorder_customer->firstname} {$payorder_customer->lastname}</h2>
                        <p class="m-0">Você está pagando o pedido {$order->reference} de {dateFormat date=$order->date_add}</p>
                    </div>
                </div>

                <hr class="separator">

                <div class="p-1">
                    <div class="payment-options cart-overview js-cart">
                        {if $cc_form|default}
                            <div>
                                <div id="payment-option-card-container" class="payment-option clearfix">
                                    <span class="custom-radio float-xs-left">
                                        <input class="ps-shown-by-js " id="payment-option-card" data-module-name="" name="payment-option" type="radio" required="">
                                        <span></span>
                                    </span>

                                    <label for="payment-option-card">
                                        <span>Pagar no Cartão de Crédito</span>
                                    </label>

                                </div>
                            </div>

                            <div id="pay-with-payment-option-card-form" class="pt-1 pb-2 js-payment-option-form  ps-hidden " style="display: none;">
                                {$cc_form nofilter}
                            </div>
                        {/if}


                        {if $ticket_form|default}
                            <div>
                                <div id="payment-option-ticket-container" class="payment-option clearfix">
                                    <span class="custom-radio float-xs-left">
                                        <input class="ps-shown-by-js " id="payment-option-ticket" data-module-name="" name="payment-option" type="radio" required="">
                                        <span></span>
                                    </span>
                                    <form method="GET" class="ps-hidden-by-js" style="display: none;">
                                        <button class="ps-hidden-by-js" type="submit" name="select_payment_option" value="payment-option-ticket" style="display: none;">
                                            Escolha
                                        </button>
                                    </form>

                                    <label for="payment-option-ticket">
                                        <span>Pagar no Boleto</span>
                                    </label>

                                </div>
                            </div>

                            <div id="pay-with-payment-option-ticket-form" class="pt-1 pb-2 js-payment-option-form  ps-hidden " style="display: none;">
                                {$ticket_form nofilter}
                            </div>
                        {/if}


                        {if $pix_form|default}
                            <div>
                                <div id="payment-option-pix-container" class="payment-option clearfix">
                                    <span class="custom-radio float-xs-left">
                                        <input class="ps-shown-by-js " id="payment-option-pix" data-module-name="" name="payment-option" type="radio" required="">
                                        <span></span>
                                    </span>
                                    <form method="GET" class="ps-hidden-by-js" style="display: none;">
                                        <button class="ps-hidden-by-js" type="submit" name="select_payment_option" value="payment-option-pix" style="display: none;">
                                            Escolha
                                        </button>
                                    </form>

                                    <label for="payment-option-pix">
                                        <span>Pagar no PIX</span>
                                    </label>

                                </div>
                            </div>

                            <div id="pay-with-payment-option-pix-form" class="pt-1 pb-2 js-payment-option-form  ps-hidden " style="display: none;">
                                {$pix_form nofilter}
                            </div>
                        {/if}




                        {if $eft_form|default}
                            <div>
                                <div id="payment-option-bank-container" class="payment-option clearfix">
                                    <span class="custom-radio float-xs-left">
                                        <input class="ps-shown-by-js " id="payment-option-bank" data-module-name="" name="payment-option" type="radio" required="">
                                        <span></span>
                                    </span>
                                    <form method="GET" class="ps-hidden-by-js" style="display: none;">
                                        <button class="ps-hidden-by-js" type="submit" name="select_payment_option" value="payment-option-bank" style="display: none;">
                                            Escolha
                                        </button>
                                    </form>

                                    <label for="payment-option-bank">
                                        <span>Pagar no Débito em Conta</span>
                                    </label>

                                </div>
                            </div>

                            <div id="pay-with-payment-option-bank-form" class="pt-1 pb-2 js-payment-option-form  ps-hidden " style="display: none;">
                                {$eft_form nofilter}
                            </div>
                        {/if}
                    </div>

                    <div id="payment-confirmation" class="js-payment-confirmation card-block pt-0">
                        <div class="ps-shown-by-js">
                            <button type="submit" class="btn btn-primary center-block">
                                Confirmar o pedido
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="col-md-4 cart-grid-right">
            <div class="card">
                <section id="js-checkout-summary" class="card js-cart" data-refresh-url="https://dev2.agti.eng.br/carrinho?ajax=1&amp;action=refresh">
                    <div class="card-block cart-detailed-subtotals js-cart-detailed-subtotals">
                        <div class="cart-summary-top js-cart-summary-top">
                        </div>
                        <div class="cart-summary-products js-cart-summary-products">
                            <div id="cart-summary-product-list" aria-expanded="true" style="">
                                <ul class="media-list">
                                    {foreach from=$products item=product}
                                        <li class="media">
                                            <div class="media-left">
                                                <a href="#">
                                                    <img class="media-object" src="{$product['image']}" alt="Hummingbird printed sweater" loading="lazy">
                                                </a>
                                            </div>
                                            <div class="media-body media-position-relative">
                                                <div>
                                                    <span class=" line-clamp">
                                                        <a href="#" target="_blank" title="{$product.product_name}">{$product.product_name}</a>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="product-quantity mt-1 label">Quantidade: {$product.product_quantity}</span>
                                                    <span class="label">Valor: {$product.price_formatted}</span>
                                                </div>
                                            </div>
                                        </li>
                                    {/foreach}
                                </ul>
                            </div>
                        </div>
    
    
                        <div class="cart-summary-subtotals-container js-cart-summary-subtotals-container">
                            <div class="cart-summary-line cart-summary-subtotals" id="cart-subtotal-products">
                                <span class="label">
                                    Subtotal
                                </span>
                                <span class="value">
                                    {$total_products_wt}
                                </span>
                            </div>
                            <div class="cart-summary-line cart-summary-subtotals" id="cart-subtotal-shipping">

                                <span class="label">
                                    Frete
                                </span>

                                <span class="value">
                                    {$total_shipping_tax_incl}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-block cart-summary-totals js-cart-summary-totals">
                        <div class="cart-summary-line cart-total">
                            <span class="label">Total&nbsp;</span>
                            <span class="value">{$total_paid_tax_incl}</span>
                        </div>
                    </div>

                </section>

            </div>
        </div>
    </div>
</div>
{/block}
