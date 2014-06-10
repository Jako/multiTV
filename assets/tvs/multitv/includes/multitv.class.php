<?php
/**
 * multiTV
 *
 * @category    classfile
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author        Jako (thomas.jakobi@partout.info)
 */
if (!function_exists('renderFormElement')) {
    include MODX_MANAGER_PATH . 'includes/tmplvars.inc.php';
}
if (!function_exists('ProcessTVCommand')) {
    include MODX_MANAGER_PATH . 'includes/tmplvars.commands.inc.php';
}
if (!class_exists('newChunkie')) {
    include(MTV_BASE_PATH . 'includes/newchunkie.class.php');
}
if (!class_exists('Pagination')) {
    include(MTV_BASE_PATH . 'includes/pagination.class.php');
}

class multiTV
{

    public $tvName = '';
    public $tvID = 0;
    public $tvCaption = '';
    public $tvDescription = '';
    public $tvDefault = '';
    public $tvValue = '';
    public $tvTemplates = '';
    public $display = '';
    public $fieldnames = array();
    public $fieldcolumns = array();
    public $fieldform = array();
    public $fieldtypes = array();
    public $fields = array();
    public $fieldsrte = array();
    public $modulebuttons = array();
    public $templates = array();
    public $language = array();
    public $configuration = array();
    public $sortkey = '';
    public $sortdir = '';
    public $sorttype = '';
    public $cmsinfo = array();
    public $options = array();
    private $modx;


    function __construct(&$modx, $options)
    {
        $this->modx = & $modx;

        $this->language = $this->loadLanguage($this->modx->config['manager_language']);
        $this->options = $options;
        $this->options['modulename'] = ($this->options['modulename']) ? $this->options['modulename'] : $this->language['modulename'];

        $version = $this->modx->getVersionData();
        switch ($version['branch']) {
            case 'Evolution':
                $this->cmsinfo['clipper'] = '';
                $this->cmsinfo['kcfinder'] = version_compare($version['version'], '1.0.10', '>') ? 'true' : 'false';
                $this->cmsinfo['thumbsdir'] = ($this->modx->config['thumbsDir']) ? $this->modx->config['thumbsDir'] . '/' : '';
                $this->cmsinfo['seturl'] = version_compare($version['version'], '1.0.12', '>') ? '' : 'old';
                break;
            case 'ClipperCMS':
                $this->cmsinfo['clipper'] = 'Clipper';
                $this->cmsinfo['kcfinder'] = version_compare($version['version'], '1.1', '>') ? 'true' : 'false';
                $this->cmsinfo['thumbsdir'] = 'thumbs/';
                $this->cmsinfo['seturl'] = version_compare($version['version'], '1.2.0', '>') ? '' : 'old';
                break;
        }

        switch ($options['type']) {
            case 'module':
                break;
            case 'tv':
            default:
                $this->initTV($options['tvDefinitions']);
                break;
        }
    }

    // Init TV
    function initTV($tvDefinitions)
    {
        if (isset($tvDefinitions['name'])) {
            $this->tvName = $tvDefinitions['name'];
            $this->tvID = $tvDefinitions['id'];
            $this->tvCaption = $tvDefinitions['caption'];
            $this->tvDescription = $tvDefinitions['description'];
            $this->tvDefault = $tvDefinitions['default_text'];
            $this->tvTemplates = 'templates' . $tvDefinitions['tpl_config'];
        } else {
            $this->modx->messageQuit('No multiTV definitions set');
        }
        $settings = $this->loadSettings($this->tvName, 'config');
        $this->prepareSettings($settings);
        if ($tvDefinitions['value']) {
            $this->prepareValue($tvDefinitions['value']);
        }
    }

    // Run Module
    function runModule($moduleConfigs)
    {
        switch ($this->options['action']) {
            case 'save_config':
                $newModuleConfigs = isset($_POST['moduleconfigs']) ? $_POST['moduleconfigs'] : $moduleConfigs;
                echo $this->uploadLocalPackages();
                exit();
            case 'load':
            default :
                $moduleTabs = array();
                $moduleTabheads = array();
                foreach ($moduleConfigs as $moduleConfig) {
                    $settings = $this->loadSettings($moduleConfig, 'moduleconfig');
                    $this->prepareSettings($settings);
                    $settings['name'] = $moduleConfig;
                    $chunkie = new newChunkie($this->modx, array('basepath' => $this->options['tvUrl']));
                    $chunkie->setPlaceholder('lang', $this->language, 'module');
                    $chunkie->setPlaceholder('options', $this->options, 'module');
                    $chunkie->setPlaceholder('', $settings, 'module');
                    if ($settings['buttons']) {
                        $this->generateButtonGroups($settings['name'], $settings['buttons']);
                        $buttons = array(
                            'top' => $this->displayButtonGroups($settings['name'], 'topleft') . "\n" . $this->displayButtonGroups($settings['name'], 'topright'),
                            'bottom' => $this->displayButtonGroups($settings['name'], 'bottomleft') . "\n" . $this->displayButtonGroups($settings['name'], 'bottomright')
                        );
                        $chunkie->setPlaceholder('buttons', $buttons, 'module');
                    }
                    $chunkie->setPlaceholder('content', $this->generateModuleTab($settings), 'module');

                    $chunkie->setTpl($chunkie->getTemplateChunk('@FILE templates/moduleTabhead.template.html'));
                    $chunkie->prepareTemplate('', array(), 'module');
                    $moduleTabheads[] = $chunkie->process('module', "\r\n", false);

                    $chunkie->setTpl($chunkie->getTemplateChunk('@FILE templates/moduleTab.template.html'));
                    $chunkie->prepareTemplate('', array(), 'module');
                    $moduleTabs[] = $chunkie->process('module', "\r\n", true);
                }
                $chunkie = new newChunkie($this->modx, array('basepath' => $this->options['tvUrl']));
                $chunkie->setPlaceholder('lang', $this->language, 'module');
                $chunkie->setPlaceholder('options', $this->options, 'module');
                $chunkie->setPlaceholder('moduleTabheads', implode("\n", $moduleTabheads), 'module');
                $chunkie->setPlaceholder('moduleTabs', implode("\n", $moduleTabs), 'module');

                $chunkie->setTpl($chunkie->getTemplateChunk('@FILE templates/module.template.html'));
                $chunkie->prepareTemplate('', array(), 'module');

                $output = $chunkie->process('module');
        }
        return $output;
    }

    // Return the include path of a configuration/template/whatever file
    function includeFile($name, $type = 'config', $extension = '.inc.php', $folder = '')
    {
        if ($folder == '') {
            $folder = MTV_BASE_PATH . ((substr($type, -1) != 'y') ? $type . 's/' : substr($type, 0, -1) . 'ies/');
        } else {
            if (is_dir(MTV_BASE_PATH . ((substr($type, -1) != 'y') ? $type . 's/' : substr($type, 0, -1) . 'ies/') . $folder)) {
                $folder = MTV_BASE_PATH . ((substr($type, -1) != 'y') ? $type . 's/' : substr($type, 0, -1) . 'ies/') . $folder . '/';
            } else {
                $folder = MODX_BASE_PATH . $folder . '/';
            }
        }
        $allowedConfigs = glob($folder . '*.' . $type . $extension);

        if ($allowedConfigs) {
            $configs = array();
            foreach ($allowedConfigs as $config) {
                $configs[] = preg_replace('=' . $folder . '([^.]*).' . $type . $extension . '=', '$1', $config);
            }

            if (in_array($name, $configs)) {
                $filePath = $folder . $name . '.' . $type . $extension;
            } else {
                if (file_exists($folder . 'default.' . $type . $extension)) {
                    $filePath = $folder . 'default.' . $type . $extension;
                } else {
                    $filePath = false;
                }
            }
        } else {
            $filePath = false;
        }
        return $filePath;
    }

// Load setting file (php array or json) and return settings
    function loadSettings($name, $type, $breakOnError = true)
    {
        $types = (substr($type, -1) != 'y') ? $type . 's' : substr($type, 0, -1) . 'ies';
        $settings = array();
        if ($includeFile = $this->includeFile($name, $type, '.json')) {
            $settings = json_decode(file_get_contents($includeFile), true);
        } elseif ($includeFile = $this->includeFile($name, $type, '.inc.php')) {
            include($includeFile);
        } elseif ($breakOnError) {
            $this->modx->messageQuit($name . ' multiTV ' . $type . ' file "' . MTV_BASE_PATH . $types . '/' . $name . '.' . $type . '.inc.php" not found.');
        }
        return $settings;
    }

    // Load language file and return language array
    function loadLanguage($name)
    {
        $language = array();
        include($this->includeFile('english', 'language'));
        if ($languageFile = $this->includeFile($name, 'language')) {
            include($languageFile);
        }
        if (!count($language)) {
            $this->modx->messageQuit($name . ' and english multiTV language file "' . MTV_BASE_PATH . 'languages/' . $name . '.language.inc.php" not found.');
        }
        return $language;
    }

    // Load template file and return template string
    function loadTemplate($name)
    {
        $template = '';
        if ($templateFile = $this->includeFile($name, 'template', '.html')) {
            $template = file_get_contents($templateFile);
        } else {
            $this->modx->messageQuit($name . ' multiTV template file "' . MTV_BASE_PATH . 'templates/' . $name . '.template.html" not found.');
        }
        return $template;
    }

    // Initialize customtv settings
    function prepareSettings($settings)
    {
        $this->fields = $settings['fields'];
        $this->fieldnames = array_keys($this->fields);
        $this->fieldtypes = array();
        foreach ($this->fields as $field) {
            $this->fieldtypes[] = $field['type'];
        }
        $this->fieldtitles = array();
        $this->fieldcolumns = isset($settings['columns']) ? $settings['columns'] : array();
        $this->fieldform = isset($settings['form']) ? $settings['form'] : array();
        $this->templates = $settings[$this->tvTemplates];
        $this->display = $settings['display'];
        $this->configuration['csvseparator'] = isset($settings['configuration']['csvseparator']) ? $settings['configuration']['csvseparator'] : ',';
        $this->configuration['enablePaste'] = isset($settings['configuration']['enablePaste']) ? $settings['configuration']['enablePaste'] : true;
        $this->configuration['enableClear'] = isset($settings['configuration']['enableClear']) ? $settings['configuration']['enableClear'] : true;
        $this->configuration['hideHeader'] = isset($settings['configuration']['hideHeader']) ? $settings['configuration']['hideHeader'] : false;
        $this->configuration['radioTabs'] = isset($settings['configuration']['radioTabs']) ? $settings['configuration']['radioTabs'] : false;
        $this->configuration['sorting'] = isset($settings['configuration']['sorting']) ? $settings['configuration']['sorting'] : false;
        $this->configuration['sortindex'] = isset($settings['configuration']['sortindex']) ? $settings['configuration']['sortindex'] : '';
    }

    function prepareValue($value)
    {
        switch ($this->display) {
            case 'datatable':
                $val = json_decode($value);
                if ($val) {
                    foreach ($this->fieldcolumns as $column) {
                        if (isset($column['render']) && $column['render'] != '') {
                            foreach ($val->fieldValue as &$elem) {
                                $parser = new newChunkie($this->modx);
                                foreach ($elem as $k => $v) {
                                    $parser->setPlaceholder($k, $this->maskTags($v));
                                }
                                $parser->setTpl($column['render']);
                                $parser->prepareTemplate();
                                $elem->{'mtvRender' . ucfirst($column['fieldname'])} = $parser->process();
                            }
                        }
                    }
                    $value = json_encode($val);
                }
                break;
            default:
                break;
        }
        $this->tvValue = $value;
    }

    // mask MODX tags
    function maskTags($value)
    {
        $unmasked = array('[', ']', '{', '}');
        $masked = array('&#x005B;', '&#x005D;', '&#x007B;', '&#x007D;');
        return str_replace($unmasked, $masked, $value);
    }

    function unmaskTags($value)
    {
        $unmasked = array('[', ']', '{', '}');
        $masked = array('&#x005B;', '&#x005D;', '&#x007B;', '&#x007D;');
        return str_replace($masked, $unmasked, $value);
    }

    // render a template in multiTV templates folder
    function renderTemplate($template, $placeholder)
    {
        $output = '';
        if ($templateFile = $this->includeFile($template, 'template', '.html')) {
            $output = file_get_contents($templateFile);
            foreach ($this->language as $key => $value) {
                $placeholder['tvlang.' . $key] = $value;
            }
            foreach ($placeholder as $key => $value) {
                $output = str_replace('[+' . $key . '+]', $value, $output);
            }
        }
        return $output;
    }

    // invoke modx renderFormElement and change the output (to multiTV demands)
    function renderMultiTVFormElement($fieldType, $fieldName, $fieldElements, $fieldClass, $fieldDefault)
    {
        $fieldName .= '_mtv';
        $currentScript = array();
        $currentClass = array();
        $fieldClass = explode(' ', $fieldClass);
        switch ($fieldType) {
            case 'url' :
                $fieldType = 'text';
                break;
            case 'unixtime' :
                $fieldType = 'date';
                break;
            case 'image' :
                if ($this->display == 'datatable' || $this->display == 'vertical') {
                    $fieldClass[] = 'image';
                }
                break;
            case 'richtext' :
                if ($this->display == 'datatable') {
                    $this->fieldsrte[] = "tv" . $this->tvID . $fieldName;
                    $fieldClass[] = 'tabEditor';
                } else {
                    $fieldType = 'textarea';
                }
                break;
        }
        $formElement = renderFormElement($fieldType, 0, '', $fieldElements, '', '', array());
        $formElement = preg_replace('/( tvtype=\"[^\"]+\")/', '', $formElement); // remove tvtype attribute
        $formElement = preg_replace('/(<label[^>]*><\/label>)/', '', $formElement); // remove empty labels
        $formElement = preg_replace('/( id=\"[^\"]+)/', ' id="[+tvid+]' . $fieldName, $formElement); // change id attributes
        $formElement = preg_replace('/( name=\"[^\"]+)/', ' name="[+tvid+]' . $fieldName, $formElement); // change name attributes
        preg_match('/(<script.*?script>)/s', $formElement, $currentScript); // get script
        if (isset($currentScript[1])) { // the tv script is only included for the first tv that is using them (tv with image or file type)
            $formElement = preg_replace('/(<script.*?script>)/s', '', $formElement); // remove the script tag
            if ($this->cmsinfo['kcfinder'] == 'false' || $this->cmsinfo['seturl'] == 'old') {
                $currentScript[1] = preg_replace('/function SetUrl.*script>/s', '</script>', $currentScript[1]); // remove original SetUrl function
            }
            $formElement = $formElement . $currentScript[1]; // move the script tag to the end
        }
        preg_match('/<.*class=\"([^\"]*)/s', $formElement, $currentClass); // get current classes
        $formElement = preg_replace('/class=\"[^\"]*\"/s', '', $formElement, 1); // remove all classes
        if ($fieldDefault != '') {
            $formElement = preg_replace('/(<\w+)/', '$1 alt="' . $fieldDefault . '"', $formElement, 1); // add alt to first tag (the input)
            $fieldClass[] = 'setdefault';
        }
        if (isset($currentClass[1])) {
            $fieldClass[] = str_replace('DatePicker', 'mtvDatePicker', $currentClass[1]);
        }
        $fieldClass = implode(' ', array_unique($fieldClass));
        $formElement = preg_replace('/(<\w+)/', '$1 class="' . $fieldClass . '"', $formElement, 1); // add class to first tag (the input)
        $formElement = preg_replace('/<label for=[^>]*>([^<]*)<\/label>/s', '<label class="inlinelabel">$1</label>', $formElement); // add label class
        $formElement = preg_replace('/(onclick="BrowseServer[^\"]+\")/', 'class="browseimage ' . $fieldClass . '"', $formElement, 1); // remove imagebrowser onclick script
        $formElement = preg_replace('/(onclick="BrowseFileServer[^\"]+\")/', 'class="browsefile ' . $fieldClass . '"', $formElement, 1); // remove filebrowser onclick script
        $formElement = str_replace('document.forms[\'mutate\'].elements[\'tv0\'].value=\'\';document.forms[\'mutate\'].elements[\'tv0\'].onblur(); return true;', '$j(this).prev(\'input\').val(\'\').trigger(\'change\');', $formElement); // change datepicker onclick script
        $formElement = preg_replace('/(<script.*?DatePicker.*?script>)/s', '', $formElement); // remove datepicker script
        $formElement = preg_replace('/( onmouseover=\"[^\"]+\")/', '', $formElement); // delete onmouseover attribute
        $formElement = preg_replace('/( onmouseout=\"[^\"]+\")/', '', $formElement); // delete onmouseout attribute
        $formElement = str_replace(array('&nbsp;'), ' ', $formElement); // change whitespace
        $formElement = str_replace(array('style="width:100%;"', 'style="width:100%"', ' width="100%"', '  width="100"', '<br />', 'onchange="documentDirty=true;"', " checked='checked'"), array(''), $formElement); // remove unused atrributes and tags
        return trim($formElement);
    }

    // build the output of multiTV script and css
    function generateScript()
    {
        $tvid = "tv" . $this->tvID;
        $tvvalue = ($this->tvValue != '') ? $this->tvValue : '[]';
        $tvvalue = $this->maskTags($tvvalue);
        $tvlanguage = json_encode($this->language);
        $tvpath = '../' . $this->options['tvUrl'];

        // generate tv elements
        $tvcss = '';
        $hasthumb = '';

        switch ($this->display) {
            // horizontal template
            case 'horizontal':
                $tvfields = json_encode(array('fieldnames' => $this->fieldnames, 'fieldtypes' => $this->fieldtypes, 'csvseparator' => $this->configuration['csvseparator']));
                $tvheading = array('<div id="[+tvid+]heading" class="heading">');
                $tvelement = array('<li class="element inline' . $hasthumb . '"><div>');
                foreach ($this->fieldnames as $fieldname) {
                    $tvheading[] = '<span class="inline ' . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</span>';
                    $type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
                    $elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
                    $default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
                    if ($this->fields[$fieldname]['width']) {
                        $tvcss .= '.multitv #[+tvid+]list li.element .inline.' . $fieldname . ', .multitv #[+tvid+]heading .inline.' . $fieldname . ' { width: ' . $this->fields[$fieldname]['width'] . 'px }';
                    }
                    switch ($type) {
                        case 'thumb':
                            $tvelement[] = '<div class="inline tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
                            $hasthumb = ' hasthumb';
                            break;
                        case 'date':
                            $tvelement[] = $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inline ' . $fieldname, $default);
                            $tvcss .= '.multitv #[+tvid+]list li.element .inline.' . $fieldname . ' { width: ' . strval($this->fields[$fieldname]['width'] - 26) . 'px }';
                            break;
                        default:
                            $tvelement[] = $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inline ' . $fieldname, $default);
                    }
                }
                $tvheading[] = '</div>';
                $tvelement[] = '<a href="#" class="copy" title="[+tvlang.add+]">[+tvlang.add+]</a>';
                $tvelement[] = '<a href="#" class="remove" title="[+tvlang.remove+]">[+tvlang.remove+]</a>';
                $tvelement[] = '</div><div class="clear"></div></li>';
                break;
            // vertical template
            case 'vertical':
                $tvfields = json_encode(array('fieldnames' => $this->fieldnames, 'fieldtypes' => $this->fieldtypes, 'csvseparator' => $this->configuration['csvseparator']));
                $tvheading = array();
                $tvelement = array('<li class="element' . $hasthumb . '"><div>');
                foreach ($this->fieldnames as $fieldname) {
                    $type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
                    $elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
                    $default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
                    if ($this->fields[$fieldname]['width']) {
                        $tvcss .= '.multitv #[+tvid+]list li.element .' . $fieldname . ' { width: ' . $this->fields[$fieldname]['width'] . 'px !important }' . "\r\n";
                    }
                    switch ($type) {
                        case 'thumb':
                            $tvelement[] = '<div class="tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
                            $hasthumb = ' hasthumb';
                            break;
                        default:
                            $tvelement[] = '<label for="' . $tvid . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</label>';
                            $tvelement[] = $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . '<br />';
                    }
                }
                $tvelement[] = '<a href="#" class="copy" title="[+tvlang.add+]">[+tvlang.add+]</a>';
                $tvelement[] = '<a href="#" class="remove" title="[+tvlang.remove+]">[+tvlang.remove+]</a>';
                $tvelement[] = '</div><div class="clear"></div></li>';
                break;
            // horizontal template
            case 'single':
                $tvfields = json_encode(array('fieldnames' => $this->fieldnames, 'fieldtypes' => $this->fieldtypes, 'csvseparator' => $this->configuration['csvseparator']));
                $tvheading = array();
                $tvelement = array();
                foreach ($this->fieldnames as $fieldname) {
                    $type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
                    $elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
                    $default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
                    switch ($type) {
                        case 'thumb':
                            $tvelement[] = '<div class="tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
                            $hasthumb = ' hasthumb';
                            break;
                        default:
                            $tvelement[] = '<label for="' . $tvid . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</label>';
                            $tvelement[] = $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . '<br />';
                    }
                }
                $tvelement[] = '<li class="element single' . $hasthumb . '"><div>';
                $tvelement[] = '</div><div class="clear"></div></li>';
                break;
            // datatable template
            case 'datatable':
            case 'dbtable':
                $tableClasses = array();
                $fieldcolumns = array();
                if ($this->display == 'dbtable') {
                    $fieldcolumns[] = array(
                        'mData' => 'id',
                        'sTitle' => '',
                        'bSortable' => false,
                        'bVisible' => false
                    );
                }
                if (!$this->configuration['sorting'] && $this->display == 'datatable') {
                    $fieldcolumns[] = array(
                        'mData' => 'MTV_RowId',
                        'sTitle' => '',
                        'sClass' => 'handle',
                        'bSortable' => false,
                        'sWidth' => '2px'
                    );
                }
                if ($this->configuration['radioTabs']) {
                    $fieldcolumns[] = array(
                        'mData' => 'fieldTab',
                        'sTitle' => '',
                        'bSortable' => false,
                        'bVisible' => false
                    );
                }
                if (count($this->fieldcolumns)) {
                    foreach ($this->fieldcolumns as $column) {
                        $fieldcolumns[] = array(
                            'mData' => (isset($column['render']) && $column['render'] != '') ? 'mtvRender' . ucfirst($column['fieldname']) : $column['fieldname'],
                            'sTitle' => (isset($column['caption'])) ? $column['caption'] : ((isset($this->fields[$column['fieldname']]['caption'])) ? $this->fields[$column['fieldname']]['caption'] : $column['fieldname']),
                            'sWidth' => (isset($column['width'])) ? $column['width'] : ((isset($this->fields[$column['fieldname']]['width'])) ? $this->fields[$column['fieldname']]['width'] : ''),
                            'bSortable' => ($this->configuration['sorting']) ? ((isset($column['sortable'])) ? (bool)$column['sortable'] : true) : false,
                            'bVisible' => (isset($column['visible'])) ? (bool)$column['visible'] : ((isset($this->fields[$column['fieldname']]['visible'])) ? (bool)$this->fields[$column['fieldname']]['visible'] : true)
                        );
                    }
                } else {
                    foreach ($this->fields as $key => $column) {
                        $fieldcolumns[] = array(
                            'mData' => $key,
                            'sTitle' => (isset($column['caption'])) ? $column['caption'] : $column['fieldname'],
                            'bSortable' => ($this->configuration['sorting']) ? ((isset($column['sortable'])) ? (bool)$column['sortable'] : true) : false,
                        );
                    }
                }
                $tabs = array();
                $tabPages = array();
                foreach ($this->fieldform as $key => $tab) {
                    $tvElements = array();
                    foreach ($tab['content'] as $fieldname => $tv) {
                        $type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
                        $elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
                        $default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
                        $caption = (is_array($tv) && isset($tv['caption'])) ? $tv['caption'] : $this->fields[$fieldname]['caption'];
                        switch ($type) {
                            case 'thumb':
                                $tvElements[] = '<div class="tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
                                break;
                            default:
                                $tvElements[] = '<label for="' . $tvid . $fieldname . '">' . $caption . '</label>' .
                                    $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . "\r\n";
                        }
                    }

                    $tabplaceholder = array(
                        'id' => ($this->configuration['radioTabs']) ? $tvid . 'tab_radio_' . $tab['value'] : $tvid . 'tab_' . $key,
                        'tvid' => $tvid,
                        'caption' => $tab['caption'],
                        'value' => $tab['value'],
                        'content' => implode("\n", $tvElements),
                        'radio' => ($this->configuration['radioTabs']) ? '1' : '0'
                    );
                    $formTabTemplate = (!$this->configuration['radioTabs']) ? 'editFormTab' : 'editFormTabRadio';
                    $tabs[] = $this->renderTemplate($formTabTemplate, $tabplaceholder);
                    $tabPages[] = $this->renderTemplate('editFormTabpage', $tabplaceholder);
                }
                $placeholder = array();
                $placeholder['tabs'] = implode("\r\n", $tabs);
                $placeholder['tabpages'] = implode("\r\n", $tabPages);
                $tvelement = array($this->renderTemplate('editForm', $placeholder));
                if ($this->configuration['hideHeader']) {
                    $tableClasses[] = 'hideHeader';
                }
                $tvfields = json_encode(array(
                    'fieldconfig' => $this->tvName,
                    'fieldconfigtype' => 'tv',
                    'fieldnames' => $this->fieldnames,
                    'fieldtypes' => $this->fieldtypes,
                    'fieldcolumns' => $fieldcolumns,
                    'fieldrte' => $this->fieldsrte,
                    'csvseparator' => $this->configuration['csvseparator'],
                    'tableClasses' => implode(' ', $tableClasses),
                    'radioTabs' => $this->configuration['radioTabs'],
                    'sorting' => $this->configuration['sorting'],
                    'sortindex' => $this->configuration['sortindex']
                ));
                break;
        }

        // populate tv template
        $scriptfiles = array();
        $cssfiles = array();
        $files = array();
        $placeholder = array();

        $settings = $this->loadSettings('default' . $this->cmsinfo['clipper'], 'setting');
        $files['scripts'] = $settings['scripts'];
        $files['css'] = $settings['css'];
        if ($this->configuration['enablePaste'] && $this->display != 'dbtable') {
            $settings = $this->loadSettings('paste' . $this->cmsinfo['clipper'], 'setting');
            $files['scripts'] = array_merge($files['scripts'], $settings['scripts']);
            $files['css'] = array_merge($files['css'], $settings['css']);
            $placeholder['paste'] = $this->loadTemplate('paste');
        } else {
            $placeholder['paste'] = '';
        }
        if ($this->configuration['enableClear'] && $this->display != 'datatable' && $this->display != 'dbtable') {
            $placeholder['clear'] = $this->loadTemplate('clear');
        } else {
            $placeholder['clear'] = '';
        }
        if ($this->display == 'datatable' || $this->display == 'dbtable') {
            $settings = $this->loadSettings('datatable' . $this->cmsinfo['clipper'], 'setting');
            $files['scripts'] = array_merge($files['scripts'], $settings['scripts']);
            $files['css'] = array_merge($files['css'], $settings['css']);
            $placeholder['data'] = $this->loadTemplate('datatable');
            $placeholder['script'] = $this->loadTemplate('datatableScript' . $this->cmsinfo['clipper']);
            $placeholder['edit'] = $this->loadTemplate('edit');
            $placeholder['editform'] = implode("\n", $tvelement);
        } else {
            $placeholder['data'] = $this->loadTemplate('sortablelist');
            $placeholder['script'] = $this->loadTemplate('sortablelistScript' . $this->cmsinfo['clipper']);
        }

        foreach ($files['css'] as $file) {
            $cssfiles[] = '	<link rel="stylesheet" type="text/css" href="' . $tvpath . $file . '" />';
        }
        if ($this->cmsinfo['clipper'] != 'Clipper') {
            $files['scripts'] = array_merge($files['scripts'], array('js/multitvhelper' . $this->cmsinfo['seturl'] . '.js', 'js/multitv.js'));
            foreach ($files['scripts'] as $file) {
                $scriptfiles[] = '	<script type="text/javascript" src="' . $tvpath . $file . '"></script>';
            }
        } else {
            $files['scripts'] = array_merge($files['scripts'], array(
                array('name' => 'multitvhelper', 'path' => 'js/multitvhelperclipper' . $this->cmsinfo['seturl'] . '.js'),
                array('name' => 'multitv', 'path' => 'js/multitv.js'),
            ));
            foreach ($files['scripts'] as $file) {
                $scriptfiles[] = $this->modx->getJqueryPluginTag($file['name'], $tvpath . $file['path'], false);
            }
        }

        $placeholder['cssfiles'] = implode("\r\n", $cssfiles);
        $placeholder['scriptfiles'] = implode("\r\n", $scriptfiles);
        $placeholder['tvcss'] = '<style type="text/css">' . "\r\n" . $tvcss . "\r\n" . '</style>';
        $placeholder['tvheading'] = is_array($tvheading) ? implode("\n", $tvheading) : '';
        $placeholder['tvmode'] = $this->display;
        $placeholder['tvfields'] = $tvfields;
        $placeholder['tvlanguage'] = $tvlanguage;
        $placeholder['tvheading'] = is_array($tvelement) ? implode("\n", $tvelement) : '';
        $placeholder['tvvalue'] = $tvvalue;
        $placeholder['tvid'] = $tvid;
        $placeholder['tvpath'] = $tvpath;
        $placeholder['tvkcfinder'] = $this->cmsinfo['kcfinder'];
        $placeholder['tvthumbs'] = $this->cmsinfo['thumbsdir'];
        $placeholder['tvmtvpath'] = $this->options['tvUrl'];

        $tvtemplate = $this->renderTemplate('multitv', $placeholder);

        return $tvtemplate;
    }

    function generateModuleTab($config)
    {
        $modulepath = '../' . $this->options['tvUrl'];

        $tableClasses = array();
        $fieldcolumns = array();
        $fieldcolumns[] = array(
            'mData' => 'id',
            'sTitle' => '',
            'bSortable' => false,
            'bVisible' => false
        );
        if (!$this->configuration['sorting'] && $this->configuration['sortindex'] != '') {
            $fieldcolumns[] = array(
                'mData' => 'MTV_RowId',
                'sTitle' => '',
                'sClass' => 'handle',
                'bSortable' => false,
                'sWidth' => '2px'
            );
        }
        if ($this->configuration['radioTabs']) {
            $fieldcolumns[] = array(
                'mData' => 'fieldTab',
                'sTitle' => '',
                'bSortable' => false,
                'bVisible' => false
            );
        }
        if (count($this->fieldcolumns)) {
            foreach ($this->fieldcolumns as $column) {
                $fieldcolumns[] = array(
                    'mData' => (isset($column['render']) && $column['render'] != '') ? 'mtvRender' . ucfirst($column['fieldname']) : $column['fieldname'],
                    'sTitle' => (isset($column['caption'])) ? $column['caption'] : ((isset($this->fields[$column['fieldname']]['caption'])) ? $this->fields[$column['fieldname']]['caption'] : $column['fieldname']),
                    'sWidth' => (isset($column['width'])) ? $column['width'] : ((isset($this->fields[$column['fieldname']]['width'])) ? $this->fields[$column['fieldname']]['width'] : ''),
                    'bSortable' => ($this->configuration['sorting']) ? ((isset($column['sortable'])) ? (bool)$column['sortable'] : true) : false,
                    'bVisible' => (isset($column['visible'])) ? (bool)$column['visible'] : ((isset($this->fields[$column['fieldname']]['visible'])) ? (bool)$this->fields[$column['fieldname']]['visible'] : true),
                );
            }
        } else {
            foreach ($this->fields as $key => $column) {
                $fieldcolumns[] = array(
                    'mData' => $key,
                    'sTitle' => (isset($column['caption'])) ? $column['caption'] : $column['fieldname'],
                    'bSortable' => ($this->configuration['sorting']) ? ((isset($column['sortable'])) ? (bool)$column['sortable'] : true) : false,
                );
            }
        }
        $tabs = array();
        $tabPages = array();
        foreach ($config['form'] as $key => $tab) {
            $tvElements = array();
            foreach ($tab['content'] as $fieldname => $tv) {
                $type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
                $elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
                $default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
                $caption = (is_array($tv) && isset($tv['caption'])) ? $tv['caption'] : $this->fields[$fieldname]['caption'];
                switch ($type) {
                    case 'thumb':
                        $tvElements[] = '<div class="tvimage" id="' . $config['table'] . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
                        break;
                    default:
                        $tvElements[] = '<label for="' . $config['table'] . $fieldname . '_mtv">' . $caption . '</label>' .
                            $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . "\r\n";
                }
            }

            $tabplaceholder = array(
                'id' => ($this->configuration['radioTabs']) ? $config['table'] . 'tab_radio_' . $tab['value'] : $config['table'] . 'tab_' . $key,
                'tvid' => $config['table'],
                'caption' => $tab['caption'],
                'value' => $tab['value'],
                'content' => implode("\r\n", $tvElements),
                'radio' => ($this->configuration['radioTabs']) ? '1' : '0'
            );
            $formTabTemplate = (!$this->configuration['radioTabs']) ? 'editFormTab' : 'editFormTabRadio';
            $tabs[] = $this->renderTemplate($formTabTemplate, $tabplaceholder);
            $tabPages[] = $this->renderTemplate('editFormTabpage', $tabplaceholder);
        }

        $placeholder = array();
        $placeholder['tabs'] = implode("\r\n", $tabs);
        $placeholder['tabpages'] = implode("\r\n", $tabPages);
        $tvelement = $this->renderTemplate('editForm', $placeholder);
        $tvfields = json_encode(array(
            'fieldconfig' => $config['name'],
            'fieldconfigtype' => 'module',
            'fieldnames' => $this->fieldnames,
            'fieldtypes' => $this->fieldtypes,
            'fieldcolumns' => $fieldcolumns,
            'fieldrte' => $this->fieldsrte,
            'tableClasses' => implode(' ', $tableClasses),
            'radioTabs' => $this->configuration['radioTabs'],
            'sorting' => $this->configuration['sorting'],
            'sortindex' => $this->configuration['sortindex']
        ));

        // populate tv template
        $scriptfiles = array();
        $cssfiles = array();
        $files = array();
        $placeholder = array();

        $settings = $this->loadSettings('default' . $this->cmsinfo['clipper'], 'setting');
        $files['scripts'] = $settings['scripts'];
        $files['css'] = $settings['css'];
        $placeholder['paste'] = '';
        $placeholder['clear'] = '';
        $settings = $this->loadSettings('datatable' . $this->cmsinfo['clipper'], 'setting');
        $files['scripts'] = array_merge($files['scripts'], $settings['scripts']);
        $files['css'] = array_merge($files['css'], $settings['css']);
        $placeholder['data'] = $this->loadTemplate('datatable');
        $placeholder['script'] = $this->loadTemplate('datatableScript' . $this->cmsinfo['clipper']);
        $placeholder['edit'] = $this->loadTemplate('edit');
        $placeholder['editform'] = $tvelement;

        foreach ($files['css'] as $file) {
            $cssfiles[] = '	<link rel="stylesheet" type="text/css" href="' . $modulepath . $file . '" />';
        }
        $files['scripts'] = array_merge($files['scripts'], array('js/multitvhelper' . $this->cmsinfo['seturl'] . '.js', 'js/multitv.js'));
        foreach ($files['scripts'] as $file) {
            $scriptfiles[] = '	<script type="text/javascript" src="' . $modulepath . $file . '"></script>';
        }

        $placeholder['cssfiles'] = implode("\r\n", $cssfiles);
        $placeholder['scriptfiles'] = implode("\r\n", $scriptfiles);
        $placeholder['tvmode'] = 'dbtable';
        $placeholder['tvfields'] = $tvfields;
        $placeholder['tvlanguage'] = json_encode($this->language);
        $placeholder['tvelement'] = $tvelement;
        $placeholder['tvid'] = $config['table'];
        $placeholder['tvkcfinder'] = $this->cmsinfo['kcfinder'];
        $placeholder['tvthumbs'] = $this->cmsinfo['thumbsdir'];
        $placeholder['tvmtvpath'] = $this->options['tvUrl'];

        $tvtemplate = $this->renderTemplate('multitv', $placeholder);

        return $tvtemplate;
    }

    function generateButtons($buttons, $group, $config)
    {
        $output = array();
        foreach ($buttons as $name => $options) {
            $icon = ($options['icon']) ? '<img alt="' . $options['caption'] . '" src="../' . $this->options['tvUrl'] . 'css/images/' . $options['icon'] . '">' : '';
            $output[] = '<li><a id="' . ucfirst($group) . ucfirst($name) . '" href="#">' . $icon . ' ' . $options['caption'] . '</a></li>' .
                '<script type="text/javascript">var ' . $name . 'Config = "' . $config . '"</script>' .
                '<script type="text/javascript">var ' . $name . 'Group = "' . $group . '"</script>' .
                '<script type="text/javascript">var ' . $name . 'Button = "' . $name . '"</script>' .
                '<script type="text/javascript">var mtvpath = "' . $this->options['tvUrl'] . '"</script>' .
                '<script type="text/javascript" src="../' . $this->options['tvUrl'] . 'buttons/' . $group . '/' . $name . '.button.js"></script>';
        }
        return implode("\n", $output);
    }

    function generateButtonGroups($tab, $buttons)
    {
        foreach ($buttons as $name => $options) {
            $this->modulebuttons[$tab][$options['position']][$name] = '<ul class="actionButtons">' . $this->generateButtons($options['buttons'], $name, $tab) . '</ul>';
        }
    }

    function displayButtonGroups($tab, $position)
    {
        return (isset($this->modulebuttons[$tab][$position])) ? '<div class="modulebuttons ' . $position . '">' . implode("\n", $this->modulebuttons[$tab][$position]) . '</div>' : '';
    }

    function GetTransaliasSettings()
    {
        global $modx;

        // Cleaning uploaded filename?
        if (!isset($_SESSION['TransAliasSettings'])) {
            $setting = $this->modx->db->select('*', $this->modx->getFullTableName('system_settings'), 'setting_name="clean_uploaded_filename" AND setting_value=1');
            if ($this->modx->db->getRecordCount($setting)) {
                // Transalias plugin active?
                $res = $this->modx->db->select('*', $this->modx->getFullTableName('site_plugins'), 'name="TransAlias" AND disabled=0');
                if ($this->modx->db->getRecordCount($res)) {
                    $row = $this->modx->db->getRow($res);
                    $properties = $this->modx->parseProperties($row['properties']);
                } else {
                    $properties = null;
                }
            } else {
                $properties = null;
            }
            $_SESSION['TransAliasSettings'] = $properties;
        } else {
            $properties = $_SESSION['TransAliasSettings'];
        }
        return $properties;
    }

    function CleanAlias($string)
    {
        if ($transaliasSettings = $this->GetTransaliasSettings()) {
            if (!class_exists('TransAlias')) {
                include MODX_BASE_PATH . 'assets/plugins/transalias/transalias.class.php';
            }
            $trans = new TransAlias();
            $trans->loadTable($transaliasSettings['table_name']);
            $string = $trans->stripAlias($string, $transaliasSettings['char_restrict'], $transaliasSettings['word_separator']);
        } else {
            $string = (preg_replace('/[^0-9a-z\/\._-]+/', '', strtolower($string)));
        }
        return $string;
    }

    function getMultiValue($params)
    {
        // get template variable always if logged into manager
        $published = isset($_SESSION['mgrValidated']) ? '2' : $params['published'];
        // get template variable
        switch (strtolower($published)) {
            case '0':
            case 'false':
                $tvOutput = $this->modx->getTemplateVarOutput(array($this->tvName), $params['docid'], '0');
                break;
            case '1':
            case '2':
            case 'true':
                $tvOutput = $this->modx->getTemplateVarOutput(array($this->tvName), $params['docid'], '1');
                if ($tvOutput == false && $published == '2') {
                    $tvOutput = $this->modx->getTemplateVarOutput(array($this->tvName), $params['docid'], '0');
                }
                break;
        }
        $tvOutput = $tvOutput[$this->tvName];
        $tvOutput = json_decode($tvOutput, true);
        if (isset($tvOutput['fieldValue'])) {
            $tvOutput = $tvOutput['fieldValue'];
        }
        return $tvOutput;
    }

    function displayMultiValue($tvOutput, $params)
    {
        // replace masked placeholder tags (for templates that are set directly set in snippet call by @CODE)
        $maskedTags = array('((' => '[+', '))' => '+]');
        $params['outerTpl'] = str_replace(array_keys($maskedTags), array_values($maskedTags), $params['outerTpl']);
        $params['rowTpl'] = str_replace(array_keys($maskedTags), array_values($maskedTags), $params['rowTpl']);

        $countOutput = count($tvOutput);
        $firstEmpty = true;
        if ($countOutput) {
            // check for first item empty
            foreach ($tvOutput[0] as $value) {
                if ($value != '') {
                    $firstEmpty = false;
                }
            }
        }

        // stop if there is no output
        if (!$countOutput || $firstEmpty) {
            $noResults = '';
            if ($params['noResults'] != '') {
                $parser = new newChunkie($this->modx);
                $noResults = $parser->getTemplateChunk($params['noResults']);
            }
            if ($params['emptyOutput']) {
                // output nothing
                return $noResults;
            } else {
                // output empty outer template
                $parser = new newChunkie($this->modx);
                $parser->setPlaceholder('wrapper', $noResults);
                $parser->setTpl($parser->getTemplateChunk($params['outerTpl']));
                $parser->prepareTemplate();
                return $parser->process();
            }
        }

        // random or sort output
        if ($params['randomize']) {
            shuffle($tvOutput);
        } elseif ($params['reverse']) {
            $tvOutput = array_reverse($tvOutput);
        } elseif (!empty($params['sortBy'])) {
            $this->sort($tvOutput, trim($params['sortBy']), strtolower(trim($params['sortDir'])));
        }

        // check for display all regarding selected rows count and offset
        $countOutput = ($params['rows'] === 'all') ? $countOutput : count($params['rows']);
        $display = $limit = ($params['display'] !== 'all') ? intval($params['display']) : $countOutput;
        $display = (($display + $params['offset']) < $countOutput) ? $display : $countOutput - $params['offset'];
        $offset = $params['offset'];

        // output
        $wrapper = array();
        $i = $iteration = 1;
        $classes = array($params['firstClass']);
        // rowTpl output
        foreach ($tvOutput as $value) {
            if ($display == 0) {
                break;
            }
            if ($params['rows'] !== 'all' && !in_array($i, $params['rows'])) {
                // output only selected rows
                $i++;
                continue;
            }
            if ($offset) {
                // don't show the offset rows
                $offset--;
                $i++;
                continue;
            }
            if (!$params['toJson']) {
                if ($display == 1) {
                    $classes[] = $params['lastClass'];
                }
                if ($iteration % 2) {
                    $classes[] = $params['oddClass'];
                } else {
                    $classes[] = $params['evenClass'];
                }
                $parser = new newChunkie($this->modx);
                foreach ($value as $key => $fieldvalue) {
                    $fieldname = (is_int($key)) ? $this->fieldnames[$key] : $key;
                    $parser->setPlaceholder($fieldname, $fieldvalue);
                }
                $parser->setPlaceholder('iteration', $iteration);
                $parser->setPlaceholder('row', array('number' => $i, 'class' => implode(' ', $classes), 'total' => $countOutput));
                $parser->setPlaceholder('docid', $params['docid']);
                $parser->setTpl($parser->getTemplateChunk($params['rowTpl']));
                $parser->prepareTemplate();
                $placeholder = $parser->process();
                if ($params['toPlaceholder']) {
                    $this->modx->setPlaceholder($params['toPlaceholder'] . '.' . $i, $placeholder);
                }
                $wrapper[] = $placeholder;
                $classes = array();
            } else {
                $wrapper[] = $value;
            }
            $i++;
            $iteration++;
            $display--;
        }
        if ($params['emptyOutput'] && !count($wrapper)) {
            // output nothing
            $output = '';
        } else {
            if (!$params['toJson']) {
                // wrap rowTpl output in outerTpl
                $parser = new newChunkie($this->modx);
                $parser->setPlaceholder('wrapper', implode($params['outputSeparator'], $wrapper));
                $parser->setPlaceholder('rows', array('offset' => $params['offset'], 'total' => $countOutput));
                $parser->setPlaceholder('docid', $params['docid']);
                if ($params['paginate']) {
                    $pagination = new Pagination(array(
                        'per_page' => $limit,
                        'num_links' => 2,
                        'cur_page' => ($params['offset'] / $limit) + 1,
                        'total_rows' => $countOutput,
                        'page_query_string' => $params['offsetKey'],
                        'use_page_numbers' => true,
                        'first_link' => $this->language['paginate.first'],
                        'prev_link' => $this->language['paginate.prev'],
                        'next_link' => $this->language['paginate.next'],
                        'last_link' => $this->language['paginate.last']
                    ));
                    $parser->setPlaceholder('pagination', $pagination->create_links());
                }
                $parser->setTpl($parser->getTemplateChunk($params['outerTpl']));
                $parser->prepareTemplate();
                $output = $parser->process();
            } else {
                $output = json_encode($wrapper);
            }
        }
        if ($params['toPlaceholder']) {
            $this->modx->setPlaceholder($params['toPlaceholder'], $output);
            $output = '';
        }
        return $output;
    }

// sort a multidimensional array
    function sort(&$array, $sortkey, $sortdir = 'asc')
    {
        $sortkey = explode(':', $sortkey);
        if (array_search($sortkey[0], $this->fieldnames) === false) {
            return;
        }
        $this->sorttype = ($sortkey[1]) ? $sortkey[1] : 'text';
        $this->sortkey = $sortkey[0];
        $this->sortdir = ($sortdir === 'desc') ? 'desc' : 'asc';
        usort($array, array($this, 'compareSort'));
    }

// compare sort values
    private
    function compareSort($a, $b)
    {
        switch ($this->sorttype) {
            case 'date' :
                $val_a = strtotime($a[$this->sortkey]);
                $val_b = strtotime($b[$this->sortkey]);
                break;
            case 'text':
            default:
                $val_a = $a[$this->sortkey];
                $val_b = $b[$this->sortkey];
                break;
        }
        if ($val_a === $val_b) {
            return 0;
        } else if ($val_a < $val_b) {
            return ($this->sortdir === 'asc') ? -1 : 1;
        } else {
            return ($this->sortdir === 'asc') ? 1 : -1;
        }
    }

}
