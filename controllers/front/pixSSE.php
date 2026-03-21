<?php

class agyapaypixSSEModuleFrontController extends ModuleFrontController
{
    public $auth = true;

    public function initContent()
    {
        parent::initContent();

        $id_order = Tools::getValue('id_order');
        if (!$id_order) {
            exit();
        }

        //verifica se o pedido é do cliente mesmo
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order) || $order->id_customer != $this->context->customer->id) {
            exit();
        }


        if (headers_sent($file, $line)) {
            die("Headers já enviados."); // ou apenas return se quiser continuar silenciosamente
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');


        while (ob_get_level()) ob_end_clean();
        ob_implicit_flush(true);

        do {    
            Cache::clean('*');
            $order = new Order($id_order);

            if ($order->hasBeenPaid()) {
                echo "event: paid\ndata:\n\n";
                flush();
                break;
            } else {
                echo "event: waiting\ndata:\n\n";
                flush();
            }

            Order::cleanHistoryCache();
            sleep(3);
        } while (true);

        exit();
    }
}