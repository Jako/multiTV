<?php
/**
 * multiTV
 *
 * @category    snippet
 * @version     2.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * @internal    description: <strong>2.0</strong> Custom Template Variabe containing a sortable multi item list or a datatable.
 * @internal    snippet code: return include(MODX_BASE_PATH.'assets/tvs/multitv/multitv.snippet.php');
 */
if (MODX_BASE_PATH == '') {
    die('<h1>ERROR:</h1><p>Please use do not access this file directly.</p>');
}

global $modx;

// set customtv (base) path
define('MTV_PATH', str_replace(MODX_BASE_PATH, '', str_replace('\\', '/', realpath(dirname(__FILE__)))) . '/');
define('MTV_BASE_PATH', MODX_BASE_PATH . MTV_PATH);

// include classfile
if (!class_exists('multiTV')) {
    include MTV_BASE_PATH . 'includes/multitv.class.php';
}

// load template variable settings
$tvName = isset($tvName) ? $tvName : '';
$fromJson = isset($fromJson) ? $fromJson : '';

if (!empty($fromJson)) {
    $tvSettings = array(
        'name' => $tvName,
        'value' => $fromJson
    );
} else {
    $res = $modx->db->select('*', $modx->getFullTableName('site_tmplvars'), 'name="' . $tvName . '"');
    $tvSettings = $modx->db->getRow($res);
}
if (!$tvSettings) {
    return 'Template variable ' . $tvName . ' does not exists or parameter fromJson empty.';
}

// pre-init template configuration
$tvSettings['tpl_config'] = (isset($tplConfig)) ? $tplConfig : '';

// init multiTV class
$multiTV = new multiTV($modx, array(
        'type' => 'tv',
        'tvDefinitions' => $tvSettings,
        'tvUrl' => MTV_PATH
    )
);
$templates = $multiTV->templates;

// get snippet parameter
$params = array();
$params['docid'] = (isset($docid)) ? $docid : $modx->documentObject['id'];
$params['outerTpl'] = (isset($outerTpl)) ? $outerTpl : (isset($templates['outerTpl']) ? '@CODE:' . $templates['outerTpl'] : '@CODE:<select name="' . $tvName . '">[+wrapper+]' . ((isset($paginate) && $paginate) ? '[+pagination+]' : '') . '</select>');
$params['emptyOutput'] = (isset($emptyOutput) && !$emptyOutput) ? false : true;
$params['noResults'] = (isset($noResults)) ? $noResults : '';
$params['rowTpl'] = (isset($rowTpl)) ? $rowTpl : (isset($templates['rowTpl']) ? '@CODE:' . $templates['rowTpl'] : '@CODE:<option value="[+value+]">[+key+]</option>');
$params['display'] = (isset($display)) ? $display : 5;
$params['offset'] = (isset($offset)) ? intval($offset) : 0;
$params['rows'] = (isset($rows) && ($rows != 'all')) ? explode(',', $rows) : 'all';
$params['toPlaceholder'] = (isset($toPlaceholder) && $toPlaceholder != '') ? $toPlaceholder : false;
$params['toJson'] = (isset($toJson) && $toJson != '') ? $toJson : false;
$params['randomize'] = (isset($randomize) && $randomize) ? true : false;
$params['reverse'] = (isset($reverse) && $reverse) ? true : false;
$params['orderBy'] = (isset($orderBy)) ? $orderBy : '';
list($params['sortBy'], $params['sortDir']) = explode(" ", $orderBy);
$params['published'] = (isset($published)) ? $published : '1';
$params['outputSeparator'] = (isset($outputSeparator)) ? $outputSeparator : '';
$params['firstClass'] = (isset($firstClass)) ? $firstClass : 'first';
$params['lastClass'] = (isset($lastClass)) ? $lastClass : 'last';
$params['evenClass'] = (isset($evenClass)) ? $evenClass : '';
$params['oddClass'] = (isset($oddClass)) ? $oddClass : '';
$params['paginate'] = (isset($paginate) && $paginate) ? true : false;
$params['offsetKey'] = (isset($offsetKey)) ? $offsetKey : 'page';
$params['offset'] = ($params['paginate'] && ($params['display'] != 'all') && isset($_GET[$params['offsetKey']])) ? (intval($_GET[$params['offsetKey']]) - 1) * $params['display'] : $params['offset'];

if (!empty($fromJson)) {
    $tvOutput = json_decode($fromJson, true);
} else {
    $tvOutput = $multiTV->getMultiValue($params);
}
return $multiTV->displayMultiValue($tvOutput, $params);
