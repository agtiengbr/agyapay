<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_1_5_0()
{
    $modelInstance = new AgYapayAffiliate;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();
	
    return true;
}
