<?php
$settings['css'] = array(
	'css/jquery-ui-1.10.4.custom.css',
	'css/chosen.css',
	'css/multitv.css'
);

// Check for ManagerManager
$res = $this->modx->db->select('*', $this->modx->getFullTableName('site_plugins'), 'name="ManagerManager" AND disabled=0 ');
$mmActive = $this->modx->db->getRow($res);
if (!$mmActive && !$GLOBALS['mtvjquery']) {
	$settings['scripts'] = array('js/jquery-1.8.3.min.js');
	$GLOBALS['mtvjquery'] = TRUE;
} else {
	$settings['scripts'] = array();
}
$settings['scripts'] = array_merge($settings['scripts'], array(
	'js/jquery-json-2.4.min.js',
	'js/jquery-ui-1.10.4.custom.min.js',
	'js/jquery-ui-timepicker-addon.js',
	'js/jquery-field-0.9.6.min.js',
	'js/jquery-chosen-1.1.0.js',
	'js/jquery-chosensortable-1.0.0.js'
		)
);
?>
