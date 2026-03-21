<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_1_2($module)
{
	Configuration::updateValue('AGYAPAY_MAX_INSTALLMENTS', 12);
	
    return true;
}
