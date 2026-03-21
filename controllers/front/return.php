<?php

class AgYapayReturnModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();

        AgClienteLogger::createLogger(_PS_MODULE_DIR_ . 'agyapay/logs/return.txt', 1);
        AgClienteLogger::addLog('agyapay - webhook recebido.', 1, null, null, null, true);

        $token_transaction = Tools::getValue('token_transaction');
        AgClienteLogger::addLog("agyapay - webhook token {$token_transaction}.", 1, null, null, null, true);
        $local_transaction = AgYapayTransaction::getByRemoteToken($token_transaction);
        if (!Validate::isLoadedObject($local_transaction)) {
            AgClienteLogger::addLog("Recebido webhook de transação não localizada. Token: {$token_transaction}.");
            exit();
        }

        try {
            agyapay::updateLocalTransaction($local_transaction);
            AgClienteLogger::addLog("agyapay - status local atualizado: id {$local_transaction->id}, status_id {$local_transaction->status_id}.", 1, null, null, null, true);
        } catch (Exception $e) {
            AgClienteLogger::addLog("agyapay - erro ao atualizar transação local: {$e->getMessage()}.", 3, null, null, null, true);
        }

        // garante que o pedido do PS acompanhe o estado da transação imediatamente
        try {
            $local_transaction->updatePsStatus();
        } catch (Exception $e) {
            AgClienteLogger::addLog("agyapay - erro ao atualizar pedido via webhook: {$e->getMessage()}.", 3, null, null, null, true);
        }

        exit();
    }
}