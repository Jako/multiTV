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
$columnNames = array();
if (!$settings['configuration']['sorting'] && $settings['configuration']['sortindex']) {
    $columnNames[] = 'MTV_RowId';
}
if ($settings['configuration']['radioTabs']) {
    $columnNames[] = 'fieldTab';
}
foreach ($settings['columns'] as $column) {
    $columnNames[] = $column['fieldname'];
}

$where = isset($settings['where']) ? $settings['where'] : '';
$start = isset($_POST['iDisplayStart']) ? intval($_POST['iDisplayStart']) : 0;
$length = isset($_POST['iDisplayLength']) ? intval($_POST['iDisplayLength']) : 10;
$search = isset($_POST['sSearch']) ? $modx->db->escape($_POST['sSearch']) : '';
$sortby = isset($_POST['iSortCol_0']) ? intval(intval($_POST['iSortCol_0'])) : 1;
$sortdir = (isset($_POST['sSortDir_0']) && strtolower($_POST['sSortDir_0']) == 'asc') ? 'ASC' : 'DESC';

$limit = ($length != -1) ? $start . ',' . $length : $start;

$i = 1;
$orderby = '';
if ($settings['configuration']['sorting']) {
    foreach ($columnNames as $columnName) {
        if ($i == $sortby) {
            $orderby = $columnName . ' ' . $sortdir;
            break;
        }
        $i++;
    }
} elseif ($settings['configuration']['sortindex']) {
    $orderby = $settings['configuration']['sortindex'] . ' ASC';
}

$res = $modx->db->select('*', $modx->getFullTableName($settings['table']), $where);
$totalRecords = $modx->db->getRecordCount($res);

if ($search != '') {
    $res = $modx->db->select('*', $modx->getFullTableName($settings['table']));
    $dbColumns = $modx->db->getColumnNames($res);
    $whereSearch = array();
    foreach ($dbColumns as $dbColumn) {
        if ($dbColumn && $dbColumn != 'id') {
            $whereSearch[] = '`' . $dbColumn . '`' . ' LIKE "%' . $search . '%"';
        }
    }
    $where = (($where != '') ? $where . ' AND ' : '') . '(' . implode(' OR ', $whereSearch) . ')';
}
$res = $modx->db->select('*', $modx->getFullTableName($settings['table']), $where);
$totalDisplayRecords = $modx->db->getRecordCount($res);

$res = $modx->db->select('*', $modx->getFullTableName($settings['table']), $where, $orderby, $limit);
if ($totalDisplayRecords) {
    $displayRecords = $modx->db->makeArray($res);
} else {
    $displayRecords = array();
}

$aaData = array();
$i = 0;
foreach ($displayRecords as $record) {
    $aaData[$i]['id'] = $record['id'];
    if (!$settings['configuration']['sorting'] && $settings['configuration']['sortindex']) {
        $aaData[$i]['DT_RowId'] = (string)$i;
    }
    if ($settings['configuration']['radioTabs']) {
        $aaData[$i]['fieldTab'] = isset($record['fieldTab']) ? $record['fieldTab'] : 0;
    }
    foreach ($columnNames as $columnName) {
        $aaData[$i][$columnName] = $record[$columnName];
    }
    foreach ($settings['columns'] as $column) {
        if (isset($column['render']) && $column['render'] != '') {
            $parser = new newChunkie($modx);
            $parser->setPlaceholders($record);
            $parser->setTpl($column['render']);
            $parser->prepareTemplate();
            $aaData[$i]['mtvRender' . ucfirst($column['fieldname'])] = $parser->process();
        }
    }
    $i++;
}
$answer = array(
    'sEcho' => intval($_POST['sEcho']),
    'iTotalRecords' => $totalRecords,
    'iTotalDisplayRecords' => $totalDisplayRecords,
    "aaData" => $aaData
);
