<?php

/**
 * url rewrite规则, apache中的rewrite都应该搬到这里来
 * 
 * 另一种方案是用yaf的路由器实现, 暂时先使用这种简单直观的, 以后再统一
 * 
 * @author wangguan@staff.sina.com.cn
 */

class RewritePlugin extends Yaf_Plugin_Abstract {

	public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
		if ($request->isCli()){
			$route_static = new Yaf_Route_Static();
			$router = Yaf_Dispatcher::getInstance()->getRouter(); 
			$router->addRoute("static", $route_static);
			return;
		}else{
			$routes_config = Tool_Conf::get('routers.routers');
			$router = Yaf_Dispatcher::getInstance()->getRouter();
			$router->addConfig($routes_config);
		}

		$uri = ltrim($request->getRequestUri(), '/');

		if (NULL === $uri) {
			return;
		}
	}
}
