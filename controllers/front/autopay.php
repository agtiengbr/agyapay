<?php
class agyapayautopayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        //evita duplicidade de pagamentos
        $semId = ftok(__FILE__, "s");
        $sem = sem_get($semId, 1);
        sem_acquire($sem);

        echo "Para informações consulte " . _PS_MODULE_DIR_ . $this->module->name . "/logs/autopay.log";

        AgClienteLogger::createLogger(_PS_MODULE_DIR_ . 'agyapay/logs/autopay.log', 1);
        AgClienteLogger::addLog("Iniciando processamento das cobranças automáticas");

        $status = (int)Configuration::get('AGYAPAY_STATUS_FOR_AUTO_BILL');
        if (!$status) {
            AgClienteLogger::addLog("Abortado, porque o estado para cobrança automática não está mapeado.");
            sem_release($sem);
            exit();
        }

        if (!Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT')) {
            AgClienteLogger::addLog("Abortado, porque o cofre não está ativado.");
            sem_release($sem);
            exit();
        }

        $sql = new DbQuery;
        $sql->from('orders')
            ->where('current_state=' . $status);

        $orders = Db::getInstance()->executeS($sql);
        AgClienteLogger::addLog(count($orders) . ' encontradas.');
        foreach ($orders as $order) {
            AgClienteLogger::addLog("Processando pedido $order[id_order]");
            //verifica se ocorreu alguma tentativa nas últimas 24h
            $sql = new DbQuery;
            $sql->from('agyapay_autopay')
                ->where('date_add >="' . date('Y-m-d H:i:s', strtotime('-24h')) . '"');
            $db_data = Db::getInstance()->executeS($sql);
            if (count($db_data)) {
                AgClienteLogger::addLog("Já ocorreu uma tentativa de pagamento desse pedido recentemente.");
                continue;
            }

            //verifica se o cliente possui algum cartão salvo no cofre
            $customer = new Customer($order['id_customer']);
            $cards = AgYapayCreditCard::findByCustomer($customer);
            
            if (count($cards) == 0) {
                AgClienteLogger::addLog("O cliente não possui cartões cadastrados.", 2);
                continue;
            }

            /** @var agyapay */
            $module = $this->module;

            foreach ($cards as $card) {
                try {
                    $transaction = $module->payOrderWithCreditCard(new Order($order['id_order']), [
                        'payment_method_id'  => $card->payment_method_id,
                        'card_token'         => $card->card_token,
                        'cvv'                => $card->cvv,
                        'split'              => 1,
                    ]);

                    if (!in_array($transaction->status_id, [4, 5, 6, 87])) {
                        AgClienteLogger::addLog("Erro no pagamento.", 2);
                        throw new Exception('O seu pagamento não foi autorizado. Por favor revise as informações do seu cartão de crédito e tente novamente.');
                    }
                    AgClienteLogger::addLog("Aprovado ou Em Análise (estado {$transaction->status_id})", 1);

                    $order = new Order($order['id_order']);
                    $order_status = $module->getStatus($transaction->status_id, $order);
                    $order->setCurrentState($order_status);

                    $obj = new AgYapayTransaction;

                    $obj->id_order        = $order->id;
                    $obj->remote_id       = $transaction->transaction_id;
                    $obj->remote_token = $transaction->token_transaction;
                    $obj->value_paid = $transaction->payment->price_payment;
                    $obj->value_invoiced = $transaction->payment->price_original;
                    $obj->type            = 1;
                    $obj->add();
                } catch (Exception $e) {
                    AgClienteLogger::addLog("Erro no processamento do pagamento - " . $e->getMessage(), 3);
                }
            }
        }

        AgClienteLogger::addLog("Encerrando processamento das cobranças automáticas");
        sem_release($sem);
        exit();
    }
}