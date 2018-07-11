<?php
/**
 * 内部接口类
 */
abstract class Inner_AbstractController extends Yaf_Controller_Abstract {
    protected $check_power = false;
    protected $white_list  = [];

    protected function init() {
        $host = Comm_Context::get_server('HTTP_HOST');
        // 检查cookie权限
        if ($this->check_power) {
            $this->check_user_power();
        }
    }

    final public function __destruct() {
        try {
            Comm_Context::clear();
        } catch (Exception $ex) {
        }
    }

    protected function check_user_power() {
        if (!empty($uid) && in_array($uid, $this->white_list)) {
            return true;
        }
        echo 'no power';
        exit;
    }
}
