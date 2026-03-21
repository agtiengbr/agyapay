{extends file='page.tpl'}

{block name="page_title"}
    Boleto bancário
{/block}

{block name="page_content"}
    {if $error|default}
        <p class="alert alert-danger" style="text-align: center;">{$error}</p>
    {else}
        <p>Você pode pagar o seu boleto bancário em qualquer casa lotérica ou agência bancária, ou ainda pela internet através do aplicativo do seu banco. O pagamento leva um dia útil para ser registrado em nossa loja, então se você já realizou o pagamento, por favor, aguarde até que o mesmo seja compensado.</p>

        <p>O valor do seu Boleto Bancário é: <strong>{$formattedPrice}</strong>.<p>
        <p>O códido de barras do seu boleto é: <strong class="barcode">{$transaction->bar_code}</strong>.
        </p>
        <div class="buttons center">
            <a class="btn btn-primary copy" href='#'>Copiar Código de Barras</a>
            <a class="btn btn-primary" href="{$transaction->original_url_payment}" target="_blank">Imprimir Boleto</a>
        </div>
    {/if}
{/block}