<?php
/**
 * multiTV
 *
 * @category    processor
 * @version     2.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * Load table processor
 */
foreach ($settings['columns'] as $column) {
    $columnNames[] = $column['fieldname'];
}

$id = isset($_POST['id']) ? intval($_POST['id']) : false;
$fromPosition = isset($_POST['fromPosition']) ? intval($_POST['fromPosition']) : false;
$toPosition = isset($_POST['toPosition']) ? intval($_POST['toPosition']) : false;
$direction = isset($_POST['direction']) ? $_POST['direction'] : false;
$group = isset($_POST['group']) ? intval($_POST['group']) : false;

if ($id !== false && $fromPosition !== false && $toPosition !== false && $direction !== false) {
    $res = $modx->db->select('id, menuindex', $modx->getFullTableName($settings['table']), '', $settings['configuration']['sortindex'], ($fromPosition - 1) . ',1');
    $row = $modx->db->getRow($res);
    $startindex = $row[$settings['configuration']['sortindex']];
    $startid = $row['id'];
    $res = $modx->db->select($settings['configuration']['sortindex'], $modx->getFullTableName($settings['table']), '', $settings['configuration']['sortindex'], ($toPosition - 1) . ',1');
    $endindex = $modx->db->getValue($res);

    if ($direction == 'forward') {
        for ($i = $startindex; $i < $endindex; $i++) {
            $modx->db->update(array($settings['configuration']['sortindex'] => $i), $modx->getFullTableName($settings['table']), $settings['configuration']['sortindex'] . '=' . ($i + 1));
        }
        $modx->db->update(array($settings['configuration']['sortindex'] => $endindex), $modx->getFullTableName($settings['table']), 'id=' . $startid);
    } else {
        for ($i = $startindex; $i > $endindex; $i--) {
            $modx->db->update(array($settings['configuration']['sortindex'] => $i), $modx->getFullTableName($settings['table']), $settings['configuration']['sortindex'] . '=' . ($i - 1));
        }
        $modx->db->update(array($settings['configuration']['sortindex'] => $endindex), $modx->getFullTableName($settings['table']), 'id=' . $startid);
    }
}
$answer = array(
    'sorted' => true
);
