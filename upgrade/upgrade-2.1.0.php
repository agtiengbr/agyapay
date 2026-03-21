<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_1_0()
{
    $modelInstance = new AgYaPayRequest;
    $modelInstance->createDatabase();
    $modelInstance->createMissingColumns();
    $modelInstance->createIndexes();

	$tab = new Tab;
	$tab->module     = 'agyapay';
	$tab->active     = 1;
	$tab->class_name = 'AdminAgYaPayRequest';

    //cria abas ps 1.6
    if (version_compare(_PS_VERSION_, '1.7', '<')) {
    	$tab->id_parent  = Tab::getIdFromClassName('AdminParentModules');

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'YaPay Requisições';
        }       
    } else {
    	$tab->id_parent  = Tab::getIdFromClassName('AdminParentModulesSf');

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'YaPay Requisições';
        }
    }
	
    $tab->save();
    
    return true;
}