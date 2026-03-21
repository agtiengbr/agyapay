<?php


class agyapayupdateordersModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        AgClienteLogger::createLogger(_PS_MODULE_DIR_ . 'agyapay/logs/updateOrders.txt', 1);

        $ps_status_analysis = $this->module->getStatus(5, new Order());
        $ps_status_waiting_payment = $this->module->getStatus(4, new Order());


        $sql = new DbQuery;
        $sql->from('orders', 'o')
            ->where('current_state IN (' . $ps_status_analysis . ',' . $ps_status_waiting_payment . ')')
            ->where('module="agyapay"')
            ->innerJoin('agyapay_transaction', 't', 't.id_order=o.id_order')
            ->where('t.status_id NOT IN (0, 4, 5)');

        $orders = Db::getInstance()->executeS($sql);
        foreach ($orders as $order) {
            try {
                AgClienteLogger::addLog("Atualizando pedido {$order['id_order']}.", 1);
                $obj = new AgYapayTransaction($order['id_agyapay_transaction']);
                $obj->updatePsStatus();
            } catch (Exception $e) {
                AgClienteLogger::addLog("Erro ao atualizar pedido - " . $e->getMessage(), 3, null, 'Order', $order['id_order'], true);
            }
        }

        echo json_encode([
            'status' => 'success'
        ]);

        exit();
    }
}
