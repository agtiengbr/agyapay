<?php

class AgYapayupdateTransactionStatusModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        
        AgClienteLogger::createLogger(_PS_MODULE_DIR_ . 'agyapay/logs/UpdateTransactionStatus.txt', 1);

        
        // verificar se a transação atualizou na API e atualizar isso no PS 
        $transactions = AgYapayTransaction::getAllTransactionsWithoutStatus(Configuration::get('AGYAPAY_STATUS_4'));
        $module = new agyapay;
   
        foreach ($transactions as $transaction) {
            $transactionRemote = $module->getTransaction($transaction->remote_token);

            $transaction->status = $transactionRemote->status_name;
            $transaction->status_id = $transactionRemote->status_id;
            $transaction->save();
            $transaction->updatePsStatus();

        }

        exit();
    }

}