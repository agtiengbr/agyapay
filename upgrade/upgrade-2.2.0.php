<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_2_0()
{
    $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'agyapay_transaction ADD COLUMN original_url_payment VARCHAR(255) AFTER url_payment';
    try {
        Db::getInstance()->execute($sql);
    } catch (Exception $e) {
    }

    $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'agyapay_transaction ADD COLUMN hash_bank_slip VARCHAR(255) AFTER url_payment';
    try {
        Db::getInstance()->execute($sql);
    } catch (Exception $e) {
    }

    return true;
}
