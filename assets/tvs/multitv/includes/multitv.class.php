<?php
/**
 * multiTV
 *
 * @category 	classfile
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 */
if (!function_exists('renderFormElement')) {
	include MODX_MANAGER_PATH . 'includes/tmplvars.inc.php';
}
if (!class_exists('newChunkie')) {
	include (MTV_BASE_PATH . 'includes/newchunkie.class.php');
}
if (!class_exists('Pagination')) {
	include (MTV_BASE_PATH . 'includes/pagination.class.php');
}

class multiTV {

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
	public $templates = array();
	public $language = array();
	public $configuration = array();
	public $sortkey = '';
	public $sortdir = '';
	public $sorttype = '';
	public $cmsinfo = array();
	private $modx;

	// Init
	function multiTV(&$modx, $tvDefinitions) {
		$this->modx = &$modx;
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
		$settings = array();
		include ($this->includeFile($this->tvName));
		$this->prepareSettings($settings);
		if ($tvDefinitions['value']) {
			$this->prepareValue($tvDefinitions['value']);
		}
		$language = array();
		include ($this->includeFile('english', 'language'));
		include ($this->includeFile($this->modx->config['manager_language'], 'language'));
		$this->language = $language;

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
	}

	// Return the include path of a configuration/template/whatever file
	function includeFile($name, $type = 'config', $extension = '.inc.php') {

		$folder = (substr($type, -1) != 'y') ? $type . 's/' : substr($folder, 0, -1) . 'ies/';
		$allowedConfigs = glob(MTV_BASE_PATH . $folder . '*.' . $type . $extension);
		$configs = array();
		foreach ($allowedConfigs as $config) {
			$configs[] = preg_replace('=.*/' . $folder . '([^.]*).' . $type . $extension . '=', '$1', $config);
		}

		if (in_array($name, $configs)) {
			return MTV_BASE_PATH . $folder . $name . '.' . $type . $extension;
		} else {
			if (file_exists(MTV_BASE_PATH . $folder . 'default.' . $type . $extension)) {
				return MTV_BASE_PATH . $folder . 'default.' . $type . $extension;
			} else {
				return 'Allowed ' . $name . ' and default multiTV ' . $type . ' file "' . MTV_BASE_PATH . $folder . 'default.' . $type . $extension . '" not found. Did you upload all files?';
			}
		}
	}

	// Initialize customtv settings
	function prepareSettings($settings) {
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
		$this->configuration['enablePaste'] = isset($settings['configuration']['enablePaste']) ? $settings['configuration']['enablePaste'] : TRUE;
		$this->configuration['enableClear'] = isset($settings['configuration']['enableClear']) ? $settings['configuration']['enableClear'] : TRUE;
		$this->configuration['hideHeader'] = isset($settings['configuration']['hideHeader']) ? $settings['configuration']['hideHeader'] : FALSE;
		$this->configuration['radioTabs'] = isset($settings['configuration']['radioTabs']) ? $settings['configuration']['radioTabs'] : FALSE;
		$this->configuration['sorting'] = isset($settings['configuration']['sorting']) ? $settings['configuration']['sorting'] : FALSE;
	}

	function prepareValue($value) {
		switch ($this->display) {
			case 'datatable': {
					$val = json_decode($value);
					if ($val) {
						foreach ($this->fieldcolumns as $column) {
							if (isset($column['render']) && $column['render'] != '') {
								foreach ($val->fieldValue as &$elem) {
									$parser = new newChunkie($this->modx);
									foreach ($elem as $k => $v) {
										$parser->setPlaceholder($k, $this->maskTags($v));
									}
									$parser->setTpl($parser->getTemplateChunk('@CODE ' . $column['render']));
									$parser->prepareTemplate();
									$elem->{'mtvRender' . ucfirst($column['fieldname'])} = $parser->process();
								}
							}
						}
						$value = json_encode($val);
					}
					break;
				}
			default:
				break;
		}
		$this->tvValue = $value;
	}

	// mask MODX tags
	function maskTags($value) {
		$unmasked = array('[', ']', '{', '}');
		$masked = array('&#x005B;', '&#x005D;', '&#x007B;', '&#x007D;');
		return str_replace($unmasked, $masked, $value);
	}

	function unmaskTags($value) {
		$unmasked = array('[', ']', '{', '}');
		$masked = array('&#x005B;', '&#x005D;', '&#x007B;', '&#x007D;');
		return str_replace($masked, $unmasked, $value);
	}

	// render a template in multiTV templates folder
	function renderTemplate($template, $placeholder) {
		$output = file_get_contents($this->includeFile($template, 'template', '.html'));
		foreach ($this->language as $key => $value) {
			$placeholder['tvlang.' . $key] = $value;
		}
		foreach ($placeholder as $key => $value) {
			$output = str_replace('[+' . $key . '+]', $value, $output);
		}
		return $output;
	}

	// invoke modx renderFormElement and change the output (to multiTV demands)
	function renderMultiTVFormElement($fieldType, $fieldName, $fieldElements, $fieldClass, $fieldDefault) {
		$fieldName .= '_mtv';
		$currentScript = array();
		$currentClass = array();
		$fieldClass = explode(' ', $fieldClass);
		switch ($fieldType) {
			case 'url' : {
					$fieldType = 'text';
					break;
				}
			case 'image' : {
					if ($this->display == 'datatable' || $this->display == 'vertical') {
						$fieldClass[] = 'image';
					}
					break;
				}
			case 'richtext' : {
					if ($this->display == 'datatable') {
						$this->fieldsrte[] = "tv" . $this->tvID . $fieldName;
						$fieldClass[] = 'tabEditor';
					} else {
						$fieldType = 'textarea';
					}
					break;
				}
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
		$formElement = preg_replace('/( onmouseover=\"[^\"]+\")/', '', $formElement); // delete onmouseover attribute
		$formElement = preg_replace('/( onmouseout=\"[^\"]+\")/', '', $formElement); // delete onmouseout attribute
		$formElement = str_replace(array('&nbsp;'), ' ', $formElement); // change whitespace
		$formElement = str_replace(array('style="width:100%;"', 'style="width:100%"', ' width="100%"', '  width="100"', '<br />', 'onchange="documentDirty=true;"', " checked='checked'"), array(''), $formElement); // remove unused atrributes and tags
		return trim($formElement);
	}

	// build the output of multiTV script and css
	function generateScript() {
		$tvid = "tv" . $this->tvID;
		$tvvalue = ($this->tvValue != '') ? $this->tvValue : '[]';
		$tvvalue = $this->maskTags($tvvalue);
		$tvlanguage = json_encode($this->language);
		$tvpath = '../' . MTV_PATH;

		// generate tv elements
		$tvcss = '';
		$hasthumb = '';

		switch ($this->display) {
			// horizontal template
			case 'horizontal': {
					$tvfields = json_encode(array('fieldnames' => $this->fieldnames, 'fieldtypes' => $this->fieldtypes, 'csvseparator' => $this->configuration['csvseparator']));
					$tvheading = '<div id="[+tvid+]heading" class="heading">' . "\r\n";
					foreach ($this->fieldnames as $fieldname) {
						$tvheading .= '<span class="inline ' . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</span>' . "\r\n";
						$type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
						$elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
						$default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
						if ($this->fields[$fieldname]['width']) {
							$tvcss .= '.multitv #[+tvid+]list li.element .inline.' . $fieldname . ', .multitv #[+tvid+]heading .inline.' . $fieldname . ' { width: ' . $this->fields[$fieldname]['width'] . 'px }' . "\r\n";
						}
						switch ($type) {
							case 'thumb': {
									$tvelement .= '<div class="inline tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
									$hasthumb = ' hasthumb';
									break;
								}
							case 'date': {
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inline ' . $fieldname, $default) . "\r\n";
									$tvcss .= '.multitv #[+tvid+]list li.element .inline.' . $fieldname . ' { width: ' . strval($this->fields[$fieldname]['width'] - 48) . 'px }' . "\r\n";
									break;
								}
							default: {
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inline ' . $fieldname, $default) . "\r\n";
								}
						}
					}
					$tvheading .= '</div>' . "\r\n";
					// wrap tvelements
					$tvelement = '<li class="element inline' . $hasthumb . '"><div>' . $tvelement;
					$tvelement .= '<a href="#" class="copy" title="[+tvlang.add+]">[+tvlang.add+]</a>' . "\r\n";
					$tvelement .= '<a href="#" class="remove" title="[+tvlang.remove+]">[+tvlang.remove+]</a>' . "\r\n";
					$tvelement .= '</div><div class="clear"></div></li>' . "\r\n";
					break;
				}
			// vertical template
			case 'vertical': {
					$tvfields = json_encode(array('fieldnames' => $this->fieldnames, 'fieldtypes' => $this->fieldtypes, 'csvseparator' => $this->configuration['csvseparator']));
					$tvheading = '';
					foreach ($this->fieldnames as $fieldname) {
						$type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
						$elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
						$default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
						if ($this->fields[$fieldname]['width']) {
							$tvcss .= '.multitv #[+tvid+]list li.element .' . $fieldname . ' { width: ' . $this->fields[$fieldname]['width'] . 'px !important }' . "\r\n";
						}
						switch ($type) {
							case 'thumb': {
									$tvelement .= '<div class="tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
									$hasthumb = ' hasthumb';
									break;
								}
							default: {
									$tvelement .= '<label for="' . $tvid . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</label>';
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . '<br />' . "\r\n";
								}
						}
					}
					$tvelement = '<li class="element' . $hasthumb . '"><div>' . $tvelement;
					$tvelement .= '<a href="#" class="copy" title="[+tvlang.add+]">[+tvlang.add+]</a>' . "\r\n";
					$tvelement .= '<a href="#" class="remove" title="[+tvlang.remove+]">[+tvlang.remove+]</a>' . "\r\n";
					$tvelement .= '</div><div class="clear"></div></li>' . "\r\n";
					break;
				}
			// horizontal template
			case 'single': {
					$tvfields = json_encode(array('fieldnames' => $this->fieldnames, 'fieldtypes' => $this->fieldtypes, 'csvseparator' => $this->configuration['csvseparator']));
					$tvheading = '';
					foreach ($this->fieldnames as $fieldname) {
						$type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
						$elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
						$default = (isset($this->fields[$fieldname]['default'])) ? $this->fields[$fieldname]['default'] : '';
						switch ($type) {
							case 'thumb': {
									$tvelement .= '<div class="tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
									$hasthumb = ' hasthumb';
									break;
								}
							default: {
									$tvelement .= '<label for="' . $tvid . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</label>';
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . '<br />' . "\r\n";
								}
						}
					}
					$tvelement = '<li class="element single' . $hasthumb . '"><div>' . $tvelement;
					$tvelement .= '</div><div class="clear"></div></li>' . "\r\n";
					break;
				}
			// datatable template
			case 'datatable': {
					if (!$this->configuration['sorting']) {
						$fieldcolumns = array(
							array(
								'mData' => 'MTV_RowId',
								'sTitle' => '',
								'sClass' => 'handle',
								'bSortable' => FALSE,
								'sWidth' => '2px'
							)
						);
					}
					$tableClasses = array();
					if ($this->configuration['radioTabs']) {
						$fieldcolumns[] = array(
							'mData' => 'fieldTab',
							'sTitle' => '',
							'bSortable' => FALSE,
							'bVisible' => FALSE
						);
					}
					if (count($this->fieldcolumns)) {
						foreach ($this->fieldcolumns as $column) {
							$fieldcolumns[] = array(
								'mData' => (isset($column['render']) && $column['render'] != '') ? 'mtvRender' . ucfirst($column['fieldname']) : $column['fieldname'],
								'sTitle' => (isset($column['caption'])) ? $column['caption'] : ((isset($this->fields[$column['fieldname']]['caption'])) ? $this->fields[$column['fieldname']]['caption'] : $column['fieldname']),
								'sWidth' => (isset($column['width'])) ? $column['width'] : ((isset($this->fields[$column['fieldname']]['width'])) ? $this->fields[$column['fieldname']]['width'] : ''),
								'bSortable' => $this->configuration['sorting'],
								'bVisible' => (isset($column['visible'])) ? (bool) $column['visible'] : ((isset($this->fields[$column['fieldname']]['visible'])) ? (bool) $this->fields[$column['fieldname']]['visible'] : TRUE),
							);
						}
					} else {
						foreach ($this->fields as $key => $column) {
							$fieldcolumns[] = array(
								'mData' => $key,
								'sTitle' => (isset($column['caption'])) ? $column['caption'] : $column['fieldname'],
								'bSortable' => $this->configuration['sorting']
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
								case 'thumb': {
										$tvelement = '<div class="tvimage" id="' . $tvid . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
										$hasthumb = ' hasthumb';
										break;
									}
								default: {
										$tvelement = '<label for="' . $tvid . $fieldname . '">' . $caption . '</label>';
										$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default) . "\r\n";
									}
							}
							$tvElements[] = $tvelement;
						}

						$tabplaceholder = array(
							'id' => ($this->configuration['radioTabs']) ? $tvid . 'tab_radio_' . $tab['value'] : $tvid . 'tab_' . $key,
							'tvid' => $tvid,
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
					if ($this->configuration['hideHeader']) {
						$tableClasses[] = 'hideHeader';
					}
					$tvfields = json_encode(array(
						'fieldnames' => $this->fieldnames,
						'fieldtypes' => $this->fieldtypes,
						'fieldcolumns' => $fieldcolumns,
						'fieldrte' => $this->fieldsrte,
						'csvseparator' => $this->configuration['csvseparator'],
						'tableClasses' => implode(' ', $tableClasses),
						'radioTabs' => $this->configuration['radioTabs'],
						'sorting' => $this->configuration['sorting']
					));
				}
		}

		// populate tv template
		$scriptfiles = array();
		$cssfiles = array();
		$settings = array();
		$files = array();
		$placeholder = array();

		include ($this->includeFile('default' . $this->cmsinfo['clipper'], 'setting'));
		$files['scripts'] = $settings['scripts'];
		$files['css'] = $settings['css'];
		if ($this->configuration['enablePaste']) {
			include ($this->includeFile('paste' . $this->cmsinfo['clipper'], 'setting'));
			$files['scripts'] = array_merge($files['scripts'], $settings['scripts']);
			$files['css'] = array_merge($files['css'], $settings['css']);
			$placeholder['paste'] = file_get_contents($this->includeFile('paste', 'template', '.html'));
		} else {
			$placeholder['paste'] = '';
		}
		if ($this->configuration['enableClear'] && $this->display != 'datatable') {
			$placeholder['clear'] = file_get_contents($this->includeFile('clear', 'template', '.html'));
		} else {
			$placeholder['clear'] = '';
		}
		if ($this->display == 'datatable') {
			include ($this->includeFile('datatable' . $this->cmsinfo['clipper'], 'setting'));
			$files['scripts'] = array_merge($files['scripts'], $settings['scripts']);
			$files['css'] = array_merge($files['css'], $settings['css']);
			$placeholder['data'] = file_get_contents($this->includeFile('datatable', 'template', '.html'));
			$placeholder['script'] = file_get_contents($this->includeFile('datatableScript' . $this->cmsinfo['clipper'], 'template', '.html'));
			$placeholder['edit'] = file_get_contents($this->includeFile('edit', 'template', '.html'));
			$placeholder['editform'] = $tvelement;
		} else {
			$placeholder['data'] = file_get_contents($this->includeFile('sortablelist', 'template', '.html'));
			$placeholder['script'] = file_get_contents($this->includeFile('sortablelistScript' . $this->cmsinfo['clipper'], 'template', '.html'));
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
				$scriptfiles[] = $this->modx->getJqueryPluginTag($file['name'], $tvpath . $file['path'], FALSE);
			}
		}

		$placeholder['cssfiles'] = implode("\r\n", $cssfiles);
		$placeholder['scriptfiles'] = implode("\r\n", $scriptfiles);
		$placeholder['tvcss'] = '<style type="text/css">' . "\r\n" . $tvcss . "\r\n" . '</style>';
		$placeholder['tvheading'] = $tvheading;
		$placeholder['tvmode'] = $this->display;
		$placeholder['tvfields'] = $tvfields;
		$placeholder['tvlanguage'] = $tvlanguage;
		$placeholder['tvelement'] = $tvelement;
		$placeholder['tvvalue'] = $tvvalue;
		$placeholder['tvid'] = $tvid;
		$placeholder['tvpath'] = $tvpath;
		$placeholder['tvkcfinder'] = $this->cmsinfo['kcfinder'];
		$placeholder['tvthumbs'] = $this->cmsinfo['thumbsdir'];
		$placeholder['tvmtvpath'] = MTV_PATH;

		$tvtemplate = $this->renderTemplate('multitv', $placeholder);

		return $tvtemplate;
	}

	function getMultiValue($params) {
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
				if ($tvOutput == FALSE && $published == '2') {
					$tvOutput = $this->modx->getTemplateVarOutput(array($this->tvName), $params['docid'], '0');
				}
				break;
		}
		$tvOutput = $tvOutput[$this->tvName];
		$tvOutput = json_decode($tvOutput, TRUE);
		if (isset($tvOutput['fieldValue'])) {
			$tvOutput = $tvOutput['fieldValue'];
		}
		return $tvOutput;
	}

	function displayMultiValue($tvOutput, $params) {
		// replace masked placeholder tags (for templates that are set directly set in snippet call by @CODE)
		$maskedTags = array('((' => '[+', '))' => '+]');
		$params['outerTpl'] = str_replace(array_keys($maskedTags), array_values($maskedTags), $params['outerTpl']);
		$params['rowTpl'] = str_replace(array_keys($maskedTags), array_values($maskedTags), $params['rowTpl']);

		$countOutput = count($tvOutput);
		$firstEmpty = TRUE;
		if ($countOutput) {
			// check for first item empty
			foreach ($tvOutput[0] as $value) {
				if ($value != '') {
					$firstEmpty = FALSE;
				}
			}
		}

		// stop if there is no output
		if (!$countOutput || $firstEmpty) {
			if ($params['emptyOutput']) {
				// output nothing
				return '';
			} else {
				// output empty outer template
				$parser = new newChunkie($this->modx);
				$parser->setPlaceholder('wrapper', '');
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
	function sort(&$array, $sortkey, $sortdir = 'asc') {
		$sortkey = explode(':', $sortkey);
		if (array_search($sortkey[0], $this->fieldnames) === FALSE) {
			return;
		}
		$this->sorttype = ($sortkey[1]) ? $sortkey[1] : 'text';
		$this->sortkey = $sortkey[0];
		$this->sortdir = ($sortdir === 'desc') ? 'desc' : 'asc';
		usort($array, array($this, 'compareSort'));
	}

	// compare sort values
	private function compareSort($a, $b) {
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

?>
