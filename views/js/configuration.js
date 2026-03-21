document.addEventListener('DOMContentLoaded', function(){

    function getQtyInstallments()
    {
        let _return = Math.min(12, $('#agyapay_max_installments').val());

        if (_return == 0) {
            _return = 12;
        }

        return _return;
    }

    function showOrHideInstallments()
    {
        const no_interest_installments = $('#agyapay_credit_card_installments_no_interest').val();

        if ($('[name=agyapay_credit_card_installment_method]:checked').val() != 'local') {
            for (var i=1; i<=12; i++) {
                $(`#agyapay_credit_card_interest_rate_${i}`).closest('.form-group').hide();
            }
        } else {
            for (var i=1; i<= getQtyInstallments(); i++) {
                if(no_interest_installments && no_interest_installments >= i){
                    $(`#agyapay_credit_card_interest_rate_${i}`).val('');
                    $(`#agyapay_credit_card_interest_rate_${i}`).closest('.form-group').hide();
                    
                    continue;
                }

                $(`#agyapay_credit_card_interest_rate_${i}`).closest('.form-group').show();
            }

            for (var i=getQtyInstallments() + 1; i <= 12; i++) {
                $(`#agyapay_credit_card_interest_rate_${i}`).closest('.form-group').hide();
            }
        }
    }
    
    $('#agyapay_credit_card_installments_no_interest').prop('type', 'number').prop('max', 12).prop('min', '0');
    $('[name=agyapay_credit_card_installments_no_interest]').change(showOrHideInstallments);
    $('[name=agyapay_credit_card_installment_method]').change(showOrHideInstallments);
    $('#agyapay_max_installments').on('input', showOrHideInstallments);

    showOrHideInstallments();
});