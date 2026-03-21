<?php

class AgYapayAutopay extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_autopay',
        'primary'   => 'id_agyapay_autopay',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_autopay' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int unsigned'),
            'id_agyapay_card' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int unsigned'),
            'success' => array('type' => self::TYPE_INT, 'db_type' => 'bool'),
            'error_msg' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'date_add' => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
            'date_upd' => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
        )
    );


    public $id_agyapay_autopay;
    public $id_order;
    public $id_agyapay_card;
    public $success;
    public $error_msg;
    public $date_add;
    public $date_upd;
}