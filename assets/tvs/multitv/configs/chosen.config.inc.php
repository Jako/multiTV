<?php
$settings['display'] = 'vertical';
$settings['fields'] = array(
	'title' => array(
		'caption' => 'Title',
		'type' => 'text'
	),
	'desc' => array(
		'caption' => 'Description',
		'type' => 'text'
	),
	'resource' => array(
		'caption' => 'Resource',
		'type' => 'listbox-chosen',
		'elements' => '@SELECT pagetitle, id FROM [+PREFIX+]site_content WHERE parent = 0 ORDER BY menuindex ASC'
	),
);

$settings['configuration'] = array(
	'enablePaste' => FALSE,
	'enableClear' => FALSE,
	'csvseparator' => ','
);
?>
