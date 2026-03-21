<?php

class AgYapayNotification extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_notification',
        'primary'   => 'id_agyapay_notification',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_notification' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_agyapay_transaction' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'id_shop' => ['type' => self::TYPE_INT, 'db_type' => 'int'],
            'status' => ['type' => self::TYPE_INT, 'db_type' => 'int'],
            'date_add' => ['type' => self::TYPE_DATE,'db_type' => 'datetime'],
            'date_upd' => ['type' => self::TYPE_DATE,'db_type' => 'datetime'],
        ),
        'indexes' => array(
            array(
                'name' => 'status',
                'fields' => array('status')
            ),
        )
    );

    public $id_agyapay_notification;
    public $id_agyapay_transaction;
    public $id_shop;

    //0: na fila
    //1: erro
    //2: processada
    public $status;
    public $date_add;
    public $date_upd;

    public static function findNext()
    {
        $sql = new DbQuery;
        $sql->from('agyapay_notification', 'a')
            ->where('status = 0')
            ->orderBy('date_upd ASC')
            ->where("id_shop=" . (int) Context::getContext()->shop->id);

        $return = Db::getInstance()->getRow($sql);

        return $return;
    }

    public function proccess()
    {
        $transaction = new AgYapayTransaction($this->id_agyapay_transaction);

        if (!$transaction->deprecated) {
            $transaction->updatePsStatus();
        }
    }
}