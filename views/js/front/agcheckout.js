document.addEventListener('DOMContentLoaded', function(){
    let installments = [];
    prestashop.on('AgCheckout.paymentModesLoaded', async function(){
        $('#agyapay_credit_card [name=agyapay_cardnumber]').mask('0000 0000 0000 0099');
        $('#agyapay_credit_card [name=agyapay_cvv]').mask('0009');

        let agyapay = new AgYapay;
        installments = await agyapay.getInstallments();
    });

    $(document).on('keyup', '[name=agyapay_cardnumber]', function(){
        let obj = new AgYapay;

        var form = $(this).closest('form');
		var input_banner = form.find('[name=card_banner]');
		if (input_banner.length == 0) {
			input_banner = $('<input/>', {
				type: 'hidden',
				name: 'card_banner'
			});
			
			form.append(input_banner);
		}
		
		var bandeira = obj.getCardBanner($(this).val());
		var payment_method_id = obj.getCardBannerCode(bandeira);

		input_banner.val(payment_method_id);
		if (bandeira) {
            document.getElementById('agyapay_cardbanner').innerHTML='<img src="' + agyapay.base_uri + 'modules/agyapay/views/img/cardbanners/'+bandeira+'.png" />';
		}

		var input_fingerprint = form.find('[name=fingerprint]');
		if (input_fingerprint.length == 0) {
			input_fingerprint = $('<input/>', {
				type: 'hidden',
				name: 'fingerprint'
			});
		}

		input_fingerprint.val($('#agyapay_fingerprint_form [name=finger_print]').val());
    });
})