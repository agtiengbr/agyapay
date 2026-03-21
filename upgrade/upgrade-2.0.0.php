<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_0_0($module)
{
    $modelInstance = new AgYapayCreditCard;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();
    
    $modelInstance = new AgYapayAutopay;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();

    $module->registerHook("displayCustomerAccount");
    return true;
}