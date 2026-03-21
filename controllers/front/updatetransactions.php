<?php


class agyapayupdatetransactionsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        AgClienteLogger::createLogger(_PS_MODULE_DIR_ . 'agyapay/logs/updateTransaction.txt', 1);

        $sql = new DbQuery;
        $sql->from('agyapay_transaction')
            ->select('id_agyapay_transaction, status')
            ->where('(status_id IN (4, 5, 87, 0) OR status IS NULL OR status = "") AND date_add BETWEEN "' . date('Y-m-d H:i:s', strtotime("-1 week")) . '" AND "' . date('Y-m-d H:i:s') . '"');

        $transactions = Db::getInstance()->executeS($sql);

        foreach ($transactions as $transaction) {
            try {
                $obj = new AgYapayTransaction($transaction['id_agyapay_transaction']);
                agyapay::updateLocalTransaction($obj);
                // alteração mínima: refletir novo estado da transação no pedido
                $obj->updatePsStatus();
            } catch (Exception $e) {
                AgClienteLogger::addLog("Erro ao atualizar transação - " . $e->getMessage(), 3, null, 'AgYapayTransaction', $obj->id, true);
            }
        }

        echo json_encode([
            'status' => 'success'
        ]);

        exit();
    }
}
