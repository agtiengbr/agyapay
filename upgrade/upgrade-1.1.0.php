<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_0($module)
{
	Configuration::updateValue('AGYAPAY_EFT_DISCOUNT', '0');
	Configuration::updateValue('AGYAPAY_EFT_ACTIVE', 0);
	Configuration::updateValue('AGYAPAY_EFT_TEXT', 'Pagar via Débito em Conta');
	
    return true;
}
