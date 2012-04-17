<?php
/**
 * multiTV
 *
 * @category 	classfile
 * @version 	1.3
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    description: <strong>1.3</strong> Transform template variables into a sortable multi item list.
 */
if (!function_exists('renderFormElement')) {
	include MODX_BASE_PATH . 'manager/includes/tmplvars.inc.php';
}

class multiTV {

	public $tvName = '';
	public $tvID = 0;
	public $tvCaption = '';
	public $tvDescription = '';
	public $tvDefault = '';
	public $tvValue = '';
	public $display = '';
	public $fieldnames = array();
	public $fields = array();
	public $templates = array();
	public $language = array();

	// Init
	function multiTV($tvDefinitions) {
		global $modx;

		if (isset($tvDefinitions['name'])) {
			$this->tvName = $tvDefinitions['name'];
			$this->tvID = $tvDefinitions['id'];
			$this->tvCaption = $tvDefinitions['caption'];
			$this->tvDescription = $tvDefinitions['description'];
			$this->tvDefault = $tvDefinitions['default_text'];
			$this->tvValue = $tvDefinitions['value'];
		} else {
			$modx->messageQuit('No multiTV definitions set');
		}
		$settings = array();
		include ($this->includeFile($this->tvName));
		$this->tvSettings($settings);
		$language = array();
		include ($this->includeFile($modx->config['manager_language'], 'language'));
		$this->language = $language;
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
	function tvSettings($settings) {
		$this->fields = $settings['fields'];
		$this->fieldnames = array_keys($this->fields);
		$this->templates = $settings['templates'];
		$this->display = $settings['display'];
	}

	// invoke modx renderFormElement and change the output (to multiTV demands)
	function renderMultiTVFormElement($fieldType, $fieldName, $fieldElements, $fieldClass) {
		switch ($fieldType) {
			case 'url' : {
					$fieldType == 'text';
					break;
				}
			case 'richtext' : {
					$fieldType == 'textarea';
					break;
				}
		}
		$fieldName .= '_mtv';
		$currentClass = '';
		$formElement = renderFormElement($fieldType, 0, '', $fieldElements, '', '', array());
		$formElement = preg_replace('/( tvtype=\"[^\"]+\")/', '', $formElement); // remove tvtype attribute
		$formElement = preg_replace('/(<label[^>]*><\/label>)/', '', $formElement); // remove empty labels
		$formElement = preg_replace('/(\s*<script[^>]*>[^(<\/script>)].*<\/script>)/s', '', $formElement); // remove scripts
		$formElement = preg_replace('/( id=\"[^\"]+)/', ' id="[+tvid+]' . $fieldName, $formElement); // change id attributes
		$formElement = preg_replace('/( name=\"[^\"]+)/', ' name="[+tvid+]' . $fieldName, $formElement); // change name attributes
		preg_match('/<.*class=\"([^\"]*)/s', $formElement, $currentClass); // get current classes
		$formElement = preg_replace('/class=\"[^\"]*\"/s', '', $formElement, 1); // remove all classes
		$fieldClass = (isset($currentClass[1])) ? $currentClass[1] . ' ' . $fieldClass : $fieldClass;
		$formElement = preg_replace('/(<\w+)/', '$1 class="' . $fieldClass . '"', $formElement, 1); // add class to first tag (the input)	
		$formElement = preg_replace('/<label for=[^>]*>([^<]*)<\/label>/s', '<label class="inlinelabel">$1</label>', $formElement); // add label class
		$formElement = preg_replace('/(BrowseServer\(\'tv0\'\))/', 'BrowseServer($j(this).prev(\'input\').attr(\'id\'))', $formElement, 1); // change filebrowser onclick script
		$formElement = str_replace('document.forms[\'mutate\'].elements[\'tv0\'].value=\'\';document.forms[\'mutate\'].elements[\'tv0\'].onblur(); return true;', '$j(this).prev(\'input\').val(\'\').trigger(\'change\');', $formElement); // change datepicker onclick script
		$formElement = preg_replace('/( onmouseover=\"[^\"]+\")/', '', $formElement); // delete onmouseover attribute
		$formElement = preg_replace('/( onmouseout=\"[^\"]+\")/', '', $formElement); // delete onmouseout attribute
		$formElement = str_replace(array('style="width:100%;"', 'style="width:100%"', ' width="100%"', '  width="100"', '<br />', " checked='checked'"), array(''), $formElement);
		if ($fieldType == 'checkbox') {
			$formElement = '<input type="hidden" onchange="documentDirty=true;" name="[+tvid+]linked" value="" />' . "\r\n" . $formElement;
		}
		// echo '<pre>' . htmlspecialchars($formElement) . '</pre>';
		return $formElement;
	}

	// build the output of multiTV script and css
	function generateScript() {
		$tvid = "tv" . $this->tvID;
		$tvvalue = ($this->tvValue != '') ? $this->tvValue : '[]';
		$tvvalue = str_replace(array('[[', ']]'), array('[ [', '] ]'), $tvvalue);
		$tvfields = json_encode($this->fieldnames);
		$tvlanguage = json_encode($this->language);
		$tvpath = '../' . MTV_PATH;

		// get the javascript and css chunks
		$tvtemplate = file_get_contents($this->includeFile('multitv', 'template', '.html'));
		$tvcss = '';
		$hasthumb = '';

		switch ($this->display) {
			// horizontal template
			case 'horizontal': {
					$tvheading = '<div id="[+tvid+]heading" class="heading">' . "\r\n";
					foreach ($this->fieldnames as $fieldname) {
						$tvheading .= '<span class="inline ' . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</span>' . "\r\n";
						$type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
						$elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
						$tvcss .= '.multitv #[+tvid+]list li.element .inline.' . $fieldname . ', .multitv #[+tvid+]heading .inline.' . $fieldname . ' { width: ' . $this->fields[$fieldname]['width'] . 'px }' . "\r\n";
						switch ($type) {
							case 'thumb': {
									$tvelement .= '<div class="tvimage" id="[+tvid+]' . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
									$hasthumb = ' hasthumb';
									break;
								}
							case 'date': {
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inline ' . $fieldname) . "\r\n";
									$tvcss .= '.multitv #[+tvid+]list li.element .inline.' . $fieldname . ' { width: ' . strval($this->fields[$fieldname]['width'] - 48) . 'px }' . "\r\n";
									break;
								}
							default: {
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inline ' . $fieldname) . "\r\n";
								}
						}
					}
					$tvheading .= '</div>' . "\r\n";
					// wrap tvelements
					$tvelement = '<li class="element inline' . $hasthumb . '"><div>' . $tvelement;
					$tvelement .= '<a href="#" class="remove" title="[+tvlang.remove+]">[+tvlang.remove+]</a></div><div class="clear"></div></li>' . "\r\n";
					break;
				}
			// horizontal template
			case 'vertical': {
					$tvheading = '';
					foreach ($this->fieldnames as $fieldname) {
						$type = (isset($this->fields[$fieldname]['type'])) ? $this->fields[$fieldname]['type'] : 'text';
						$elements = (isset($this->fields[$fieldname]['elements'])) ? $this->fields[$fieldname]['elements'] : '';
						switch ($type) {
							case 'thumb': {
									$tvelement .= '<div class="tvimage" id="[+tvid+]' . $this->fields[$fieldname]['thumbof'] . '_mtvpreview"></div>';
									$hasthumb = ' hasthumb';
									break;
								}
							default: {
									$tvelement .= '<label for="[+tvid+]' . $fieldname . '">' . $this->fields[$fieldname]['caption'] . '</label>';
									$tvelement .= $this->renderMultiTVFormElement($type, $fieldname, $elements, 'inputBox ' . $fieldname) . '<br />' . "\r\n";
								}
						}
					}
					$tvelement = '<li class="element' . $hasthumb . '"><div>' . $tvelement;
					$tvelement .= '<a href="#" class="remove" title="[+tvlang.remove+]">[+tvlang.remove+]</a></div><div class="clear"></div></li>' . "\r\n";
					break;
				}
			// inline popup - i.e. if there are too much elements in one row	
			case 'popup': {
					// Todo
					$tvheading = '';
					$tvelement = '';
					break;
				}
		}

		// populate template
		$placeholder = array();
		$placeholder['tvcss'] = $tvcss;
		$placeholder['tvheading'] = $tvheading;
		$placeholder['tvfields'] = $tvfields;
		$placeholder['tvlanguage'] = $tvlanguage;
		$placeholder['tvelement'] = $tvelement;
		$placeholder['tvvalue'] = $tvvalue;
		$placeholder['tvid'] = $tvid;
		$placeholder['tvpath'] = $tvpath;
		foreach ($this->language as $key => $value) {
			$placeholder['tvlang.' . $key] = $value;
		}
		foreach ($placeholder as $key => $value) {
			$tvtemplate = str_replace('[+' . $key . '+]', $value, $tvtemplate);
		}
		return $tvtemplate;
	}

}

?>
