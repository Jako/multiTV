<?php
$settings['css'] = array(
	'css/colorbox.css',
	'css/dataTables.css',
	'css/easytabs.css',
	'css/contextMenu.css'
);
$settings['scripts'] = array(
	array('name' => 'jquery-colorbox', 'path' => 'js/jquery-colorbox-1.4.33.min.js'),
	array('name' => 'jquery-easytabs', 'path' => 'js/jquery-easytabs-3.2.0.min.js'),
	array('name' => 'jquery-datatables.rowReordering', 'path' => 'js/jquery-dataTables.rowReordering-1.0.0.js'),
	array('name' => 'jquery-contextMenu', 'path' => 'js/jquery-contextMenu-1.7.js')
);
if (CMS_RELEASE_VERSION != '1.2') {
	$settings['scripts'][] = array('name' => 'jquery-datatables', 'path' => 'js/jquery-dataTables-1.9.4.min.js');
}
?>
