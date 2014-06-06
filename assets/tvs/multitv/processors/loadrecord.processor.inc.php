<?php
/**
 * multiTV
 *
 * @category    processor
 * @version     2.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * Load record processor
 */
$rowId = isset($_POST['rowId']) ? intval($_POST['rowId']) : false;

if ($rowId) {
    $res = $modx->db->select('*', $modx->getFullTableName($settings['table']), 'id =' . $rowId);
    if ($modx->db->getRecordCount($res)) {
        $row = $modx->db->getRow($res);
        foreach ($settings['fields'] as $fieldname => $field) {
            if ($row[$fieldname] == '' && $field['default'] != '') {
                $field['default'] = str_replace(array('{i}', '{time}'), array($rowId, time()), $field['default']);
                $row[$fieldname] = $field['default'];
            }
            if (isset($field['type'])) {
                switch ($field['type']) {
                    case 'unixtime':
                        $row[$fieldname] = ($row[$fieldname] != '0') ? $modx->toDateFormat($row[$fieldname]) : '';
                        break;
                }
            }
        }
        $answer = $row;
    } else {
        $answer = false;
    }
} else {
    $answer = false;
}
