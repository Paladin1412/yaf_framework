<?php
/**
 * Enter description here . ..
 *
 * @package   application
 * @author    weibo_tech_homesite <weibo_tech_homesite@staff.sina.com.cn>
 * @copyright 2013 weibo.com all rights reserved
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {

    /**
     * Enter description here . ..
     *
     * @param Yaf_Dispatcher $dispatcher dispatcher
     * @return void
     */
    public function _initLoader(Yaf_Dispatcher $dispatcher) {
        /* 注册本地类名前缀, 这部分类名将会在本地类库查找 */
        $local_namespaces = [
            'common',
            'db',
            'tool'
        ];
        Yaf_Loader::getInstance()->registerLocalNameSpace($local_namespaces);
    }

    /**
     * Enter description here . ..
     *
     * @param Yaf_Dispatcher $dispatcher dispatcher
     * @return void
     *
     * @throws Yaf_Exception_DispatchFailed
     */
    public function _initConfig(Yaf_Dispatcher $dispatcher) {
        Yaf_Registry::set("config", Yaf_Application::app()->getConfig());

        // CLI模式
        if ($dispatcher->getRequest()->isCli()) {
            // 配置SINASRV_CONFIG
            $ini_file = ROOTPATH . '/system/TBSRV_CONFIG';
            if (!is_file($ini_file)) {
                throw new Yaf_Exception_DispatchFailed('Can\'t find the SINASRV_CONFIG.', 500);
            }
            $_SERVER = array_merge($_SERVER, parse_ini_file($ini_file));
        }

        Comm_Context::init();
    }

    /**
     * Enter description here . ..
     *
     * @param Yaf_Dispatcher $dispatcher dispatcher
     * @return void
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        // rpc请求，不加载插件
        if (strpos($_SERVER['HTTP_USER_AGENT'], "Yar")) {
            return;
        }

        //$dispatcher->registerPlugin(new ModulePlugin());

//        $host = strtolower($_SERVER['HTTP_HOST']);

        $dispatcher->registerPlugin(new RewritePlugin());
    }

    /**
     * Enter description here . ..
     *
     * @param Yaf_Dispatcher $dispatcher dispatcher
     * @return void
     */
    public function _initView(Yaf_Dispatcher $dispatcher) {
        /* 关闭自动渲染, 因为我们都是自己主动调用视图的render */
        $dispatcher->disableView();
        $view = $dispatcher->initView(TPLPATH);
        Yaf_Registry::set("view", $view);
    }
}
