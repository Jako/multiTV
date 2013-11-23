<?php
/**
 * Ditto Extender: multiTvFilter
 *
 * @category 	extender
 * @version 	1.1
 * @license 	http: //www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * Filter displayed Ditto rows by multiTV field content values. As all other Ditto filters the row is filtered if the condition is true.
 *
 * Parameters:
 *   multiTvFilterBy - multiTV name to filter by (required)
 *   multiTvFilterOptions - (Array of) json encoded object of filter options - example: {"name":"title","type":"text","value":"Important","mode":"contains"}
 *
 * Filter options:
 *   name - multiTV field name that is used for filtering
 *   type - Type of the multiTV field content (possible content: date, text)
 *   value - The value the multiTV field content is filtered with
 *   mode - Mode for filtering the multiTV field content
 *
 *   Allowed modes for text:
 *     contains - filtered if one value contains filterValue
 *     allcontains - filtered if all values containing filterValue
 *     containsnot - filtered if one value not contains filterValue
 *     allcontainsnot - filtered if all values not containing filterValue
 *     is - filtered if one value is filterValue
 *     allis - filtered if all values are filterValue
 *     isnot - filtered if one value is not filterValue
 *     allisnot - filtered if all values are not filterValue
 *   Allowed modes for date:
 *     before - filtered if one value is before filterValue
 *     beforeall - filtered if all values are before filterValue
 *     equal - filtered if one value is equal filterValue
 *     equalall - filtered if all values are equal filterValue
 *     after - filtered if one value is after filterValue
 *     afterall - filtered if one value is after filterValue
 *
 * Example: display all documents within 3, 4, and 5 containers that multiTV event values in column title not containing 'Important' in any multiTV row
 *
 * [[Ditto?
 * &parents=`3,4,5`
 * &display=`all`
 * &tpl=`...`
 * &extenders=`@FILE assets/tvs/multitv/dittoExtender/multitvfilter.extender.inc.php`
 * &multiTvFilterBy=`event`
 * &multiTvFilterOptions=`[{"name":"title","type":"text","value":"Important","mode":"contains"}]`]]
 * ]]
 *
 */
$safetags = array('&_PHX_INTERNAL_091_&' => '[', '&_PHX_INTERNAL_093_&' => ']');

$GLOBALS['multiTvFilterBy'] = isset($multiTvFilterBy) ? explode(',', $multiTvFilterBy) : NULL;
$GLOBALS['multiTvFilterOptions'] = isset($multiTvFilterOptions) ? json_decode(str_replace(array_keys($safetags), $safetags, $multiTvFilterOptions)) : NULL;
$GLOBALS['multiTvFilterOptions'] = (is_object($GLOBALS['multiTvFilterOptions'])) ? array($GLOBALS['multiTvFilterOptions']) : $GLOBALS['multiTvFilterOptions'];
$GLOBALS['multiTvFilterDebug'] = isset($multiTvFilterDebug) ? $multiTvFilterDebug : FALSE;

$filters['custom']['multiTvFilter'] = array($multiTvFilterBy, 'multiTvFilter');

if (!function_exists('multiTvFilter')) {

	function multiTvFilter($resource) {
		global $modx;

		if (!$GLOBALS['multiTvFilterBy'] || !$GLOBALS['multiTvFilterOptions']) {
			// do nothing (leave document within result set)
			return 1;
		} else {
			// filter it
			foreach ($GLOBALS['multiTvFilterBy'] as $key => $filterBy) {
				$tvvalue = json_decode($resource[$filterBy]);
				if (!$tvvalue) {
					return 0;
				}
				$options = $GLOBALS['multiTvFilterOptions'][$key];
				if (isset($options->name)) {
					$filterName = $options->name;
				} else {
					die('Error in &multiTvFilterOptions. \'name\' not set!');
				}
				if (isset($options->type)) {
					$filterType = $options->type;
				} else {
					die('Error in &multiTvFilterOptions. \'type\' not set!');
				}
				if (isset($options->value)) {
					$filterValue = $options->value;
				} else {
					die('Error in &multiTvFilterOptions. \'value\' not set!');
				}
				if (isset($options->mode)) {
					$filterMode = $options->mode;
				} else {
					die('Error in &multiTvFilterOptions. \'mode\' not set!');
				}
				$filterConjunction = (isset($options->conjunction)) ? $options->conjunction : 'AND';

				unset($filteredCurrent);
				if ($GLOBALS[multiTvFilterDebug]) {
					echo '<p>Start: ';
				}
				foreach ($tvvalue->fieldValue as $value) {
					switch ($filterType) {
						case 'date':
							$currentValue = strtotime($value->$filterName);
							switch ($filterMode) {
								case 'before': // filtered if one value is before filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && ($currentValue <= $filterValue) : ($currentValue <= $filterValue);
									break;
								case 'beforeall': // filtered if all values are before filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || ($currentValue <= $filterValue) : ($currentValue <= $filterValue);
									break;
								case 'equal': // filtered if one value is equal filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && ($currentValue != $filterValue) : ($currentValue != $filterValue);
									break;
								case 'equalall': // filtered if all values are equal filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || ($currentValue != $filterValue) : ($currentValue != $filterValue);
									break;
								case 'after': // filtered if one value is after filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && ($currentValue >= $filterValue) : ($currentValue >= $filterValue);
									break;
								case 'afterall': // filtered if all values are after filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || ($currentValue >= $filterValue) : ($currentValue >= $filterValue);
									break;
								default:
									break;
							}
							if ($GLOBALS[multiTvFilterDebug]) {
								echo ($filteredCurrent) ? 'gefiltert, ' : 'ungefiltert, ';
							}
							break;
						case 'text':
						default:
							$currentValue = $value->$filterName;
							switch ($filterMode) {
								case 'contains': // filtered if one value contains filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || (strpos($currentValue, $filterValue) !== FALSE) : (strpos($currentValue, $filterValue) !== FALSE);
									break;
								case 'allcontains': // filtered if all values containing filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && (strpos($currentValue, $filterValue) !== FALSE) : (strpos($currentValue, $filterValue) !== FALSE);
									break;
								case 'containsnot': // filtered if one value not contains filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || (strpos($currentValue, $filterValue) === FALSE) : (strpos($currentValue, $filterValue) !== FALSE);
									break;
								case 'allcontainsnot': // filtered if all values not containing filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && (strpos($currentValue, $filterValue) === FALSE) : (strpos($currentValue, $filterValue) === FALSE);
									break;
								case 'is': // filtered if one value is filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || ($currentValue === $filterValue) : ($currentValue === $filterValue);
									break;
								case 'allis': // filtered if all values are filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && ($currentValue === $filterValue) : ($currentValue === $filterValue);
									break;
								case 'isnot': // filtered if one value is not filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent || ($currentValue !== $filterValue) : ($currentValue !== $filterValue);
									break;
								case 'allisnot': // filtered if all values are not filterValue
									$filteredCurrent = (isset($filteredCurrent)) ? $filteredCurrent && ($currentValue !== $filterValue) : ($currentValue !== $filterValue);
								default:
									break;
							}
							if ($GLOBALS[multiTvFilterDebug]) {
								echo ($filteredCurrent) ? 'gefiltert, ' : 'ungefiltert, ';
							}
							break;
					}
				}
				if ($GLOBALS[multiTvFilterDebug]) {
					echo(' Gesamt: ' . (($filteredCurrent) ? 'gefiltert ' : 'ungefiltert ') . '</p>' . '<pre>' . print_r($options, true) . ' ' . print_r($tvvalue->fieldValue, true) . '</pre>');
				}
				switch ($filterConjunction) {
					case 'OR':
						$filtered = (isset($filtered)) ? ($filtered || $filteredCurrent) : $filteredCurrent;
						break;
					case 'AND':
					default:
						$filtered = (isset($filtered)) ? ($filtered && $filteredCurrent) : $filteredCurrent;
						break;
				}
			}
			return !$filtered;
		}
	}

}
?>