<?php
/**
 * multiTV
 * 
 * @category 	snippet
 * @version 	1.3
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    description: <strong>1.3</strong> Transform template variables into a sortable multi item list.
 * @internal    snippet code: return include(MODX_BASE_PATH.'assets/tvs/multitv/multitv.snippet.php');
 */
if (MODX_BASE_PATH == '') {
	die('<h1>ERROR:</h1><p>Please use do not access this file directly.</p>');
}

// set customtv (base) path
$filepath = str_replace(MODX_BASE_PATH, '', dirname(__FILE__));

define(MTV_PATH, $filepath . '/');
define(MTV_BASE_PATH, MODX_BASE_PATH . MTV_PATH);

// include classfile
if (!class_exists('multiTV')) {
	include MTV_BASE_PATH . 'multitv.class.php';
}

// get snippet parameter
$tvName = isset($tvName) ? $tvName : '';
$docid = isset($docid) ? $docid : $modx->documentObject['id'];
$outerTpl = isset($outerTpl) ? $outerTpl : '@CODE:<select name="' . $tvName . '">[+wrapper+]</select>';
$rowTpl = isset($rowTpl) ? $rowTpl : '@CODE:<option value="[+value+]">[+key+]</option>';
$display = (isset($display) && $display >= 0) ? (int) $display : 5;
$rows = isset($rows) ? explode(',', $rows) : 'all';

// load template variable settings
$res = $modx->db->select('*', $modx->getFullTableName('site_tmplvars'), 'name="' . $tvName . '"');
$row = $modx->db->getRow($res);
if (!$row) {
	return 'Template variable ' . $tvName . ' does not exists';
}
$multiTV = new multiTV($row);
$columns = $multiTV->fieldnames;

// replace masked placeholder tags (for templates that are set directly set in snippet call by @CODE)
$maskedTags = array('((' => '[+', '))' => '+]');
$outerTpl = str_replace(array_keys($maskedTags), array_values($maskedTags), $outerTpl);
$rowTpl = str_replace(array_keys($maskedTags), array_values($maskedTags), $rowTpl);

// get template variable
$tvOutput = $modx->getTemplateVarOutput(array($tvName), $docid);
$tvOutput = $tvOutput[$tvName];
$tvOutput = json_decode($tvOutput);

// stop if there is no output
if (!count($tvOutput))
	return;

// parse the output chunks
if (!class_exists('multitvChunkie')) {
	include (MTV_BASE_PATH . '/includes/chunkie.class.inc.php');
}

// output
$columnCount = count($columns);
$wrapper = '';
$i = 1;
// rowTpl output 
foreach ($tvOutput as $value) {
	if ($rows != 'all') {
		// output only selected rows 
		if (!in_array($i, $rows)) {
			continue;
		}
	}
	$parser = new multitvChunkie($rowTpl);
	for ($j = 0; $j < $columnCount; $j++) {
		$parser->AddVar($columns[$j], $value[$j]);
	}
	$parser->AddVar('iteration', $i);
	$wrapper .= $parser->Render();
	$i++;
}

// wrap rowTpl output in outerTpl
$parser = new multitvChunkie($outerTpl);
$parser->AddVar('wrapper', $wrapper);
$output = $parser->Render();
return $output;
?>
