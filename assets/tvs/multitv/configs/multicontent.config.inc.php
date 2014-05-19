<?php
$settings['display'] = 'datatable';
$settings['fields'] = array(
    'title1_1' => array(
        'caption' => 'Title',
        'type' => 'text'
    ),
    'content1_1' => array(
        'caption' => 'Content',
        'type' => 'richtext'
    ),
    'title2_1' => array(
        'caption' => 'Title (First Column)',
        'type' => 'text'
    ),
    'content2_1' => array(
        'caption' => 'Content (First Column)',
        'type' => 'richtext'
    ),
    'title2_2' => array(
        'caption' => 'Title (Second Column)',
        'type' => 'text'
    ),
    'content2_2' => array(
        'caption' => 'Content (Second Column)',
        'type' => 'richtext'
    )
);
$settings['columns'] = array(
    array(
        'caption' => 'Content',
        'fieldname' => 'title1_1',
        'render' => '[+fieldTab:select=`onecol=<table><tr><td>[+title1_1+][+content1_1:notags:limit+]</td></tr></table>&twocol=<table><tr><td>[+title2_1+][+content2_1:notags:limit+]</td><td>[+title2_2+][+content2_2:notags:limit+]</td></tr></table>`+]'
    )
);
$settings['form'] = array(
    array(
        'caption' => 'One Column',
        'value' => 'onecol',
        'content' => array(
            'title1_1' => array(),
            'content1_1' => array()
        )
    ),
    array(
        'caption' => 'Two Columns',
        'value' => 'twocol',
        'content' => array(
            'title2_1' => array(),
            'content2_1' => array(),
            'title2_2' => array(),
            'content2_2' => array()
        )
    )
);
$settings['configuration'] = array(
    'enablePaste' => false,
    'csvseparator' => ',',
    'radioTabs' => true,
    'hideHeader' => true
);
$settings['templates'] = array(
    'outerTpl' => '<table>[+wrapper+]</table>',
    'rowTpl' => '[+fieldTab:select=`onecol=<tr><td><h1>[+title1_1+]</h1>[+content1_1+]</td></tr>&twocol=<tr><td><h1>[+title2_1+]</h1>[+content2_1+]</td><td><h1>[+title2_2+]</h1>[+content2_2+]</td></tr>`+]'
);
