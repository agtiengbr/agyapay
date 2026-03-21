<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_2_3()
{
    $modelInstance = new AgYaPayTransaction;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();
    
    return true;
}