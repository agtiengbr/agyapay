{extends file="page.tpl"}

{block name="content"}
{if $error|default}
    <div class='alert alert-danger'>{$error}</div>
{else}
    <p>Você pode pagar o seu pedido realizando a leitura do QRCode abaixo no aplicativo do seu banco, ou copiando o código clicando no botão abaixo e utilizando a opção de PIX Copia e Cola do seu banco.
        </p>

        <p>O PIX poderá ser pago até <strong>{Tools::displayDate($transaction->pix_expiration_date, true)}</strong>, e a aprovação leva até 12h para ser feita em nossa loja.</p>
    </div>

    <p>O valor do seu PIX é: <strong>{Tools::displayPrice($transaction->value_invoiced)}</strong>

    <div>
        <iframe id="pix-qrcode" src="{$transaction->pix_qrcode_url}" data-pix-data="{$transaction->pix_qrcode_hash}"></iframe>
        <center>
            <a class="btn btn-primary copyPix" href='#'><i class="icon-barcode"></i>Copiar código PIX</a>
        </center>
    </div>
{/if}
{/block}