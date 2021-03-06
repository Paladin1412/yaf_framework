<?php
/**
 * cli入口
 *
 * @copyright copyright(2011) weibo.com all rights reserved
 *  <chengxuan@staff.sina.com.cn>
 */
error_reporting(E_ALL & ~ E_STRICT & ~ E_NOTICE);
ini_set('display_errors','on');
/* @MARK: 这部分应该交给WebServer去做 */
header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Pragma: no-cache");

define('ROOTPATH', dirname(dirname(__FILE__)));
define('APPPATH', ROOTPATH . '/application');
define('TPLPATH', APPPATH  . '/views');
define('DMNPATH', ROOTPATH . '/daemon');
define('COOKIE_CONF_PATH', ROOTPATH. '/libraries/SinaSSO/cookie.conf');
define('VISITOR_CONF_PATH', '/data1/www/privdata/v6.weibo.com/SinaSSO/visitor.conf.ini');    
define('LOCALE_PATH', '/data1/www/privdata/v6.weibo.com/languages/');
$app = new Yaf_Application(APPPATH . '/config/application.ini');
$app->bootstrap()->getDispatcher()->dispatch(new Yaf_Request_Simple());
