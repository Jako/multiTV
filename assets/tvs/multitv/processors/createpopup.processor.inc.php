<?php
/**
 * multiTV
 *
 * @category    processor
 * @version     2.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * Create popup processor
 */
$config = isset($_POST['config']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['config']) : false;
$group = isset($_POST['group']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['group']) : false;
$button = isset($_POST['button']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['button']) : false;

$form = isset($settings['buttons'][$group]['buttons'][$button]['form']) ? $settings['buttons'][$group]['buttons'][$button]['form'] : FALSE;

if ($form) {
    $buttoncaption = $settings['buttons'][$group]['buttons'][$button]['caption'];
    $icon = isset($settings['buttons'][$group]['buttons'][$button]['icon']) ? $settings['buttons'][$group]['buttons'][$button]['icon'] : '';

    $tabs = array();
    $tabPages = array();
    foreach ($form as $key => $tab) {
        $tvElements = array();
        foreach ($tab['content'] as $fieldname => $tv) {
            $type = (isset($settings['fields'][$fieldname]['type'])) ? $settings['fields'][$fieldname]['type'] : 'text';
            $elements = (isset($settings['fields'][$fieldname]['elements'])) ? $settings['fields'][$fieldname]['elements'] : '';
            $default = (isset($settings['fields'][$fieldname]['default'])) ? $settings['fields'][$fieldname]['default'] : '';
            $caption = (is_array($tv) && isset($tv['caption'])) ? $tv['caption'] : $settings['fields'][$fieldname]['caption'];
            switch ($type) {
                case 'thumb':
                    $tvElements[] = '<div class="tvimage" id="' . $button . $settings['fields'][$fieldname]['thumbof'] . '_mtvpreview"></div>';
                    break;
                default:
                    $tvElements[] = '<label for="' . $button . $fieldname . '_mtv">' . $caption . '</label>' .
                        str_replace('[+tvid+]', $button, $multiTV->renderMultiTVFormElement($type, $fieldname, $elements, $fieldname, $default));
            }
        }

        $tabplaceholder = array(
            'id' => ($settings['configuration']['radioTabs']) ? $button . 'tab_radio_' . $tab['value'] : $button . 'tab_' . $key,
            'tvid' => $button,
            'caption' => $tab['caption'],
            'value' => $tab['value'],
            'content' => implode("\r\n", $tvElements),
            'radio' => ($settings['configuration']['radioTabs']) ? '1' : '0'
        );
        $formTabTemplate = (!$settings['configuration']['radioTabs']) ? 'editFormTab' : 'editFormTabRadio';
        $tabs[] = $multiTV->renderTemplate($formTabTemplate, $tabplaceholder);
        $tabPages[] = $multiTV->renderTemplate('editFormTabpage', $tabplaceholder);
    }

    $placeholder = array();
    $placeholder['tabs'] = implode("\r\n", $tabs);
    $placeholder['tvid'] = $button;
    $placeholder['tabpages'] = implode("\r\n", $tabPages);
    $editForm = '<div class="tveditform"><form  id="' . $button . 'form">' .
        '<h2>' . $buttoncaption . '</h2>' .
        str_replace(array('<img src="" border="0" alt="No date" />'), array('<img src="media/style/' . $modx->config['manager_theme'] . '/images/icons/cal_nodate.gif" border="0" alt="No date" />'), $multiTV->renderTemplate('editForm', $placeholder)) .
        '<ul class="actionButtons buttons">' .
        '<li><a class="' . $button . '" href="#"><img alt="' . $buttoncaption . '" src="../' . MTV_PATH . 'css/images/' . $icon . '"> ' . $buttoncaption . '</a></li>' .
        '<li><a class="cancel" href="#"><img alt="Abbrechen" src="../' . MTV_PATH . 'css/images/cancel.png"> Abbrechen</a></li>' .
        '</ul></form></div>';

    $answer['error'] = false;
    $answer['msg'] = $editForm;
    return;
}
