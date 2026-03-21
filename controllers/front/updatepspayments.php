<?php

class agyapayupdatepspaymentsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        /** @var AgClienteWorker */
        $id_worker = Tools::getValue('id_agworker');

        global $agti_worker;
        $agti_worker = new AgClienteWorker($id_worker);
        $agti_worker->save();

        
        $dbPrefix = _DB_PREFIX_;
        $sql = "
        update {$dbPrefix}agyapay_transaction t
        INNER JOIN {$dbPrefix}orders o ON o.id_order = t.id_order
        INNER JOIN {$dbPrefix}order_payment op ON op.order_reference = o.reference
        SET op.transaction_id = t.remote_id
        WHERE op.transaction_id = '' OR op.transaction_id IS NULL AND t.status_id = 6
        ";
        
        Db::getInstance()->execute($sql);
 
        exit();
    }
}