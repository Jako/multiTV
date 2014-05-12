<?php
/**
 * multiTV
 *
 * @category    processor
 * @version     2.0 beta 1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * Delete record processor
 */
$rowId = isset($_POST['rowId']) ? intval($_POST['rowId']) : false;

if ($rowId) {
    $answer = $modx->db->delete($modx->getFullTableName($settings['table']), 'id =' . $rowId);
} else {
    $answer = false;
}
