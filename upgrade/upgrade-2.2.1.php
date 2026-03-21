<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_2_1()
{
    $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'agyapay_transaction ADD COLUMN id_cart int AFTER id_order';
    try {
        Db::getInstance()->execute($sql);
    } catch (Exception $e) {
    }

    return true;
}
