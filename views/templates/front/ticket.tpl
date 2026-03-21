{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="Voltar ao Checkout">Checkout</a><span class="navigation-pipe">{$navigationPipe}</span>Pagamento via Boleto Bancário
{/capture}

<h1 class="page-heading">
    Pagamento do Pedido
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('agyapay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
    {if $error}
        <p class="alert alert-danger">
            Ocorreu um erro processando o pagamento do seu pedido. Se achar necessário, <a href="{$link->getPageLink('contact')}" target="_blank">entre em contato</a> com nossa equipe de atendimento ao cliente.
        </p>
    {/if}

	<input type="hidden" name="payment_mode" value="ticket" />
	
    <div class="box cheque-box">
        <h3 class="page-subheading">
            Pagamento via Boleto Bancário
        </h3>

        <p>Você pode pagar o boleto em qualquer banco ou casa lotérica. Se escolher pagar via boleto, o total de sua compra será de <strong>{displayPrice price=$total_with_discount}</strong>.</p>
    </div><!-- .cheque-box -->        

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>Outras formas de pagamento
        </a>
        <button class="button btn btn-default button-medium" type="submit">
            <span>Confirmar a Compra<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
</form>
