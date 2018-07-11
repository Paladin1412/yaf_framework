<?php
/**
 * Cli接口Controller抽象
 */
abstract class Cli_AbstractController extends Yaf_Controller_Abstract {
    //Action路径
    const ACTION_DIR = 'controllers/Cli/actions/';

    /**
     * 初始化
     *
     * @throws Exception_System
     */
    public function init() {
        $q = $this->getRequest();

        //必需要Cli中执行
        if (!$q->isCli()) {
            throw new Comm_Exception_Program('非法入口访问cli程序', '200302');
        }

        //action 处理
        $action_name = $q->getActionName();
        if (!method_exists($this, $action_name . 'Action')) {
            $ctrl_name     = $q->getControllerName();
            $this->actions = array(
                $action_name => self::ACTION_DIR . ucfirst($ctrl_name) . '/' . ucfirst($action_name) . '.php'
            );
        }

        //禁止自动渲染模板
        $dispatcher = Yaf_Dispatcher::getInstance();
        $dispatcher->autoRender(false);
        $dispatcher->disableView();
    }

    /**
     * 获取当前服务器IP
     *
     * @return string
     */
    public function server_ip() {
        $str = "/sbin/ifconfig | grep 'inet addr' | awk '{ print $2 }' | awk -F ':' '{ print $2}' | head -1";
        $_pp = @popen($str, 'r');
        $ip  = trim(@fread($_pp, 512));
        @pclose($_pp);
        return $ip;
    }

    /**
     * 获取服务器名称
     *
     * @return string
     *
     *
     */
    public function hostname() {
//         if(isset($_SERVER['HOSTNAME'])) {
//             $hostname = $_SERVER['HOSTNAME'];
//         } else {
        $hostname = 'IP:' . $this->server_ip();
//         }

        return $hostname;
    }

    /**
     * 检查是否是否该停了
     *
     * @param string $type   类别crontab/mcq
     * @param string $action 方法名称
     * @param int    $idx    线程号
     *
     * @return mixed false|string
     *
     */
    public function checkStop($type, $action, $idx, $serial = null) {
        $cache    = new Cache_Cli();
        $hostname = $this->hostname();
        $key      = $_SERVER['SINASRV_SERVER_NAME'] . '_' . $type . '_' . $action . '_' . $idx . '_' . $hostname;
        if (!empty($serial)) {
            $key = $key . '_' . $serial;
        }
        $result = $cache->get_cron_delay($key);
        if (empty($result)) {
            $result = Dr_Cli::get_value($key);
            if (empty($result) || $result == 'reboot') {
                $cache->create_cron_delay($key, 'yes');
                if ($result == 'reboot') {
                    Dw_Cli::unset_key($key);
                    return 'stop';
                }
            } else {
                $cache->create_cron_delay($key, 'no');
                return 'stop';
            }
        } else {
            if ($result == 'no') {
                return 'stop';
            }
        }
        $key    = $_SERVER['SINASRV_SERVER_NAME'] . '_' . $type . '_' . $action;
        $result = $cache->get_cron_delay($key);
        if (empty($result)) {
            $result = Dr_Cli::get_value($key);
            if (empty($result)) {
                $cache->create_cron_delay($key, 'yes');
            } else {
                $cache->create_cron_delay($key, 'no');
                return 'stop';
            }
        } else {
            if ($result == 'no') {
                return 'stop';
            }
        }
        return false;
    }

    /**
     * 输出内容并记录日志(日志只记录500行，每执行500次检测一次文件大小，自动换行)
     *
     * @param string $text
     *
     * @return void
     *
     *
     */
    public function output($text) {
        static $i = 0;
        if ($i % 500 === 0) {
            //检查日志是否超过大小，如果超过，删除之前的内容
            $i = 0;
        }
        ++$i;

        //写入日志并输出
        $text .= "\n";
        echo $text;

        $r            = $this->getRequest();
        $proc_num     = Comm_Argchecker::int($r->getParam('proc_num'), 'min,1', 2, 2, 'x');
        $filename     = sprintf('%s/%s/%s/%s_%s.log', $_SERVER['SINASRV_APPLOGS_DIR'], $r->getModuleName(), $r->getControllerName(), $r->getActionName(), $proc_num);
        $filename_dir = dirname($filename);
        if (!is_dir($filename_dir)) {
            mkdir($filename_dir, 0775, true);
        }
        $this->checkLogMaxLine($filename, 500);
        file_put_contents($filename, $text, FILE_APPEND);
    }

    /**
     * 类型于printf方式输出并记录日志
     *
     * @return void
     */
    public function printf() {
        $args_array = func_get_args();
        $text       = call_user_func_array('sprintf', $args_array);
        $this->output($text);
    }

    /**
     * 检查日志文件的最大行数，如果超过，削减（可能不会完全按照max_line来削）
     *
     * @param string $filename 文件名称
     * @param int    $max_line 最大行数
     *
     * @return bool
     */
    public function checkLogMaxLine($filename, $max_line) {
        if (!is_file($filename)) {
            return false;
        }

        $filesize = filesize($filename);
        $length   = 1024;
        $fp       = fopen($filename, 'r');

        $position = $filesize;    //指针位置
        $lf_num   = 0;              //换行数

        $content = '';
        do {
            $position = max(0, $position - $length);
            fseek($fp, $position);
            $current_content = fread($fp, $length);
            $content         = $current_content . $content;
            $lf_num          += substr_count($current_content, "\n");
        } while ($position > 0 && $lf_num <= $max_line);
        fclose($fp);

        //超过行数，截取完整数据
        if ($lf_num > $max_line) {
            $content = ltrim(strstr($content, "\n"), "\n");
            file_put_contents($filename, $content);
            return true;
        } else {
            return false;
        }
    }
}
