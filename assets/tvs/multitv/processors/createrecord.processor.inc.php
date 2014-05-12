<?php
/**
 * multiTV
 *
 * @category    processor
 * @version     2.0 beta 1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * Create record processor
 */
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
