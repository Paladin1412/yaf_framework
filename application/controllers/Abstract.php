<?php
/**
 * 微博前端控制器基类
 *
 * @package
 *
 * @copyright copyright(2011) weibo.com all rights reserved
 * @author    hqlong <qinglong@staff.sina.com.cn>
 * @version   2011-4-6
 */
abstract class AbstractController extends Yaf_Controller_Abstract {
    // 登录状态
    const MUST_LOGIN = 0;

    // 未登录状态
    const NOT_LOGIN = 1;
    // 无状态
    const MAYBE_LOGIN = 2;

    const V36_PREFIX = "V36_";

    // 控制器权限，默认权限为必须登录才可访问
    public $authorize = self::MUST_LOGIN;

    public $init_owner = false;

    public $init_skin = false;

    public $init_viewer = true;

    // 验证soo的session
    const MUST_CHECK_SESSON = true;
    // 默认不验证
    public $check_sesson = false;

    // 判断是否是PC登录
    public $is_pc = 0;

    //是否判断登录和ua,返回跳转URL (tvenergy aj用)
    public $check_login = false;

    //是否验证referer
    public $check_referer = false;

    public function init() {
        // rpc调用不处理
        if (strpos($_SERVER['HTTP_USER_AGENT'], "Yar")) {
            return;
        }

        // 验证referer,防止csrf漏洞
        if ($this->check_referer) {
            $this->check_referer();
        }

        if ($this->check_login) {
            $this->check_login();
        }
        //$this->check_ispc();
    }

    public function render_ajax($code, $msg = '', $data = null) {
    }

    public static function send_http_response_headers_for_cdn($cache_time) {
        if (!headers_sent() && $cache_time > 0) {
            header("Expires: " . gmdate('D, d M Y H:i:s T', time() + $cache_time));
            header_remove('Pragma');
            header("Cache-Control: max-age={$cache_time}");
            header("Use-Cdn: yes");
        }
    }

    public function check_login() {
    }

    //根据ua判断是pc还是移动端
    public function check_ispc() {
    }

    //检查referer
    public function check_referer() {
        $request         = Yaf_Dispatcher::getInstance()->getRequest();
        $controller_name = $request->getControllerName();
        if (!isset($_SERVER['HTTP_REFERER'])) {
            Tool_Log_Comm::write_log($controller_name, 'no referer');
        }
        $parse_url = @parse_url($_SERVER['HTTP_REFERER']);
        $domain    = Comm_Context::get_domain();
    }

}
