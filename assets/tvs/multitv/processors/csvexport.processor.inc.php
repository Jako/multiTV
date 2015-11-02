<?php
/*
 * deliCart - a small shop system for MODx.
 *
 * @package deliCart
 * @subpackage snippet main
 * @link http://www.partout.info/nochnicht.html
 *
 * @version 1.0 <01.09.2012>
 * @author Thomas Jakobi <thomas.jakobi@partout.info>
 */
if (MODX_BASE_PATH == '') {
    die('<h1>ERROR:</h1><p>Please use do not access this file directly.</p>');
}

$search = (isset($_POST['search'])) ? $modx->db->escape($_POST['search']) : '';

$where = isset($settings['where']) ? $settings['where'] : '';
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

$export = array();
$keys = array();
$i = 0;
if ($modx->db->getRecordCount($res)) {
    while ($row = $modx->db->getRow($res)) {
        if (!$i) {
            $keysRow = array();
            $keys = array_keys($row);
            foreach ($keys as $value) {
                $keysRow[] = '"' . $value . '"';
            }
            $keys = implode(';', $keysRow);
        }
        $csvRow = array();
        foreach ($row as $key => $value) {
            $csvRow[] = '"' . $value . '"';
        }
        $export[] = implode(';', $csvRow);
        $i++;
    }
}

$export = array_merge(array($keys), $export);
$output = implode("\n", $export);

header('Set-Cookie: fileDownload=true; path=/');
header('Cache-Control: max-age=60, must-revalidate');
header('Content-type: text/plain');
header('Content-disposition: attachment; filename=csvexport' . strftime('%Y-%m-%d') . '.txt');
echo $output;
exit;
