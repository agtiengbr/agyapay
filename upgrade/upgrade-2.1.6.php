<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_1_6()
{
    $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'agyapay_transaction ADD COLUMN bar_code VARCHAR(255) AFTER url_payment';
    try {
        Db::getInstance()->execute($sql);
    } catch (Exception $e) {
    }

    return true;
}
