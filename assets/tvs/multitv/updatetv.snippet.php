<?php
/**
 * multiTV
 * 
 * @category 	snippet
 * @version 	1.8
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    description: <strong>1.8</strong> Custom Template Variabe containing a sortable multi item list or a datatable.
 * @internal    snippet code: return include(MODX_BASE_PATH.'assets/tvs/multitv/updatetv.snippet.php');
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
$tvNames = isset($tvNames) ? explode(',', $tvNames) : array();

$output = array();
foreach ($tvNames as $tvName) {
	$tvName = trim($tvName);
	$res = $modx->db->select('*', $modx->getFullTableName('site_tmplvars'), 'name="' . $tvName . '"');
	$tvSettings = $modx->db->getRow($res);
	if (!$tvSettings) {
		$output[] = '<b>Error:</b> Template variable ' . $tvName . ' does not exists';
	} else {
		// pre-init template configuration
		$tvSettings['tpl_config'] = (isset($tplConfig)) ? $tplConfig : '';

		// init multiTV class
		$multiTV = new multiTV($modx, $tvSettings);
		$columns = $multiTV->fieldnames;

		$res = $modx->db->select('*', $modx->getFullTableName('site_tmplvar_contentvalues'), 'tmplvarid="' . $multiTV->tvID . '"');
		$output[] = 'Updating template variable ' . $tvName;
		while ($row = $modx->db->getRow($res)) {
			if ($row['value'] == '[]') {
				$output[] = 'Skipped ' . $row['contentid'] . ' (multiTV is empty)';
				continue;
			}
			$tvValues = json_decode($row['value']);
			if (!is_object($tvValues)) {
				$tvValues = (object) array('fieldValue' => $tvValues);
			}
			foreach ($tvValues->fieldValue as $tvValueKey => $tvValue) {
				$newValue = array();
				foreach ($tvValue as $key => $value) {
					$fieldname = (is_int($key)) ? $columns[$key] : $key;
					$newValue[$fieldname] = $value;
				}
				$tvValues->fieldValue[$tvValueKey] = $newValue;
			}
			$modx->db->update(array('value' => json_encode($tvValues)), $modx->getFullTableName('site_tmplvar_contentvalues'), 'id="' . $row['id'] . '"');
			$output[] = 'Updated ' . $row['contentid'];
		}
	}
}
return implode('<br/>', $output);
?>
