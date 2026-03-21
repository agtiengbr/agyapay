<?php

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class AgYapayCreditCardModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!$this->module->active) {
            exit();
        }
        parent::initContent();

        $action = Tools::getValue('action');
        if (method_exists($this, $action)) {
            $this->{$action}();
            exit();
        }

        if (Tools::getValue('id_order')) {
            $this->handleExistentOrder();
            return;
        }

        if (
            //pagamento por cartão desativado
            !Configuration::get('AGYAPAY_CREDIT_CARD_ACTIVE')
            //carrinho de compras vazio
            || @count($this->context->cart->getProducts()) == 0
        ) {
            $link = 'index.php?controller=order&step=3';

            if (Tools::getIsSet('error')) {
                $link .= '&agyapay_error=1';
            }

            Tools::redirect($link);
        }

        $cart = $this->context->cart;

        $total = $cart->getOrderTotal();
        $installments = $this->getInstallments($total);

        $this->context->smarty->assign(array(
            'total' => version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($total) : (new PriceFormatter)->format($total),
            'installments' => $installments,
            'error' => Tools::getIsSet('error')
        ));

        $this->addCss(_PS_MODULE_DIR_ . $this->module->name . '/views/css/credit_card.css');

        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/loadingOverlay.js');
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/jquery.mask.min.js');
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/credit_card.js');

        Media::addJsDef(array(
            'agyapay_base_uri' => $this->context->shop->getBaseURL()
        ));

        $this->setTemplate('credit_card.tpl');
    }

    protected function handleExistentOrder()
    {
        if (!Configuration::get('AGYAPAY_CREDIT_CARD_ACTIVE')) {
            Tools::redirect($this->context->link->getPageLink('history'));           
        }

        $id_order = Tools::getValue('id_order');
        $validation = $this->module->userMayCreateTicketForOrder($this->context->customer, $id_order);

        if ($validation !== true) {
            $this->context->smarty->assign([
                'fatal_error' => $validation
            ]);
        } else {
            $order = new Order($id_order);
            $cart = new Cart($order->id_cart);
        
            $total = $order->total_paid_tax_incl;
            $installments = $this->getInstallments($total);

            $this->context->smarty->assign(array(
                'full' => true,
                'form_action' => $this->context->link->getModuleLink('agyapay', 'validation'),
                'cancel_link' => $this->context->link->getPageLink('history'),
                'total' => version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($total) : (new PriceFormatter)->format($total),
                'error' => Tools::getIsSet('error'),
                'id_order' => $id_order,
                'order_reference' => $order->reference,
                'installments' => $installments
            ));

            $this->addCss(_PS_MODULE_DIR_ . $this->module->name . '/views/css/credit_card.css');

            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/loadingOverlay.js');
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/jquery.mask.min.js');
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/lib/fingerprint.js');
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/lib/creditcard.min.js');
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/agyapay.js');
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/credit_card.js');

            Media::addJsDef(array(
                'agyapay_base_uri' => $this->context->shop->getBaseURL()
            ));
        }

        if ($this->module->ps17 || $this->module->ps8) {
            $this->setTemplate('module:agyapay/views/templates/front/existent_order.credit_card.ps17.tpl');
        } else {
            $this->setTemplate('existent_order.credit_card.tpl');
        }
    }

    protected function getInstallments($total)
    {
        $interest_rate = [];

        for ($i=0; $i<12; $i++) {
            $interest_rate[] = Configuration::get("AGYAPAY_CREDIT_CARD_INTEREST_RATE_$i");
        }

        $options = array(
            'value' => $total,
            'installment_value_min' => Configuration::get('AGYAPAY_CREDIT_CARD_MIN_INSTALLMENT_VALUE'),
            'interest_rate' => $interest_rate,
        );
 
        $installments = $this->module->calcInstallments($options);

        foreach ($installments as $i => $installment) {
            if (version_compare(_PS_VERSION_, '9', '<')) {
                $installments[$i]['installment_value'] = Tools::displayPrice($installments[$i]['installment_value']); 
                $installments[$i]['total'] = Tools::displayPrice($installments[$i]['total']);
            } else {
                $installments[$i]['installment_value'] = (new PriceFormatter)->format($installments[$i]['installment_value']);
                $installments[$i]['total'] = (new PriceFormatter)->format($installments[$i]['total']);
            }
        }

        return $installments;
    }

    protected function simulateSplitting()
    {
        if (Tools::getValue('id_order')) {
            $id_order = Tools::getValue('id_order');
            $order = new Order($id_order);
            $cart = new Cart($order->id_cart);

            $price = $order->total_paid_tax_incl;
        } else {
            $cart = $this->context->cart;
            $price = $cart->getOrderTotal();
        }

        try {
            $splits = $this->module->simulateSplitting($price);

            foreach ($splits as $i=>$payment_mode) {
                foreach ($payment_mode['splits'] as $j=>$split) {
                    $splits[$i]['splits'][$j]['value_split'] = version_compare(_PS_VERSION_, '9', '<') ? 
                        Tools::displayPrice($split['value_split']) : 
                        (new PriceFormatter)->format($split['value_split']);
                    $splits[$i]['splits'][$j]['value_split_numeric'] = $split['value_split'];
                    $splits[$i]['splits'][$j]['value_transaction'] = version_compare(_PS_VERSION_, '9', '<') ? 
                        Tools::displayPrice($split['value_transaction']) : 
                        (new PriceFormatter)->format($split['value_transaction']); 
                    $splits[$i]['splits'][$j]['value_transaction_numeric'] = $split['value_transaction'];
                }
            }

            echo json_encode([
                'success' => true,
                'splits' => $splits
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit();
    }
}
