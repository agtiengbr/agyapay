window.addEventListener('load', function(){
    $('.copyPix').click(function(){
        navigator.clipboard.writeText($('#pix-qrcode').attr('data-pix-data'));

        let oldText = $(this).text();
        $(this).text('Copiado com sucesso!');

        let that = this;
        setTimeout(function(){
            $(that).text(oldText);
        }, 4000);

        return false;
    });
});