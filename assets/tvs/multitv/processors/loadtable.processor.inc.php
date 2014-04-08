<?php
$columnNames = array();
if (!$settings['configuration']['sorting']) {
    $columnNames[] = 'MTV_RowId';
}
if ($settings['configuration']['radioTabs']) {
    $columnNames[] = 'fieldTab';
}
foreach ($settings['columns'] as $column) {
    $columnNames[] = $column['fieldname'];
}

$where = isset($settings['where']) ? $settings['where'] : '';
$start = isset($_POST['iDisplayStart']) ? $_POST['iDisplayStart'] : 0;
$length = isset($_POST['iDisplayLength']) ? $_POST['iDisplayLength'] : 10;
$search = isset($_POST['sSearch']) ? $modx->db->escape($_POST['sSearch']) : '';
$sortby = isset($_POST['iSortCol_0']) ? intval($_POST['iSortCol_0']) : 1;
$sortdir = (isset($_POST['sSortDir_0']) && strtolower($_POST['sSortDir_0']) == 'asc') ? 'ASC' : 'DESC';
$limit = $start . ',' . $length;

$i = 1;
foreach ($columnNames as $columnName) {
    if ($i == $sortby) {
        $orderby = $columnName . ' ' . $sortdir;
        break;
    }
    $i++;
}

$res = $modx->db->select('*', $modx->getFullTableName($settings['table']), $where);
$totalRecords = $modx->db->getRecordCount($res);

if ($search != '') {
    $dbColumns = $modx->db->getColumnNames($res);
    $whereSearch = array();
    foreach ($dbColumns as $dbColumn) {
        if ($dbColumn != 'id') {
            $whereSearch[] = $dbColumn . ' LIKE "%' . $search . '%"';
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
    if (!$settings['configuration']['sorting']) {
        $aaData[$i]['MTV_RowId'] = $i;
    }
    if ($settings['configuration']['radioTabs']) {
        $aaData[$i]['fieldTab'] = isset($record['fieldTab']) ? $record['fieldTab'] : 0;
    }
    foreach ($columnNames as $columnName) {
        $aaData[$i][$columnName] = $record[$columnName];
    }
    $i++;
}
$answer = array(
    'sEcho' => intval($_POST['sEcho']),
    'iTotalRecords' => $totalRecords,
    'iTotalDisplayRecords' => $totalDisplayRecords,
    "aaData" => $aaData
);
