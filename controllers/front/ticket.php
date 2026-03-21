<?php

class AgYapayTicketModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!$this->module->active) {
            exit();
        }
        
        parent::initContent();
        
        if (
            //pagamento por boleto desativado
            !Configuration::get('AGYAPAY_TICKET_ACTIVE')
            //carrinho de compras vazio
            || @count($this->context->cart->getProducts()) == 0
        ) {
            $link = 'index.php?controller=order&step=3';

            if (Tools::getIsSet('error')) {
                $link .= '&agyapay_error=1';
            }

            Tools::redirect($link);
        }

        
        $total = $this->context->cart->getOrderTotal();

        //verifica se o desconto do boleto já foi aplicado ao carrinho
        $has_discount = false;

        $rules = $this->context->cart->getCartRules();
        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto boleto') {
                $has_discount = true;
            }
        }

        if (!$has_discount) {
            $discount = (float) Configuration::get('AGYAPAY_TICKET_DISCOUNT');
            $total_with_discount = $total * (100 - $discount) / 100;
        } else {
            $total_with_discount = $total;
        }

        $this->context->smarty->assign(array(
            'total' => $total,
            'total_with_discount' => $total_with_discount,
            'error' => Tools::getIsSet('error')
        ));

        $this->setTemplate('ticket.tpl');

        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/loadingOverlay.js');
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/ticket.js');
    }
}
