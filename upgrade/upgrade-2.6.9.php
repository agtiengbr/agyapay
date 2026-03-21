<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_6_9(agyapay $module)
{
    $modelInstance = new AgYapayOrderEmails;
    $module->RemakeWorkers();

    return true;
}