<?php
$rowId = isset($_POST['rowId']) ? intval($_POST['rowId']) : false;

if ($rowId) {
    $answer = $modx->db->delete($modx->getFullTableName($settings['table']), 'id =' . $rowId);
} else {
    $answer = false;
}
