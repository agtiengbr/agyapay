$(function(){
	$(document).ready(function() {

			setInterval(function() {
				if($('.agcheckout').length > 0){
					validateAllInputsAgCheckout();
				}else{
					validateAllInputs();
				}
			}, 300);

			function validateAllInputsAgCheckout(){

				var has_error = !validateData(true);
				
				if($("[name = 'agyapay_name']").length){
					if ($('#psgdpr').is(':checked').length && !$('#psgdpr').is(':checked')) {
						$has_error = true;
					}
					if(!has_error){
						$(".payment-method-body, .mt-1").parent().parent().next().prop('disabled', false);
					}else{
						$(".payment-method-body, .mt-1").parent().parent().next().prop('disabled', true);
					}
				}else{
					if($(`[name='conditions_to_approve[terms-and-conditions]']`).is(':checked') || $(`[name='conditions_to_approve[terms-and-conditions]']`).length == 0){
						$(".payment-method-body, .mt-1").parent().parent().next().prop('disabled', false);
					}else{
						$(".payment-method-body, .mt-1").parent().parent().next().prop('disabled', true);
					}
				}
				
			};

		function validateAllInputs(){

			var has_error = !validateData(true);
			
			if($("#agyapay_credit_card").parents('.js-payment-option-form').prev().find("[name='payment-option']").is(':checked')){
				if(!has_error && ($(`[name='conditions_to_approve[terms-and-conditions]']`).is(':checked') || $(`[name='conditions_to_approve[terms-and-conditions]']`).length == 0)){
					$('#payment-confirmation').find('button').prop('disabled', false);
				}else{
					$('#payment-confirmation').find('button').prop('disabled', true);
				}
			}
			
		};

		//não calcula as parcelas se não estiver no checkout do yapay
		if ($('[name=agyapay_cardnumber]').length == 0) {
			return;
		}

		if(agyapay.calc_method === 'ws') {
			$('[name=agyapay_cardnumber]').val('4111111111111111');

			var bandeira = getBandeira($('[name=agyapay_cardnumber]').val());

			if (bandeira) {
				document.getElementById('agyapay_cardbanner').innerHTML='<img src="' + agyapay.base_uri + 'modules/agyapay/views/img/cardbanners/'+bandeira+'.png" />';

				loadSplits();
			}

			$('[name=agyapay_cardnumber]').val('');
			document.getElementById('agyapay_cardbanner').innerHTML = '';
		}
	})

	var div_ps16 = document.getElementById('module-agyapay-creditCardt');
	var btn_submit;
	
	function validateData(mark_input = true)
	{
		var error_card_number  = !validateCardNumber(mark_input);
		var error_cvv          = !validateCvv(mark_input);
		var error_name 		   = !validateName(mark_input);
		var error_installments = !validateInstallments(mark_input);
		var error_month 	   = !validateMonth(mark_input);
		var error_year 		   = !validateYear(mark_input);

		if ($('[name=agyapay_credit_card_id]').val()) {
			return !error_installments;
		}
			//se o cliente estiver utilizando um cartão já salvo, valida apenas a escolha do parcelamento
		let has_error = error_card_number || error_cvv || error_name || error_installments || error_month || error_year;
        return !has_error;
	}

	function validateCardNumber(mark_input = true)
	{
		var input_bandeira = $('[name=agyapay_cardnumber]');
		if (!input_bandeira.length) {
			return false;
		}
		
		var bandeira = getBandeira(input_bandeira.val());

		var error = !bandeira;
		if (mark_input) {
			if (error) {
				$(input_bandeira).parent().addClass('has-error');
			} else {
				$(input_bandeira).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateCvv(mark_input = true)
	{
		var input_cvv = $('[name=agyapay_cvv]');
		if (!input_cvv.length) {
			return false;
		}

		var cvv = input_cvv.val();

		var error = cvv.length !== 3 && cvv.length !== 4;

		if (mark_input) {
			if (error) {
				$(input_cvv).parent().addClass('has-error');
			} else {
				$(input_cvv).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateName(mark_input = true)
	{
		var input_name = $('[name=agyapay_name]');
		if (!input_name.length) {
			return false;
		}

		var name = input_name.val().trim();
		var error = name.length == 0;

		if (mark_input) {
			if (error) {
				$(input_name).parent().addClass('has-error');
			} else {
				$(input_name).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateInstallments(mark_input = true)
	{
		var input_installments = $('[name=agyapay_installment]');
		if (!input_installments.length) {
			return false;
		}

		var installments = input_installments.val();
		var error = installments < 0;

		if (mark_input) {
			if (error) {
				$(input_installments).parent().addClass('has-error');
			} else {
				$(input_installments).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateMonth(mark_input = true)
	{
		var input_month = $('[name=agyapay_month]');
		if (!input_month.length) {
			return false;
		}

		var month = input_month.val()
		var error = month < 0;

		if (mark_input) {
			if (error) {
				$(input_month).parent().addClass('has-error');
			} else {
				$(input_month).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateYear(mark_input = true)
	{
		var input_year = $('[name=agyapay_year]');
		if (!input_year.length) {
			return false;
		}
		
		var year = input_year.val();
		var error = year < 0;

		if (mark_input) {
			if (year < 0) {
				$(input_year).parent().addClass('has-error');
			} else {
				$(input_year).parent().removeClass('has-error');
			}
		}

		return !error;
	}

    function getBandeira(cardNumber)
    {
		let mod = new AgYapay();
		return mod.getCardBanner(cardNumber);

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
    	let mod = new AgYapay();
		return mod.getCardBannerCode(card_banner);
    }

    function getSplits(success, error)
    {
    	var data = {};

    	var params = new URL(location).searchParams;
    	var id_order = params.get('id_order');

    	if (id_order) {
    		data.id_order = id_order;
    	}

    	$.ajax({
    		url: agyapay.get_installments_url,
    		dataType : 'JSON',
    		data: data,
    		success: function(data) {
    			if (data.success) {
    				success(data.splits);
    			} else {
    				error(data.error);
    			}
    		},
    		error: function(){
    			error('Ocorreu um erro inesperado.');
    		}
    	})
    }

    function loadSplits()
    {
		var overlay = loadingOverlay().activate();
    	var payment_method_id = $('#agyapay_credit_card').closest('form').find('[name=card_banner]').val();
		
    	var success = function(splits)
    	{
			$('#agyapay_installments').removeAttr('hidden');
			$('#agyapay_installments_list').empty();

    		var options = "";
			let select = $('select[name=agyapay_installment]').length;
			let radio = !select;
    		$.each(splits, function(key, payment_method) {
				if (payment_method.payment_method_id != 5) {
					return;
				}
				
    			$.each(payment_method.splits, function(key, split){
					if (key + 1 > agyapay.max_installments || (key > 0 && parseInt(split.value_split_numeric) < parseInt(agyapay.min_installment_value))) {
						return false;
					}

					if (radio) {
						options += `<div class="agyapay_installment_container">
										<label for="agyapay_installments_${(key + 1)}">${(key + 1)}x R$&nbsp;${split.value_split} (Total de R$&nbsp;${split.value_transaction})</label>
										<input type="radio" name="agyapay_installment" value="${split.split}" id="agyapay_installments_${key + 1}">
									</div>`;
					} else {
						options += `<option value="${split.split}">${split.split}x R$ ${split.value_split} (Total de R$ ${split.value_transaction})</option>"`;
					}
    			});
				
				if (radio) {
    				$('#agyapay_installments_list').html(options);
				} else {
					$('select[name=agyapay_installment]').html(options);
				}
    			return false;
    		});

    		loadingOverlay().cancel(overlay);
    	};

    	var error = function(error_message)
    	{
    		loadingOverlay().cancel(overlay);
    	}

    	getSplits(success, error);
    }

	if ($('#agyapay_existent_order').length) {
		btn_submit = document.querySelector('#agyapay_existent_order .btn-primary');
	} else if (div_ps16 !== null) {
		btn_submit = document.getElementById('cart_navigation').querySelector('button');
	} else {
		btn_submit = document.querySelector('#payment-confirmation .btn-primary');
	}


	$('#agyapay_credit_card [name=agyapay_cardnumber]').mask('0000 0000 0000 0099');
	$('#agyapay_credit_card [name=agyapay_cvv]').mask('0009');

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

		var input_fingerprint = form.find('[name=fingerprint]');
		if (input_fingerprint.length == 0) {
			input_fingerprint = $('<input/>', {
				type: 'hidden',
				name: 'fingerprint'
			});
		}

		input_fingerprint.val($('#agyapay_fingerprint_form [name=finger_print]').val());
	});


	$('#agyapay_credit_card select').change(function(){
		if ($(this).val() > 0) {
			$(this).removeClass('is-not-selected').addClass('is-selected');
		} else {
			$(this).removeClass('is-selected').addClass('is-not-selected');
		}
	});

	$('#agyapay_credit_card input, #agyapay_credit_card select').change(function(e){
		var has_error = !validateData(false);
		var terms = document.getElementById('conditions_to_approve[terms-and-conditions]');

		if (!has_error && ((terms != null && terms.checked) || terms == null)){
			$(btn_submit).removeAttr('disabled');
		} else {
			$(btn_submit).attr('disabled', 'disabled');
		}

		if (e.target.name == 'agyapay_cardnumber') {
			validateCardNumber();
		} else if (e.target.name == 'agyapay_cvv') {
			validateCvv();
		} else if (e.target.name == 'agyapay_name') {
			validateName();
		} else if (e.target.name == 'agyapay_installment') {
			validateInstallments();
		} else if (e.target.name == 'agyapay_month') {
			validateMonth();
		} else if (e.target.name == 'agyapay_year') {
			validateYear();
		}
	});



	$('#agyapay_credit_card .nav-item').click(function(){
		$(this).siblings().removeClass('active');

		if ($('[name=agyapay_cardnumber]').is(':visible')) {
			$('#agyapay_use_existent_card').val('0');
		} else {
			$('#agyapay_use_existent_card').val('1');
		}

		return true;
	});

	$('#agyapay_credit_card_change_card').click(function(){
		$('#agyapay_credit_card_form').removeClass('hidden');
		$('#agyapay_credit_card_selection').addClass('hidden');	
		$('[name=payment_mode]').val(0);

		return false;
	})


	//abas do form de cartão de crédito
	$(document).on('click', '#agyapay_credit_card .nav-item', function(){
		$(this).siblings().removeClass('active');
		$(this).addClass('active');

		$('#agyapay_tabs_content > div').removeClass('active');
		$($(this).attr('href')).addClass('active');
	});
});