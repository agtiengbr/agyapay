<?php

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class AgYapaybankslipModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $path = _PS_MODULE_DIR_ . 'agyapay/files/boletos/';
        $internal_path = _PS_MODULE_DIR_ . $this->module->name . '/files/boletos/';

        if (!$this->module->active) {
            exit();
        }

        parent::initContent();

        $hash_bank_slip = Tools::getValue('hash');

        if (!$hash_bank_slip) {
            exit();
        }

        $bank_slip_data = AgYapayTransaction::getBankSlipFile($hash_bank_slip);
        if (!Validate::isLoadedObject($bank_slip_data)) {
            $this->context->smarty->assign(['error' => 'Boleto não encontrado']);

            $this->setTemplate('module:agyapay/views/templates/front/bank_slip.tpl');
        } else if (strtotime($bank_slip_data->expiration_date) < strtotime(date('Y-m-d 00:00:00'))) {

            $expire_date = date('d/m/Y', strtotime($bank_slip_data->expiration_date));

            $this->context->smarty->assign(['error' => "O boleto venceu no dia {$expire_date}"]);

            $this->setTemplate('module:agyapay/views/templates/front/bank_slip.tpl');
        } else {
            if (version_compare(_PS_VERSION_, '9.0.0', '>=')) {
                $priceFormatter = new PriceFormatter();
                $formattedPrice = $priceFormatter->format(
                    $bank_slip_data->value_invoiced,
                    $this->context->currency->iso_code
                );
            } else {
                $formattedPrice = Tools::displayPrice($bank_slip_data->value_invoiced, $this->context->currency);
            }

            $this->context->smarty->assign([
                'transaction' => $bank_slip_data,
                'formattedPrice' => $formattedPrice
            ]);

            $this->setTemplate('module:agyapay/views/templates/front/bank_slip.tpl');
            return;
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJs(_PS_MODULE_DIR_ . 'agyapay/views/js/bankslip.js');
    }
}
