<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_0_7()
{
    Configuration::updateValue('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD', 'ws');

    return true;
}