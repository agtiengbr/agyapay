{if $order->hasBeenPaid()}
    <p class="alert alert-success">O seu pagamento foi aprovado com sucesso!</p>
{else}
    {if $transaction->type == 0}
        <p class="alert alert-success">Seu boleto foi gerado com sucesso!</p>

        <div class="box">
            <p>
                Você escolheu pagar via Boleto Bancário!
                <br />Referência do seu pedido: <span class="reference"><strong>{$order->reference|escape:'html':'UTF-8'}</strong></span>

                <p>
                    <br />O boleto foi enviado para você por e-mail, mas você também pode imprimí-lo no botão abaixo. O seu pedido será enviado após a confirmação do pagamento do boleto bancário.
                    <br />Se você tem alguma dúvida ou comentário sobre a compra, por favor entre em contato com a nossa <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">equipe especializada de atendimento ao cliente!</a>
                    <br /> O código de barras do seu boleto é <strong class="barcode">{$transaction->bar_code}</strong>
                </p>

                <center>
                    <a class="btn btn-primary copyBarcode" href='#'><i class="icon-barcode"></i>Copiar código de barras</a>
                    <a class="btn btn-primary" href="{$transaction->original_url_payment}" target="_blank"><i class="icon-barcode"></i>Imprimir Boleto</a>
                </center>
            </p>
        </div>
    {else if $transaction->type == 2}
        <p class="alert alert-success">Seu pedido está aguardando pagamento!</p>

        <div class="box">
            <p>
                Você escolheu pagar via Débito em Conta!
                <br />Referência do seu pedido: <span class="reference"><strong>{$order->reference|escape:'html':'UTF-8'}</strong></span>

                <p>
                    Para realizar o pagamento do seu pedido por favor utilize o botão abaixo. O seu pedido será enviado após a confirmação do pagamento do boleto bancário.
                    <br />Se você tem alguma dúvida ou comentário sobre a compra, por favor entre em contato com a nossa <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">equipe especializada de atendimento ao cliente!</a>
                </p>

                <center>
                    <a class="btn btn-default" href="{$transaction->url_payment}" target="_blank"><i class="icon-bank"></i>Pagar Pedido</a>
                </center>
            </p>
        </div>
    {else if $transaction->type == 3}
        <p class="alert alert-success">Seu pedido foi finalizado com sucesso!</p>

        <div class="box">
            <p>Você pode pagar o seu pedido realizando a leitura do QRCode abaixo no aplicativo do seu banco, ou copiando o código clicando no botão abaixo e utilizando a opção de PIX Copia e Cola do seu banco.
            </p>

            <p>O PIX poderá ser pago até <strong>{Tools::displayDate($transaction->pix_expiration_date, null, true)}</strong>, e a aprovação leva poucos minutos para ser feita em nossa loja.</p>
        </div>

        <div>
            <iframe id="pix-qrcode" src="{$transaction->pix_qrcode_url}" data-pix-data="{$transaction->pix_qrcode_hash}"></iframe>
            <center>
                <a class="btn btn-primary copyPix" href='#'><i class="icon-barcode"></i>Copiar código PIX</a>
            </center>
            <a id="pix-message">
                <small>Ou copie o texto abaixo e utilize a opção de PIX Copia e Cola do seu banco.</small>
            </a>
            <p id="pix-message-code">
                <small>
                    <b>Copie o seguinte código para efetuar o pagamento:</b>
                    <br>
                    {$transaction->pix_qrcode_hash}
                </small>
            </p>
        </div>
    {else}
        {if $remote_transaction->status_id == 6}
            <p class="alert alert-success">Seu pagamento foi aprovado com sucesso!</p>

            <div class="box">
                <p>
                    <br />Referência do seu pedido: <span class="reference"><strong>{$order->reference|escape:'html':'UTF-8'}</strong></span>

                    <p>
                        <br /> Seu pedido será enviado em breve!
                        <br />Se você tem alguma dúvida ou comentário sobre a compra, por favor entre em contato com a nossa <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">equipe especializada de atendimento ao cliente!</a>
                    </p>
                </p>
            </div>
        {else if $remote_transaction->status_id == 5 || $remote_transaction->status_id == 87}
            <p class="alert alert-warning">Seu pagamento está em aprovação!</p>

            <div class="box">
                <p>
                    <br />Referência do seu pedido: <span class="reference"><strong>{$order->reference|escape:'html':'UTF-8'}</strong></span>

                    <p>
                        <br /> Seu pedido será enviado após a confirmação do pagamento pela operadora de cartão de crédito.
                        <br />Se você tem alguma dúvida ou comentário sobre a compra, por favor entre em contato com a nossa <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">equipe especializada de atendimento ao cliente!</a>
                    </p>
                </p>
            </div>
        {else}
            <p class="alert alert-danger">Seu pagamento foi recusado!</p>

            <div class="box">
                <p>
                    <br/>Ocorreu um erro ao processar o seu pagamento. Resposta recebida: <strong>{$remote_transaction->payment->payment_response}</strong>.

                    <p>
                        <br />
                        <br />Se você tem alguma dúvida ou comentário sobre a compra, por favor entre em contato com a nossa <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">equipe especializada de atendimento ao cliente!</a>
                    </p>
                </p>
            </div>
        {/if}
    {/if}
{/if}