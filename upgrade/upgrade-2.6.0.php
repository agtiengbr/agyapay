<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_6_0($module)
{
    $mod = new agcliente;
    $mod->updateModuleTables($module);


    $account = new AgYapaySellerAccount;
    $account->name = 'Conta Principal.';
    $account->access_token = Configuration::get('AGYAPAY_ACCESS_TOKEN');
    $account->access_token_sandbox = Configuration::get('AGYAPAY_SANDBOX_ACCESS_TOKEN');

    $account->save();

    foreach (Carrier::getCarriers(Context::getContext()->language->id) as $carrier) {
        $asso = new AgYaPaySellerAccountCarrier;
        $asso->id_agyapay_seller_account = $account->id;
        $asso->id_carrier = $carrier['id_carrier'];

        try {
            $asso->save();
        } catch (Exception $e) {
            
        }
    }

    return true;
}