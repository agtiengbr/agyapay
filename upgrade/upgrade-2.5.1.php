<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_5_1(agyapay $module)
{
    $modelInstance = new AgYapayOrderEmails;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();
    $module->RemakeWorkers();

    return true;
}