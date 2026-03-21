class AgYapay
{
	constructor()
	{
	}

	getCardBannerCode(card_banner)
	{
		if (card_banner === 'Visa') {
    		return 3;
    	}

    	if (card_banner === 'Mastercard') {
    		return 4;
    	}

    	if (card_banner === 'Diners') {
    		return 2;
    	}

    	if (card_banner === 'Amex') {
    		return 5;
    	}

    	if (card_banner === 'Elo') {
    		return 16;
    	}

    	if (card_banner === 'Aura') {
    		return 18;
    	}

    	if (card_banner === 'Hipercard') {
    		return 20;
    	}

    	if (card_banner === 'hiper') {
    		return 25;
    	}

    	if (card_banner === 'jcb') {
    		return 19;
    	}

    	if (card_banner === 'Discover') {
    		return 15;
    	}
	}

	getCardBanner(cardNumber)
	{
		cardNumber = cardNumber.replaceAll(' ', '');
		let bin = cardNumber.substring(0, 6);

		if (bin == '230650') {
			return 'Mastercard';
		}

		if (bin == '222763') {
			return 'Mastercard';
		}

		if (bin == '234028') {
			return 'Mastercard';
		}

		let lib = new CreditCard();
		let banner = lib.getCreditCardNameByNumber(cardNumber);

		return banner;
	}

	
	async getInstallments()
    {
    	var data = {};

    	var params = new URL(location).searchParams;
    	var id_order = params.get('id_order');

    	if (id_order) {
    		data.id_order = id_order;
    	}

		let r = await axios.get(agyapay.get_installments_url);
		if (r.data.success) {
			return r.data.splits;
		}

		if (typeof data.error !== 'undefned') {
			throw new Error(data.error);
		}

		throw new Error('Ocorreu um erro inesperado.');
    }
}

document.addEventListener('DOMContentLoaded', function(){
	function isSandbox()
	{
		return agyapay.sandbox;
	}

	function initFingerprint()
	{
		if (isSandbox()) {
			var r = window.yapay.FingerPrint({ env: 'sandbox' });
			var f = r.getFingerPrint();
		} else {
			var r = window.yapay.FingerPrint();
		}
	}

	initFingerprint();
});