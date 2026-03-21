document.addEventListener('DOMContentLoaded', function(){
    let modal = new AgModal({content: $('#agyapay-modal')[0]});

    async function removeCardAjax(card_id)
    {
        return new Promise((resolve, reject) => {
            $.ajax({
                url : agyapay.links.manage_card,
                data: {
                    removecard: 1,
                    id_card   : card_id
                },
                dataType: 'JSON'
            })
            .then(function(data){
                if (data.success) {
                    resolve();
                } else {
                    reject(data.error);
                }
            }).fail(reject);
        });
    }

    async function removeCard(card_id)
    {
        try {
            await removeCardAjax(card_id);
            $('[data-card-id=' + card_id + ']').remove();
        } catch(error) {
            alert(error);
        }
    }

    async function ajaxSubmitCard()
    {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: location.href,
                dataType: 'JSON',
                method: 'POST',
                data:  {
                    saveCard: "1",
                    cardNumber: $('[name=agyapay_cardnumber]').val(),
                    cvv: $('[name=agyapay_cvv]').val(),
                    cardHolder: $('[name=agyapay_name]').val(),
                    expYear: $('[name=agyapay_year]').val(),
                    expMonth: $('[name=agyapay_month]').val(),
                    paymentMethod: getCardBannerCode(getBandeira($('[name=agyapay_cardnumber]').val()))
                }
            })
            .then(function(data){
                if (data.success) {
                    resolve(); 
                } else {
                    reject(data.error);
                }
            }).fail(reject);
        });
    }

    async function SubmitCard()
    {
        try {
            $('#agyapay-submit-card').prop('disabled', true);
            await ajaxSubmitCard();
            location.reload();
        } catch (error) {
            $('#agyapay-submit-card').prop('disabled', false);
            alert(error);
        } finally {
        }
    }

    function getBandeira(cardNumber)
    {
        var reg = new RegExp(' ', 'g');
        cardNumber = cardNumber.replace(reg, '');

        var reg = new RegExp('-', 'g');
        cardNumber = cardNumber.replace(reg, '');

        var regexVisa = /^4[0-9]{12}(?:[0-9]{3})?/;
        var regexMaster = /^5[1-5][0-9]{14}/;
        var regexAmex = /^3[47][0-9]{13}/;
        var regexDiners = /^3(?:0[0-5]|[6][0-9])[0-9]{11}/;
        var regexElo = /^(6550|636368|438935|504175|451416|636297)([0-9]{10})$/;
        var regexElo2 = /^(5067|4576|4011)([0-9]{12})$/;
        var regexHipercard = /^(60\d{11})|(60\d{14})|(60\d{17})|(3841\d{11})|(3841\d{14})|(3841\d{17})$/;

        if(!cardNumber) {
            return false;
        }
        
        if(regexVisa.test(cardNumber)) {
            return 'visa';
        }
        
        if(regexMaster.test(cardNumber)) {
            return 'mastercard';
        }
        
        if(regexAmex.test(cardNumber)) {
            return 'amex';
        }
        
        if(regexDiners.test(cardNumber)) {
            if(cardNumber.length==14 | cardNumber.length==16) {
                return 'diners';
            }
        }

        if(regexHipercard.test(cardNumber)) {
            if(cardNumber.length==13 | cardNumber.length==16 | cardNumber.length==19)  {
                return 'hipercard';
            }
        }
        
        if(regexElo.test(cardNumber)) {
            return 'elo';
        }

        if(regexElo2.test(cardNumber)) {
            return 'elo';
        }
        
        return false;
    }

    function getCardBannerCode(card_banner)
    {
    	if (card_banner === 'visa') {
    		return 3;
    	}

    	if (card_banner === 'mastercard') {
    		return 4;
    	}

    	if (card_banner === 'diners') {
    		return 2;
    	}

    	if (card_banner === 'amex') {
    		return 5;
    	}

    	if (card_banner === 'elo') {
    		return 16;
    	}

    	if (card_banner === 'aura') {
    		return 18;
    	}

    	if (card_banner === 'hipercard') {
    		return 20;
    	}

    	if (card_banner === 'hiper') {
    		return 25;
    	}

    	if (card_banner === 'jcb') {
    		return 19;
    	}

    	if (card_banner === 'discover') {
    		return 15;
    	}
    }

    $('.credit-card').click(function(){
        if (confirm("Deseja realmente excluir o seu cartão de crédito?")) {
            removeCard($(this).attr('data-card-id'));
        }
    });

    $('#agapay-new-card').click(function(){
        modal.open();
    })

    $('#agapay-submit-card').click(function(){
        SubmitCard();
    });

    $(document).on('keyup', '[name=agyapay_cardnumber]', function(){
		var form = $(this).closest('form');
		var input_banner = form.find('[name=card_banner]');
		if (input_banner.length == 0) {
			input_banner = $('<input/>', {
				type: 'hidden',
				name: 'card_banner'
			});

			form.append(input_banner);
		}

		var bandeira = getBandeira($(this).val());
		var payment_method_id = getCardBannerCode(bandeira);

		input_banner.val(payment_method_id);
		if (bandeira) {
            document.getElementById('agyapay_cardbanner').innerHTML='<img src="' + agyapay.base_uri + 'modules/agyapay/views/img/cardbanners/'+bandeira+'.png" />';
		}
	});

    $('#agyapay-modal [name=agyapay_cardnumber]').mask('0000 0000 0000 0099');
	$('#agyapay-modal [name=agyapay_cvv]').mask('0009');
});