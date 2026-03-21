<?php

class agyapaymanagecardsModuleFrontController extends ModuleFrontController
{
    public $auth = false;

    public function initContent()
    {
        parent:: initContent();

        if (!Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT')) {
            Tools::redirect($this->context->link->getPageLink('my-account'));
        }

        if (Tools::getIsSet('saveCard')) {
            $this->saveCard();
        }

        if (Tools::getIsSet('removecard')) {
            $this->removeCard();
        }

        $cards = AgYapayCreditCard::findByCustomer($this->context->customer);
        $this->context->smarty->assign(['cards' => $cards]);        
        $this->setTemplate('module:agyapay/views/templates/front/manage_cards.tpl');
    }

    protected function removeCard()
    {
        $idCard = Tools::getValue('id_card');
        $card   = new AgYapayCreditCard($idCard);

        if (!Validate::isLoadedObject($card)) {
            echo Tools:: jsonEncode(['success' => false, 'error' => 'Cartão não localizado.']);
            exit();
        }

        if ($card->id_customer != $this->context->customer->id) {
            echo Tools:: jsonEncode(['success' => false, 'error' => 'Você não tem permissão sobre o cartão selecionado.']);
            exit();
        }

        $card->delete();
        echo Tools:: jsonEncode(['success' => true]);
        exit();
    }

    protected function saveCard()
    {
        try {
            /** @var agyapay */
            $module = $this->module;
            $module->saveCreditCard(
                Tools::getValue('cardNumber'),
                Tools::getValue('cvv'),
                Tools::getValue('cardHolder'),
                Tools::getValue('paymentMethod'),
                Tools::getValue('expMonth'),
                Tools::getValue('expYear')
            );

            echo json_encode([
                'success' => true
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit();
    }

    public function setMedia()
    {
        parent::setMedia();
        
        $this->addCss(_PS_MODULE_DIR_ . 'agyapay/views/css/card.css');
        $this->addJs(_PS_MODULE_DIR_ . 'agyapay/views/js/card.js');

        $this->addCss(_PS_MODULE_DIR_ . 'agcliente/views/css/agmodal.css');
        $this->addJs(_PS_MODULE_DIR_ . 'agcliente/views/js/agmodal.js');
    }
}