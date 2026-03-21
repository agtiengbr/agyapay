<?php

class AgYapayCronModuleFrontController extends ModuleFrontController
{
    //TODO: como tratar chargebacks? Talvez processar todas as notificações dos últimos 6 meses ao menos 1x ao dia?
    public function __construct()
    {
        parent::__construct();

        // AgClienteLogger::addLog("agyapay - Atualizando estados dos pedidos.", 1, null, null, null, true);

        // $sql = new DbQuery;
        // $sql->from('agyapay_transaction', 't')
        //     ->innerJoin('orders', 'o', 'o.id_order=t.id_order')
        //     //evita que as transações sejam atualizadas indefinidamente
        //     ->where("t.status in ('Aguardando Pagamento') OR t.status = '' OR status is null")
        //     //evita que transações muito antigas sejam processadas para evitar problemas de compatibilidade
        //     ->where("t.date_add > '2020/08/01'")            
        //     //não processa transações que estejam marcadas como expiradas
        //     ->where("t.deprecated = 0 OR t.deprecated is null")
        //     ->where("o.id_order = " . (int)$this->context->shop->id)
        //     ;

        // $transactions = Db::getInstance()->executeS($sql);
        // foreach ($transactions as $transaction) {
        //     $obj = new AgYapayTransaction($transaction['id_agyapay_transaction']);

        //     AgClienteLogger::addLog("agyapay - Transação {$obj->id}.", 1, null, null, null, true);

        //     agyapay::updateLocalTransaction($obj);

        //     $notification = new AgYapayNotification();
        //     $notification->id_agyapay_transaction = $obj->id;
        //     $notification->id_shop = $this->context->shop->id;
        //     $notification->save();
        // }

        // AgClienteLogger::addLog("agyapay - Finalizando atualização dos estados dos pedidos.", 1, null, null, null, true);

        exit();
    }
}