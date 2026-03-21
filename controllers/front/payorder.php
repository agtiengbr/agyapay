<?php

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class agyapaypayorderModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if (!Configuration::get('AGYAPAY_PAY_CLOSED_ORDERS')) {
            Tools::redirect($this->context->link->getPageLink('pagenotfound'));
            exit();
        }

        $id = Tools::getValue('id_order');
        $order = new Order($id);
        if (!Validate::isLoadedObject($order)) {
            goto redirect;
        }

        if ($order->current_state != Configuration::get('AGYAPAY_STATUS_4')) {
            goto redirect;
        }

        if ($order->id_customer != $this->context->customer->id) {
            goto redirect;
        }

        if (Configuration::get("AGYAPAY_TICKET_ACTIVE")) {
            $ticket_form = $this->module->generateTicketForm(
                version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($order->total_paid_tax_incl) : (new PriceFormatter)->format($order->total_paid_tax_incl)
            );
            $this->context->smarty->assign(['ticket_form' => $ticket_form]);
        }

        if (Configuration::get("AGYAPAY_CREDIT_CARD_ACTIVE")) {
            $ccForm = $this->module->generateCreditCardForm(
                version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($order->total_paid_tax_incl) : (new PriceFormatter)->format($order->total_paid_tax_incl)
            );
            $this->context->smarty->assign(['cc_form' => $ccForm]);
        }

        if (Configuration::get("AGYAPAY_PIX_ACTIVE")) {
            $pixForm = $this->module->generatePixForm(
                version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($order->total_paid_tax_incl) : (new PriceFormatter)->format($order->total_paid_tax_incl)
            );
            $this->context->smarty->assign(['pix_form' => $pixForm]);
        }
       
        $products = $order->getProducts();
        foreach ($products as $i=>$product) {
            $product['id_product_attribute'] = $product['product_attribute_id'];
            $prod = Product::getProductProperties($this->context->language->id, $product);
            $img = $this->context->link->getImageLink($prod['link_rewrite'], $prod['id_image'], 'small_default');
            $products[$i]['image'] = $img;
            $products[$i]['price_formatted'] = version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($product['product_price_wt']) : (new PriceFormatter)->format($product['product_price_wt']);
        }

        $this->context->smarty->assign([
            'order' => $order,
            'payorder_customer' => new Customer($order->id_customer),
            'products' => $products,
            'total_products_wt' => version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($order->total_products_wt) : (new PriceFormatter)->format($order->total_products_wt),
            'total_shipping_tax_incl' => version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($order->total_shipping_tax_incl) : (new PriceFormatter)->format($order->total_shipping_tax_incl),
            'total_paid_tax_incl' => version_compare(_PS_VERSION_, '9', '<') ? Tools::displayPrice($order->total_paid_tax_incl) : (new PriceFormatter)->format($order->total_paid_tax_incl),
        ]);
        
        $this->setTemplate('module:agyapay/views/templates/front/payorder.tpl');
        return;

        redirect:
            Tools::redirect($this->context->link->getPageLink('history'));
            exit();
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->context->controller->registerStylesheet(
            'yapay-payorder',
            'modules/agyapay/views/css/payorder.css'
        );

        $this->context->controller->registerJavascript(
            'lib-credit-card',
            'modules/agyapay/views/js/lib/creditcard.min.js'
        );

        $this->context->controller->registerJavascript(
            'yapay-credit-card',
            'modules/agyapay/views/js/agyapay_credit_card.ps17.js'
        );

        $this->context->controller->registerJavascript(
            'yapay-ticket',
            'modules/agyapay/views/js/agyapay_ticket.ps17.js'
        );

        $this->context->controller->registerJavascript(
            'yapay-fingerprint',
            'modules/agyapay/views/js/lib/fingerprint.js'
        );

        $this->context->controller->registerJavascript(
            'yapay-lib',
            'modules/agyapay/views/js/agyapay.js'
        );

        $this->context->controller->registerJavascript(
            'yapay-agcheckout',
            'modules/agyapay/views/js/front/agcheckout.js'
        );

        $this->context->controller->registerJavascript(
            'yapay-payorder',
            'modules/agyapay/views/js/front/payorder.js'
        );
    }
}