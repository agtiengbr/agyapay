<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_4_1($module)
{
    $modelInstance = new AgYapayNotification;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();
	
    return true;
}
