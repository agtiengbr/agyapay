<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_6_28($module)
{
    $db_prefix = _DB_PREFIX_;
    $sql = "ALTER TABLE {$db_prefix}agyapay_seller_account ADD COLUMN notification_url varchar(255)";
    return Db::getInstance()->execute($sql);
}
