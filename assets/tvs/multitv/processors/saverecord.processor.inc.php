<?php
$rowId = isset($_POST['rowId']) ? intval($_POST['rowId']) : false;
$value = isset($_POST['value']) ? json_decode($_POST['value'], true) : null;

if ($rowId !== false && $value) {
    $res = $modx->db->select('*', $modx->getFullTableName($settings['table']));
    $dbColumns = $modx->db->getColumnNames($res);

    $saveValue = array();
    foreach ($dbColumns as $dbColumn) {
        if ($dbColumn == 'id') {
            continue;
        }
        if (isset($value[$dbColumn])) {
            $saveValue[$dbColumn] = $value[$dbColumn];
        }
    }

    foreach ($saveValue as $key => $value) {
        if (isset($settings['fields'][$key]['saveaction'])) {
            switch ($settings['fields'][$key]['saveaction']) {
                case 'alias':
                    if ($saveValue[$key] == '' && isset($settings['fields'][$key]['aliasof'])) {
                        $aliasof = $settings['fields'][$key]['aliasof'];
                        $saveValue[$key] = $multiTV->CleanAlias($saveValue[$aliasof]);
                    }
                    break;
            }
        }
    }

    if ($rowId) {
        $answer = $modx->db->update($saveValue, $modx->getFullTableName($settings['table']), 'id =' . $rowId);
    } else {
        $answer = (boolean)$modx->db->insert($saveValue, $modx->getFullTableName($settings['table']));
    }
} else {
    $answer = false;
}
