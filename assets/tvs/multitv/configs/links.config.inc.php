<?php
$settings['display'] = 'datatable';
$settings['fields'] = array(
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
    'image' => array(
        'caption' => 'Image',
        'type' => 'image'
    ),
    'thumb' => array(
        'type' => 'thumb',
        'thumbof' => 'image'
    ),
    'legend' => array(
        'caption' => 'Legend',
        'type' => 'richtext'
    )
);
$settings['columns'] = array(
    array(
        'caption' => 'Links',
        'fieldname' => 'title',
        'render' => '<strong>[+title+]</strong> – [+subtitle+]'
    )
);
$settings['form'] = array(
    array(
        'caption' => 'Link',
        'content' => array(
            'title' => array(),
            'subtitle' => array(),
            'link' => array()
        )
    ),
    array(
        'caption' => 'Image/Legend',
        'content' => array(
            'thumb' => array(),
            'image' => array(),
            'legend' => array()
        )
    )
);

$settings['templates'] = array(
    'outerTpl' => '

[+wrapper+][+pagination+]
',
    'rowTpl' => '
[+img_bg+]
[+row.number+]
[+iteration+]
[+title:ucase+]
[+row.class+]
<br/>'
);
$settings['configuration'] = array(
    'enablePaste' => false,
    'csvseparator' => ','
);
