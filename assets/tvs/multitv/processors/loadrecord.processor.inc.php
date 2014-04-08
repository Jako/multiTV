<?php
$rowId = isset($_POST['rowId']) ? intval($_POST['rowId']) : false;

if ($rowId) {
    $res = $modx->db->select('*', $modx->getFullTableName($settings['table']), 'id =' . $rowId);
    if ($modx->db->getRecordCount($res)) {
        $row = $modx->db->getRow($res);
        $answer = $row;
    } else {
        $answer = false;
    }
} else {
    $answer = false;
}
