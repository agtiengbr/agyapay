<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_6_37($module)
{
    if (!Configuration::hasKey('AGYAPAY_VINDI_CARRIER_MODE')) {
        Configuration::updateValue('AGYAPAY_VINDI_CARRIER_MODE', 1);
    }

    if (!Configuration::hasKey('AGYAPAY_VINDI_CARRIER_ID')) {
        Configuration::updateValue('AGYAPAY_VINDI_CARRIER_ID', 0);
    }

    return true;
}

