<?php
use PrestaShop\PrestaShop\Core\Domain\Order\Command\AddCartRuleToOrderCommand;

class AgYapayValidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!$this->module->active) {
            exit();
        }

        if (Tools::getValue('action') !== 'pay_existent_order') {
            $cart = $this->context->cart;
            if (
                $cart->id_customer == 0
                || $cart->id_address_delivery == 0
                || $cart->id_address_invoice == 0
                || !$this->module->active
            ) {
                Tools::redirect('index.php?controller=order&step=1');
            }
        }

        $this->doPayment();
    }

    protected function doPayment()
    {
        //pagamento de pedido já existente
        if (Tools::getValue('action') === 'pay_existent_order') {
            if (!Configuration::get('AGYAPAY_PAY_CLOSED_ORDERS')) {
                Tools::redirect($this->context->link->getPageLink('pagenotfound'));
                exit();
            }
            
            $id_order = Tools::getValue('id_order');
            $order = new Order($id_order);
            $cart = new Cart($order->id_cart);

            $payment_mode = Tools::getValue('payment_mode');

            if ($payment_mode == 'ticket') {
                try {
                    $validation = $this->module->userMayCreateTicketForOrder($this->context->customer, $id_order);                
                    if ($validation !== true) {
                        throw new Exception($validation);
                    }

                    //se já existe um boleto para o pedido
                    $ticket_link = $this->module->getTicketLinkForOrder($order);

                    if ($ticket_link) {
                        goto ticket_found;
                    }

                    $ticket_link = $this->module->createTicketForOrder($order);
                    $this->module->sendTicketMailForOrder($order);

                    Db::getInstance()->update('agyapay_transaction', ['deprecated' => 1], 'id_order=' . (int)$id_order);

                    ticket_found:
                    echo json_encode([
                        'success' => true,
                        'url' => $ticket_link
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($payment_mode == 'pix') {
                try {
                    $validation = $this->module->userMayCreateTicketForOrder($this->context->customer, $id_order);                
                    if ($validation !== true) {
                        throw new Exception($validation);
                    }

                    //se já existe um PIX para o pedido
                    $pix = $this->module->getPixTransactionForOrder($order);
                    if (Validate::isLoadedObject($pix)) {
                        goto pix_found;
                    }

                    $pix = $this->module->createPixForOrder($order);
                    // $this->module->sendPixMailForOrder($order);

                    Db::getInstance()->update('agyapay_transaction', ['deprecated' => 1], 'id_order=' . (int)$id_order . ' AND id_agyapay_transaction!=' . (int)$pix->id);
                    $this->module->sendMailPixForOrder($id_order);
                    pix_found:
                    echo json_encode([
                        'success' => true,
                        'url' => $this->context->link->getModuleLink('agyapay', 'pix', ['hash' => $pix->remote_token]),
                        'pix' => $pix
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                try {
                    $transaction = $this->module->payOrderWithCreditCard($order, [
                        'payment_method_id'  => Tools::getValue('card_banner'),
                        'card_name'          => Tools::getValue('agyapay_name'),
                        'card_number'        => Tools::getValue('agyapay_cardnumber'),
                        'expiration_month'   => Tools::getValue('agyapay_month'),
                        'expiration_year'    => Tools::getValue('agyapay_year'),
                        'cvv'                => Tools::getValue('agyapay_cvv'),
                        'split'              => Tools::getValue('agyapay_installment'),
                    ]);

                    if (!in_array($transaction->status_id, [5, 6, 87])) {
                        throw new Exception('O seu pagamento não foi autorizado. Por favor revise as informações do seu cartão de crédito e tente novamente.');
                    }

                    Db::getInstance()->update('agyapay_transaction', ['deprecated' => 1], 'id_order=' . (int)$id_order);

                    $obj = new AgYapayTransaction;

                    $obj->id_order        = $id_order;
                    $obj->remote_id       = $transaction->transaction_id;
                    $obj->remote_token    = $transaction->token_transaction;
                    $obj->type            = 1;

                    $obj->add();

                    Tools::redirect('index.php?controller=order-confirmation&id_cart='.$order->id_cart.'&id_module='.$this->module->id.'&id_order='.$order->id.'&key='.$this->context->customer->secure_key);
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                    $this->redirectWithNotifications($this->context->link->getModuleLink('agyapay', 'payorder', ['id_order' => $id_order]));
                }
            }

            exit();
        }
        
        try {
            if (function_exists("sem_get")) {
                $semId = ftok(__FILE__, "s");
                $sem = sem_get($semId, 1);
                sem_acquire($sem);
            }

            
            $cart = $this->context->cart;

            if (Order::getIdByCartId($cart->id)) {
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.Order::getIdByCartId($cart->id).'&key='.$this->context->customer->secure_key);
                exit();
            }
            
            if (Tools::getValue('payment_mode') == 'ticket') {
                if(Configuration::get('AGYAPAY_TICKET_DISCOUNT') > 0) {
                    $this->createDiscountForTicket();
                }

                $this->removePixDiscount();
                
                $response = $this->module->createTicketForCart($cart);
                $transaction = $response['transaction'];
                $expiration_date = $response['expiration_date'];

                $payment_mode = 'Vindi - Boleto Bancário';
            } elseif (Tools::getValue('payment_mode') == 'pix') {
                $this->removeTicketsDiscount();
                
                if(Configuration::get('AGYAPAY_PIX_DISCOUNT') > 0){
                    $this->createDiscountForPix();
                }

                $response = $this->module->createPixForCart($cart);
                $transaction = $response['transaction'];
                $payment_mode = 'Vindi - PIX';
            } else {
                $this->removeTicketsDiscount();
                $this->removePixDiscount();

                //salvamento dos dados do cartão do cliente
                if (
                    Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT') == 1 &&
                    Tools::getValue('agyapay_use_existent_card') != 1 &&
                    Tools::getValue('agyapay_save_credit_card') == 1
                ) {
                    $this->module->saveCreditCard(
                        Tools::getValue('agyapay_cardnumber'),
                        Tools::getValue('agyapay_cvv'),
                        Tools::getValue('agyapay_name'),
                        Tools::getValue('card_banner'),
                        Tools::getValue('agyapay_month'),
                        Tools::getValue('agyapay_year')
                    );
                }

                if (Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT') && Tools::getValue('agyapay_credit_card_id')) {
                    $credit_card = new AgYapayCreditCard(Tools::getValue('agyapay_credit_card_id'));
                    if ($credit_card->id_customer != $this->context->customer->id) {
                        AgClienteLogger::addLog("O cliente {$this->context->customer->id} tentou comprar com um cartão de crédito salvo que não o pertence. Possivel tentativa de fraude.", 3, null, null, null, true);
                        throw new Exception("Ocorreu um erro ao validar o seu certão. Por favor tente novamente com outro cartão de crédito.");
                    }

                    $transaction = $this->module->payCartWithCreditCard($cart, [
                        'payment_method_id'  => $credit_card->payment_method_id,
                        'card_token'         => $credit_card->card_token,
                        'cvv'                => $credit_card->cvv,
                        'split'              => Tools::getValue('agyapay_installment'),
                    ]);
                } else {
                    $transaction = $this->module->payCartWithCreditCard($cart, [
                        'payment_method_id'  => Tools::getValue('card_banner'),
                        'card_name'          => Tools::getValue('agyapay_name'),
                        'card_number'        => Tools::getValue('agyapay_cardnumber'),
                        'expiration_month'   => Tools::getValue('agyapay_month'),
                        'expiration_year'    => Tools::getValue('agyapay_year'),
                        'cvv'                => Tools::getValue('agyapay_cvv'),
                        'split'              => Tools::getValue('agyapay_installment'),
                    ]);
                }

                $payment_mode = 'Vindi - Cartão ' . Tools::getValue('agyapay_installment') . 'x';

                if (!in_array($transaction->status_id, [5, 6, 87])) {
                    if (function_exists("sem_get")) {
                        sem_release($sem);
                    }
                    
                    throw new Exception('O seu pagamento não foi autorizado. Por favor revise as informações do seu cartão de crédito e tente novamente.');
                }

                //obtém o valor da parcela
                $installments = $this->module->calcInstallments([
                    'value' => $cart->getOrderTotal()
                ]);

                $value = $installments[Tools::getValue('agyapay_installment') - 1]['installment_value'];
            }

            $account = AgYaPaySellerAccountCarrier::getAccountByCarrier($cart->id_carrier);
            $obj = new AgYapayTransaction;

            $obj->remote_id       = $transaction->transaction_id;
            $obj->remote_token = $transaction->token_transaction;
            $obj->value_paid = $transaction->payment->price_payment;
            $obj->value_invoiced = $transaction->payment->price_original;
            $obj->id_cart = $cart->id;
            $obj->qty_installments = Tools::getValue('agyapay_installment') ?: 1;
            $obj->installment_value = @$value?: $cart->getOrderTotal();
            $obj->id_agyapay_seller_account = $account->id;

            $hash_bank_slip = sha1($cart->id . strtotime(date('Y-m-d H:i:s')));
            $path = _PS_MODULE_DIR_ . $this->module->name . '/files/boletos/';
            
            if(!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $params = [
                'hash' => $hash_bank_slip
            ];

            $bank_slip_file = $this->context->link->getModuleLink('agyapay', 'bankslip', $params) ;

            if (Tools::getValue('payment_mode') == 'ticket') {
                $obj->type            = 0;
                $obj->expiration_date = $expiration_date->format('Y-m-d H:i:s');
                $obj->url_payment     = $bank_slip_file;
                $obj->hash_bank_slip  = $hash_bank_slip;
                $obj->original_url_payment  = $transaction->payment->url_payment;
            } elseif (Tools::getValue('payment_mode') == 'pix') { 
                $obj->type = 3;
                $obj->pix_qrcode_hash = $transaction->payment->qrcode_original_path;
                $obj->pix_qrcode_url = $transaction->payment->qrcode_path;
                $obj->pix_expiration_date = str_replace('T', ' ', $transaction->max_days_to_keep_waiting_payment);


                if (!$obj->pix_qrcode_url) {
                    throw new \Exception("Ocorreu um erro ao finalizar a sua transação. Por favor, aguarde até 15 segundos e tente novamente.");
                }
            } else {
                $obj->type = 1;
            }

            try {
                $file = $path . $hash_bank_slip . '.pdf';
                $ch = curl_init();
                /**
                * Set the URL of the page or file to download.
                */
                curl_setopt($ch, CURLOPT_URL, $transaction->payment->url_payment);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $fp = fopen($file, 'w+');
                /**
                * Ask cURL to write the contents to a file
                */
                curl_setopt($ch, CURLOPT_FILE, $fp);
    
                curl_exec($ch);
    
                curl_close ($ch);
                
                fclose($fp);
            } catch (Exception $e) {
                if (function_exists("sem_get")) {
                    sem_release($sem);
                }
                throw $this->module->formatError($e);
            }

            $obj->add();
            $order_status = $this->module->getStatus($transaction->status_id, new Order());
            $this->module->validateOrder($cart->id, $order_status, $cart->getOrderTotal(), $payment_mode, NULL, NULL, (int)$this->context->currency->id, false, $this->context->cart->secure_key);

            if (version_compare(_PS_VERSION_, '9', '<')) {
                $ps_order = new Order(Order::getOrderByCartId($cart->id));
            } else {
                $ps_order = new Order(Order::getByCartId($cart->id));
            }
            Db::getInstance()->update('agyapay_transaction', ['deprecated' => 1], 'id_order=' . (int) $this->module->currentOrder);

            $obj->id_agyapay_transaction = $obj->id;
            $obj->id_order = $this->module->currentOrder;

            if ($transaction->payment->linha_digitavel) {
                $obj->bar_code = preg_replace('/[^0-9]/', '', $transaction->payment->linha_digitavel);
            }

            
            $obj->update();

            if (Tools::getValue('payment_mode') == 'ticket') {
                $this->module->sendTicketMailForOrder($ps_order);
            }

            if (Tools::getValue('payment_mode') == 'pix') {
                $this->module->sendMailPixForOrder($ps_order->id);
            }

            if (function_exists("sem_get")) {
                sem_release($sem);
            }

            Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$ps_order->id.'&key='.$this->context->customer->secure_key);
        } catch (Exception $e) {
            if (function_exists("sem_get")) {
                sem_release($sem);
            }

            AgClienteLogger::addLog($e->getMessage(), 4);

            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                if (Tools::getValue('payment_mode') === 'ticket') {
                    $link = $this->context->link->getModuleLink('agyapay', 'ticket', array('error' => 1));
                } else {
                    $link = $this->context->link->getModuleLink('agyapay', 'creditCard', array('error' => 1));
                }
                
                Tools::redirectLink($link);
            } else {
                if (isset($e->public_message)) {
                    $this->errors[] = $e->public_message;
                } else {
                    $this->errors[] = $e->getMessage();

                }
                $link = $this->context->link->getPageLink('order', true, null, 'step=3');
                $this->redirectWithNotifications($link);
            }
        }

        exit();
    }

    protected function createDiscountForTicket()
    {
        $id_cart_rule = (float) Configuration::get('AGYAPAY_TICKET_CART_RULE');
        
        if ($id_cart_rule) {
            $this->context->cart->addCartRule($id_cart_rule);
            $this->context->cart->save();
            return;
        }

        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto boleto') {
                return;
            }
        }

        $cart_rule = new CartRule();

        foreach (Language::getLanguages() as $lang) {
            $cart_rule->name[$lang['id_lang']] = 'Desconto boleto';
        }

        $cart_rule->id_customer = $this->context->cart->id_customer;
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime("+2 days",strtotime(date('Y-m-d'))));
        $cart_rule->description = 'discount_boleto';
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->priority = 1;
        $cart_rule->partial_use = 1;
        $cart_rule->code = md5('discount_boleto' .$this->context->cart->id_customer . date('Y-m-d H:i:s'));

        $cart_rule->minimum_amount = 0;
        $cart_rule->minimum_amount_tax = 0;
        $cart_rule->minimum_amount_currency = 1;
        $cart_rule->minimum_amount_shipping = 0;
        $cart_rule->country_restriction = 0;
        $cart_rule->carrier_restriction = 0;
        $cart_rule->group_restriction = 0;
        $cart_rule->cart_rule_restriction = 0;
        $cart_rule->product_restriction = 0;
        $cart_rule->shop_restriction = 0;
        $cart_rule->free_shipping = 0;

        $cart_rule->reduction_percent = Configuration::get('AGYAPAY_TICKET_DISCOUNT');

        $cart_rule->reduction_tax = 1;
        $cart_rule->reduction_currency = $this->context->currency->id;
        $cart_rule->reduction_product = 0;

        $cart_rule->gift_product = 0;
        $cart_rule->gift_product_attribute = 0;
        $cart_rule->highlight = 0;
        $cart_rule->active = 1;

        $cart_rule->add();
        $this->context->cart->addCartRule($cart_rule->id);
        
        $this->context->cart->save();
    }

    protected function removeTicketsDiscount()
    {
        $id_cart_rule = (float) Configuration::get('AGYAPAY_TICKET_CART_RULE');
        if ($id_cart_rule) {
            $this->context->cart->removeCartRule($id_cart_rule);
            return;
        }

        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto boleto') {
                $this->context->cart->removeCartRule($rule['id_cart_rule']);
            }
        }
    }

    protected function createDiscountForPix()
    {
        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto PIX') {
                return;
            }
        }

        $cart_rule = new CartRule();

        foreach (Language::getLanguages() as $lang) {
            $cart_rule->name[$lang['id_lang']] = 'Desconto PIX';
        }

        $cart_rule->id_customer = $this->context->cart->id_customer;
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime("+2 days",strtotime(date('Y-m-d'))));
        $cart_rule->description = 'discount_pix';
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->priority = 1;
        $cart_rule->partial_use = 1;
        $cart_rule->code = md5('discount_pix' .$this->context->cart->id_customer . date('Y-m-d H:i:s'));

        $cart_rule->minimum_amount = 0;
        $cart_rule->minimum_amount_tax = 0;
        $cart_rule->minimum_amount_currency = 1;
        $cart_rule->minimum_amount_shipping = 0;
        $cart_rule->country_restriction = 0;
        $cart_rule->carrier_restriction = 0;
        $cart_rule->group_restriction = 0;
        $cart_rule->cart_rule_restriction = 0;
        $cart_rule->product_restriction = 0;
        $cart_rule->shop_restriction = 0;
        $cart_rule->free_shipping = 0;

        $cart_rule->reduction_percent = Configuration::get('AGYAPAY_PIX_DISCOUNT');

        $cart_rule->reduction_tax = 1;
        $cart_rule->reduction_currency = $this->context->currency->id;
        $cart_rule->reduction_product = 0;

        $cart_rule->gift_product = 0;
        $cart_rule->gift_product_attribute = 0;
        $cart_rule->highlight = 0;
        $cart_rule->active = 1;

        $cart_rule->add();
        $this->context->cart->addCartRule($cart_rule->id);

        $this->context->cart->save();
    }

    protected function removePixDiscount()
    {
        $rules = $this->context->cart->getCartRules();

        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto PIX') {
                $this->context->cart->removeCartRule($rule['id_cart_rule']);
            }
        }
    }
}
