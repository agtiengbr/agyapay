<?php


class agyapayverifytransactionsidzeroModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $transactions = AgYapayTransaction::getAllTransactionsWithoutOrder();
        foreach ($transactions as $transaction) {
            if (!Validate::isLoadedObject($transaction)) {
                continue;
            }

            $ps_order = new Order(Order::getOrderByCartId($transaction->id_cart));
            if (Validate::isLoadedObject($ps_order)) {
                $transaction->id_order = $ps_order->id;
                $transaction->update();
            }
        }

        exit();
    }
}
