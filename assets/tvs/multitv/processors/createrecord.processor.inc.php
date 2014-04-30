<?php
foreach ($settings['fields'] as $fieldname => $field) {
    if ($field['default'] != '') {
        $row[$fieldname] = str_replace(array('{time}'), array(time()), $field['default']);
    }
    if (isset($field['type'])) {
        switch ($field['type']) {
            case 'unixtime':
                $row[$fieldname] = ($row[$fieldname] != '0' && $row[$fieldname] != '') ? $modx->toDateFormat($row[$fieldname]) : '';
                break;
        }
    }
}
$answer = $row;
