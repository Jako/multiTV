<?php
$filename = dirname(__FILE__) . '/russian-UTF8.language.inc.php';
$contents = file_get_contents($filename);
$contents = utf8_decode($contents);
eval('?>' . $contents);
