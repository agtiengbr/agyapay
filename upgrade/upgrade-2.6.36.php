<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_6_36($module)
{
    $db = Db::getInstance();
    $prefix = _DB_PREFIX_;
    $groupName = pSQL($module->name . '_return');

    // remove workers spawned for the deprecated async return processing
    $db->execute('DELETE FROM ' . $prefix . 'agworker WHERE group_name = "' . $groupName . '"');

    $db->execute(
        'DELETE FROM ' . $prefix . 'agworker_group_shop WHERE id_agworker_group IN (
            SELECT id_agworker_group FROM ' . $prefix . 'agworker_group WHERE group_name = "' . $groupName . '"
        )'
    );

    $db->execute('DELETE FROM ' . $prefix . 'agworker_group WHERE group_name = "' . $groupName . '"');

    // bump key of main agcliente worker to force restart and reload worker list
    $mainGroupId = (int) $db->getValue('SELECT id_agworker_group FROM ' . $prefix . 'agworker_group WHERE group_name = "agcliente_main"');
    if ($mainGroupId > 0) {
        $newKey = pSQL(uniqid());
        $db->execute('UPDATE ' . $prefix . 'agworker_group_shop SET key_for_workers = "' . $newKey . '" WHERE id_agworker_group = ' . $mainGroupId);
    }

    return true;
}
