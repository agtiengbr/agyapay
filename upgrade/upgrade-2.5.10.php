<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_5_10()
{
    Configuration::updateValue('AGYAPAY_VIRTUAL_PRODUCTS', 1);
    return true;
}