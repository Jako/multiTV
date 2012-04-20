<?php
$settings['display'] = 'vertical';
$settings['fields'] = array(
	'text' => array(
		'caption' => 'Text',
		'type' => 'text'
	),
	'image' => array(
		'caption' => 'Bild',
		'type' => 'image'
	),
	'thumb' => array(
		'caption' => 'Thumbnail',
		'type' => 'thumb',
		'thumbof' => 'image'
	),
	'textarea' => array(
		'caption' => 'Textarea',
		'type' => 'textarea'
	),
	'date' => array(
		'caption' => 'Date',
		'type' => 'date'
	),
	'dropdown' => array(
		'caption' => 'Dropdown',
		'type' => 'dropdown',
		'elements' => '@SELECT `pagetitle`, `id` FROM `modx_site_content` WHERE parent = 0 ORDER BY `menuindex` ASC'
	),
	'listbox' => array(
		'caption' => 'Listbox',
		'type' => 'listbox',
		'elements' => '1||2||3||4||5'
	),
	'listbox-multiple' => array(
		'caption' => 'Listbox (multiple)',
		'type' => 'listbox-multiple',
		'elements' => 'Orange||Apple||Strawberry'
	),
	'checkbox' => array(
		'caption' => 'Checkbox',
		'type' => 'checkbox',
		'elements' => 'Yes==1||No==0'
	),
	'option' => array(
		'caption' => 'Option',
		'type' => 'option',
		'elements' => 'Yes==1||No==0'
	)
);
?>
