<?php

class AgYaPaySellerAccount extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_seller_account',
        'primary'   => 'id_agyapay_seller_account',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_seller_account' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'name' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'account_token' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'account_token_sandbox' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'date_add' => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
            'date_upd' => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
            'access_token' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'access_token_sandbox' => array('type' => self::TYPE_STRING, 'db_type' => 'text'),
            'notification_url' => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)')
        )
    );

    public $id_agyapay_seller_account;
    public $name;
    public $account_token;
    public $account_token_sandbox;
    public $date_add;
    public $date_upd;
    public $access_token;
    public $access_token_sandbox;
    public $notification_url;
}