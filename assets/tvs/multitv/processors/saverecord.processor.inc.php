<?php
$rowId = isset($_POST['rowId']) ? intval($_POST['rowId']) : false;
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    $_POST['value'] = stripslashes($_POST['value']);
}
$value = isset($_POST['value']) ? json_decode($_POST['value'], true) : null;

// get form fields
$formfields = array();
foreach ($settings['form'] as $formtab) {
    foreach ($formtab['content'] as $fieldname => $field) {
        $formfields[$fieldname] = array_merge($settings['fields'][$fieldname], $field);
    }
}

// save form values
if ($rowId !== false && $value) {
    $saveValue = array();
    foreach ($formfields as $fieldname => $field) {
        if (isset($value[$fieldname])) {
            $saveValue[$fieldname] = $value[$fieldname];
            if ($value[$fieldname] == '' && $field['default'] != '') {
                $field['default'] = str_replace(array('{i}', '{time}'), array($rowId, $modx->toDateFormat(time())), $field['default']);
                $saveValue[$fieldname] = $field['default'];
            }
            if (isset($field['type'])) {
                switch ($field['type']) {
                    case 'unixtime':
                        if ($saveValue[$fieldname] != '') {
                            $saveValue[$fieldname] = $modx->toTimeStamp($saveValue[$fieldname]);
                        }
                        break;
                }
            }
            if (isset($field['saveaction'])) {
                switch ($field['saveaction']) {
                    case 'alias':
                        if ($saveValue[$fieldname] == '' && isset($field['aliasof'])) {
                            $saveValue[$fieldname] = $multiTV->CleanAlias($saveValue[$field['aliasof']]);
                        }
                        break;
                }
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
