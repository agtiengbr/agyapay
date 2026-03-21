document.addEventListener('DOMContentLoaded', function(){
    $('.copy').click(function(){
        navigator.clipboard.writeText($('.barcode').text());

        let oldText = $(this).text();
        $(this).text('Copiado com sucesso!');

        let that = this;
        setTimeout(function(){
            $(that).text(oldText);
        }, 4000);

        return false;
    });

})