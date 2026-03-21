document.addEventListener('DOMContentLoaded', function(){
    $('.copyBarcode').click(function(){
        navigator.clipboard.writeText($('.barcode').text()).then(
            () => {
                $(this).text('Copiado com sucesso!');
            },
            () => {
                $(this).text('Ocorreu um erro, tente novamente mais tarde.');
            }
        );

        let oldText = $(this).text();

        let that = this;
        setTimeout(function(){
            $(that).text(oldText);
        }, 4000);

        return false;
    });

    $('.copyPix').click(function(){

        navigator.clipboard.writeText($('#pix-qrcode').attr('data-pix-data')).then(
            () => {
                $(this).text('Copiado com sucesso!');
            },
            () => {
                $(this).text('Ocorreu um erro, tente novamente mais tarde.');
            }
        );

        let oldText = $(this).text();

        let that = this;
        setTimeout(function(){
            $(that).text(oldText);
        }, 4000);

        return false;
    });



    //verifica a atualização do estado do pedido
    let url = new URL(agyapay.links.sse);
    let currentUrl = new URL(window.location.href);

    url.searchParams.append('id_order', currentUrl.searchParams.get('id_order'));

    const source = new EventSource(url.toString());  
    source.addEventListener('paid', e => {
        source.close();
        location.reload();
    });

    source.addEventListener('waiting', e => {
        // alert('esperando');
    });

    source.onmessage = e => console.log('onmessage', e.data);
    source.onerror = e => console.warn('erro SSE', e);

});
