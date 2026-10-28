<?php

class AgYapayMailPaymentPendingModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();


        $sql = new DbQuery();
        $sql->select('o.*');
        $sql->from('orders', 'o');
        $sql->where("o.module = 'agyapay'");
        $sql->where('o.current_state = ' . (int) Configuration::get('AGYAPAY_STATUS_4'));
        $sql->leftJoin('agyapay_order_emails', 'eml', 'eml.id_order = o.id_order');
        $sql->where('eml.id_agyapay_order_emails IS NULL');

        $db_data = Db::getInstance()->ExecuteS($sql);
        if (!$db_data) {
            $db_data = array();
        }

        $orders = ObjectModel::hydrateCollection('Order', $db_data);
        $module = new agyapay;

        foreach ($orders as $order) {
            $ps_status = $module->getStatus(4, $order);
            $history = $order->getHistory(Context::getContext()->language->id, $ps_status);
            if (empty($history[0]['date_add'])) {
                continue;
            }

            $dateStatus = new DateTime($history[0]['date_add']);
            $dateNow = new DateTime();

            $diffDate = $dateStatus->diff($dateNow);
            $daysDiff = $diffDate->format('%a');

            if ($daysDiff >= 4 && !$diffDate->invert) {
                $module->sendPendingPaymentReminder($order);
            }
        }

        exit();
    }

}
