<article class="box">
    <center>
        <div class="w-50">
            <br>
            Você pode pagar o seu pedido pelo aplicativo do seu banco realizando a leitura
            do QRCode abaixo ou copiando o código clicando no botão a 
            seguir e utilizando a opção de PIX Copia e Cola.
            <br><br>
            O PIX poderá ser pago até <b>{Tools::displayDate($transaction->pix_expiration_date, true)}</b>, e a aprovação leva até 12h para ser feita em nossa loja.
            <br><br><br>
            <iframe id="pix-qrcode" src="{$transaction->pix_qrcode_url}" data-pix-data="{$transaction->pix_qrcode_hash}"></iframe>
            <br>
            <a class="btn btn-primary copyPix" href='#'><i class="icon-barcode"></i>Copiar código PIX</a>
            <br><br>
        </div>
    </center>
</article> 
