<?php

if (!defined('_PS_VERSION_'))
    exit;

function upgrade_module_2_6_3($module)
{
    Tools::clearCache();

    return true;
}