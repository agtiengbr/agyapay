<?php

class AgYapayOrderEmails extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_order_emails',
        'primary'   => 'id_agyapay_order_emails',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_order_emails' => array('type' => self::TYPE_INT, ' idate' => 'isInt'),
            'id_order'  => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'date_add' => array('type' => self::TYPE_DATE,'db_type' => 'datetime')

        )
    );

    public $id_agyapay_order_emails;
    public $id_order;
    public $date_add;


}
