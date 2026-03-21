<?php

class AgYapaypixModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $remote_token = Tools::getValue('hash');
        if (!$remote_token) {
            exit();
        }

        $transaction = AgYapayTransaction::getByRemoteToken($remote_token);
        if (!Validate::isLoadedObject($transaction) || $transaction->type != 3) {
            $this->context->smarty->assign(['error' => 'PIX não encontrado.']);

            $this->setTemplate('module:agyapay/views/templates/front/pix.tpl');
        } else if (strtotime($transaction->pix_expiration_date) < time()) {

            $expire_date = DateTime::createFromFormat('Y-m-d H:i:s', $transaction->pix_expiration_date);
            $this->context->smarty->assign(['error' => "O PIX  venceu em {$expire_date->format('d/m/Y H:i:s')}."]);

            $this->setTemplate('module:agyapay/views/templates/front/pix.tpl');
        } else {
            $this->context->smarty->assign(['transaction' => $transaction]);
            $this->setTemplate('module:agyapay/views/templates/front/pix.tpl');
        }

        $this->context->controller->addJs([
            _PS_MODULE_DIR_ . 'agyapay/views/js/pix.js',
        ]);
    }
}
