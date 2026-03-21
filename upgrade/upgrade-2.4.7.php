<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_4_7(agyapay $module)
{
    $module->RemakeWorkers();
    
    return true;
}