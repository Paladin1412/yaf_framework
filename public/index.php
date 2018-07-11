<?php
//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
//function xhprof_shutdown(){
//	require_once('xhprof_lib/utils/xhprof_lib.php');
//	require_once('xhprof_lib/utils/xhprof_runs.php');
//	$xhprof_data = xhprof_disable();
//	$xhprof_runs = new XHProfRuns_Default();
//	$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");
//}
//register_shutdown_function('xhprof_shutdown');
//
error_reporting(E_ALL & ~ E_STRICT & ~ E_NOTICE);
ini_set('display_errors',1);

define('ROOTPATH', dirname(dirname(__FILE__)));
define('APPPATH', ROOTPATH . '/application');
define('TPLPATH', APPPATH  . '/views');
define('DMNPATH', ROOTPATH . '/daemon');
define('SYSLIB_PATH', ROOTPATH . '/libraries');

$app = new Yaf_Application(APPPATH . '/config/application.ini');
$app->bootstrap()->run();
