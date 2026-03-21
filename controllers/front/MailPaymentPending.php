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
        $sql->where("o.current_state = ".Configuration::get('AGYAPAY_STATUS_4'));
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
            $history = $order->getHistory(Context::getContext()->language->id, $ps_status)[0];

            $dateStatus = new DateTime($history['date_add']);
            $dateNow = new DateTime();

            $diffDate = $dateStatus->diff($dateNow);
            $daysDiff = $diffDate->format('%a');

            if($daysDiff >= 4){

                $mail_data = array(
                    '{shop_name}' => $this->context->shop->name,
                    '{reference_product}'=>$order->reference
                );
                $emails = preg_split("/\r\n|\n|\r/", Configuration::get('AGYAPAY_PAY_EMAIL_ADMINS'));
                
                foreach ($emails as $email) {
                    $r = Mail::Send(
                        $order->id_lang,
                        'pending_payment',
                        'Pagamento pendente há mais de 4 dias',
                        $mail_data,
                        $email,
                        NULL,
                        null,
                        null,
                        null,
                        null,
                        _PS_MODULE_DIR_ . $module->name . '/mails/',
                        false,
                        $order->id_shop
                    );
                }

                $orderEmail = new AgYapayOrderEmails();
                $orderEmail->id_order = $order->id;
                $orderEmail->save();
            }
        }

        exit();
    }

}