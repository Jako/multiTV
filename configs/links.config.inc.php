<?php
$settings['display'] = 'datatable';
$settings['fields'] = array(
	'img_bg' => array(
		'caption' => 'Background Image',
		'type' => 'image'
	),
	'thumb1' => array(
		'caption' => 'Thumbnail',
		'type' => 'thumb',
		'thumbof' => 'img_bg'
	),
	'title' => array(
		'caption' => 'Title',
		'type' => 'text'
	),
	'subtitle' => array(
		'caption' => 'Subtitle',
		'type' => 'text'
	),
	'link' => array(
		'caption' => 'Link',
		'type' => 'text'
	),
	'img' => array(
		'caption' => 'Image',
		'type' => 'image'
	),
	'legend' => array(
		'caption' => 'Legend',
		'type' => 'richtext'
	)
);
$settings['columns'] = array(
	array(
		'caption' => 'Title',
		'fieldname' => 'title',
		'width' => '100px',
		'renderer' => 'text'
	),
	array(
		'caption' => 'Subtitle',
		'fieldname' => 'subtitle',
		'width' => '250px',
		'renderer' => 'text'
	),
	array(
		'caption' => 'Link',
		'fieldname' => 'link',
		'width' => '150px',
		'visible' => FALSE,
		'renderer' => 'text'
	)
);
$settings['form'] = array(
	array(
		'caption' => 'Link',
		'content' => array(
			'title' => array(
				'caption' => 'Alternative caption for title'
			),
			'subtitle' => array(
			),
			'link' => array(
			)
		)
	),
	array(
		'caption' => 'Image/Legend',
		'content' => array(
			'img' => array(
			),
			'legend' => array(
			)
		)
	)
);

$settings['templates'] = array(
	'outerTpl' => '

[+wrapper+]
',
	'rowTpl' => '
[+img_bg+]
[+row.number+]
[+iteration+]
[+title+]
[+row.class+]
<br/>'
);
$settings['configuration'] = array(
	'enablePaste' => FALSE,
	'csvseparator' => ','
);
$settings['templatesTest'] = array(
	'outerTpl' => '<ul>[+wrapper+]</ul>',
	'rowTpl' => '<li>[+text+], [+image+], [+thumb+], [+textarea+], [+date+], [+dropdown+], [+listbox+], [+listbox-multiple+], [+checkbox+], [+option+]</li>'
		)
?>