<?php
/**
 * multiTV
 *
 * @category    connector
 * @version     2.0 rc 1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 */
$base_path = str_replace($_POST['mtvpath'], '', str_replace('\\', '/', realpath(dirname(__FILE__))) . '/');
if (is_file($base_path . 'assets/cache/siteManager.php')) {
    include_once($base_path . 'assets/cache/siteManager.php');
}
if (!defined('MGR_DIR') && is_dir($base_path . 'manager')) {
    define('MGR_DIR', 'manager');
}

// Include the nessesary files
define('MODX_MANAGER_PATH', $base_path . MGR_DIR . '/');
require_once(MODX_MANAGER_PATH . 'includes/config.inc.php');
require_once(MODX_MANAGER_PATH . 'includes/protect.inc.php');

// Setup the MODx API
define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

//start session
startCMSSession();

// initiate a new document parser
include_once(MODX_MANAGER_PATH . '/includes/document.parser.class.inc.php');
$modx = new DocumentParser;

// provide the MODx DBAPI
$modx->db->connect();

// provide the $modx->documentMap and user settings
$modx->getSettings();

// set customtv (base) path
define('MTV_PATH', str_replace(MODX_BASE_PATH, '', str_replace('\\', '/', realpath(dirname(__FILE__)))) . '/');
define('MTV_BASE_PATH', MODX_BASE_PATH . MTV_PATH);

// include classfile
if (!class_exists('multiTV')) {
    include MTV_BASE_PATH . 'includes/multitv.class.php';
}
if (file_exists(MTV_BASE_PATH . 'languages/' . $modx->config['manager_language'] . '.language.inc.php')) {
    include MTV_BASE_PATH . 'languages/' . $modx->config['manager_language'] . '.language.inc.php';
} else {
    include MTV_BASE_PATH . 'languages/english.language.inc.php';
}

// retrieve parameter
$mode = isset($_POST['mode']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['mode']) : false;
$config = isset($_POST['config']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['config']) : false;
$configtype = isset($_POST['configtype']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['configtype']) : 'tv';
$action = isset($_POST['action']) ? preg_replace('/[^a-zA-Z0-9_-]+/', '', $_POST['action']) : false;
$docid = isset($_POST['id']) ? intval($_POST['id']) : false;
$tvid = isset($_POST['tvid']) ? intval(str_replace('tv', '', $_POST['tvid'])) : false;

switch ($configtype) {
    case 'module' :
        $type = 'moduleconfig';
        break;
    case 'tv' :
    default:
        $type = 'config';
        break;
}

$answer = array();
switch ($mode) {
    case 'dbtable':
        if ($modx->hasPermission('exec_module')) {
            if ($action && $config) {
                $multiTV = new multiTV($modx, array(
                    'type' => 'module',
                    'tvUrl' => MTV_PATH
                ));
                // config exists?
                $settings = $multiTV->loadSettings($config, $type, false);
                if ($settings) {
                    $processors = (isset($settings['processors'])) ? $settings['processors'] : '';
                    $includeFile = $multiTV->includeFile($action, 'processor', '.inc.php', $processors);
                    if (!$includeFile) {
                        $includeFile = $multiTV->includeFile($action, 'processor', '.inc.php');
                    }
                    // processor available?
                    if ($includeFile) {
                        include $includeFile;
                    } else {
                        $answer['error'] = true;
                        $answer['msg'] = $language['connector.noprocessor'];
                    }
                } else {
                    $answer['error'] = true;
                    $answer['msg'] = $language['connector.noconfig'];
                }
            } else {
                $answer['error'] = true;
                $answer['msg'] = $language['connector.illegal'];
            }
        } else {
            $answer['error'] = true;
            $answer['msg'] = $language['connector.rights'];
        }
        break;
    case 'datatable':
    default:
        if ($action && $tvid) {
            // document exists?
            $res = $modx->db->select('*', $modx->getFullTableName('site_content'), 'id=' . $docid);
            if ($modx->db->getRecordCount($res)) {
                // document with docId editable?
                $docObj = $modx->getPageInfo($docid, 0, '*');
                if ($docObj) {
                    // get the settings for the multiTV
                    $tvSettings = $modx->getTemplateVar($tvid, '*', $docid, $docObj['published']);
                    if ($tvSettings && $tvSettings['elements'] = '@INCLUDE' . MTV_PATH . 'multitv.customtv.php') {
                        $multiTV = new multiTV($modx, array(
                            'type' => 'tv',
                            'tvDefinitions' => $tvSettings,
                            'tvUrl' => MTV_PATH
                        ));
                        $includeFile = $multiTV->includeFile($action, 'processor');
                        // processor available?
                        if ($includeFile) {
                            include $includeFile;
                        } else {
                            $answer['error'] = true;
                            $answer['msg'] = $language['connector.noprocessor'];
                        }
                    } else {
                        $answer['error'] = true;
                        $answer['msg'] = $language['connector.nomultitv'];
                    }
                } else {
                    $answer['error'] = true;
                    $answer['msg'] = $language['connector.rights'];
                }
            } else {
                $answer['error'] = true;
                $answer['msg'] = $language['connector.nodoc'];
            }
        } else {
            $answer['error'] = true;
            $answer['msg'] = $language['connector.illegal'];
        }
        break;
}

echo json_encode($answer);
exit();
