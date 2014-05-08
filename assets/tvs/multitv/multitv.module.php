<?php
/**
 * multiTV
 *
 * @category    module
 * @version     2.0 alpha 3
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako (thomas.jakobi@partout.info)
 *
 * @internal    description: <strong>2.0 alpha 3</strong> Custom Template Variabe containing a sortable multi item list or a datatable and a datatable CRUD module.
 * @internal    module code: include(MODX_BASE_PATH.'assets/tvs/multitv/multitv.module.php');
 */
if (IN_MANAGER_MODE != 'true') {
    die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}

global $modx;

// set customtv (base) path
define('MTV_PATH', str_replace(MODX_BASE_PATH, '', str_replace('\\', '/', realpath(dirname(__FILE__)))) . '/');
define('MTV_BASE_PATH', MODX_BASE_PATH . MTV_PATH);

// load classfile
$class_file = MTV_BASE_PATH . 'includes/multitv.class.php';
if (!file_exists($class_file)) {
    $modx->messageQuit(sprintf('Classfile "%s" not found. Did you upload the module files?', $class_file));
}
require_once($class_file);

$configs = isset($configs) ? array_map('trim', explode(',', $configs)) : array();

$options = array(
    'moduleId' => (int)$_GET['id'],
    'action' => isset($_GET['action']) ? trim(strip_tags($_GET['action'])) : 'load',
    'managerDir' => MGR_DIR . '/',
    'moduleUrl' => MTV_PATH,
    'managerTheme' => $modx->config['manager_theme'],
    'type' => 'module',
    'modulename' => $_SESSION['itemname']
);

$multiTV = new multiTV($modx, $options);

if ($configs) {
    $output = $multiTV->runModule($configs);
} else {
    $output = '<h3>Please specify at least one configuration in module config</h3>';
}

echo $output;
