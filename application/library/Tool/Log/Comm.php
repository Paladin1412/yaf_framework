<?php
/**
 * 通用日志类
 */
class Tool_Log_Comm {
    const LOG_DIR          = 'commlog/'; //日志目录
    const HOMEFEED_LOG_DIR = 'homefeedlog/'; //HomeFeed曝光日志目录

    /** add by zhijun1
     * 虚构错误类型，用于记录异常
     * $location string 抛异常的位置信息，一般是__METHOD__
     * $exception object 异常对象
     */
    public static function except($location, $exception) {
        $logstr = self::except_message($exception);
        return self::write_log("{$location}-EXCEPTION", $logstr);
    }

    public static function except_message($exception) {
        $message = $exception->getMessage();
        $trace   = $exception->getTrace();
        $info    = array();

        $need_args  = 2;
        $need_trace = 5;
        foreach ($trace as $t) {
            if (isset($t['file'])) {
                $finfo = " {$t['line']}@{$match[1]}";
            } else {
                $finfo = '';
            }
            //头几个需要参数信息
            if ($need_args) {
                $need_args--;
                $args = '(' . json_encode($t['args']) . ')';
            } else {
                $args = '';
            }
            $str    = "{$t['class']}{$t['type']}{$t['function']}{$args}{$finfo}";
            $info[] = $str;

            $need_trace--;
            if (!$need_trace) {
                break;
            }
        }

        if (!empty($info)) {
            $trace_str = implode('; ', $info);
            $logstr    = "'{$message}' happened at {$trace_str}";
        } else {
            $logstr = "'{$message}' happened";
        }
        return $logstr;
    }

    /**
     * 记录日志
     * 日志格式：类型|时间|客户ip|日志内容
     */
    public static function write_log($type, $content) {
        if (!isset($_SERVER['SINASRV_APPLOGS_DIR']) || !is_dir($_SERVER['SINASRV_APPLOGS_DIR'])) {
            return;
        }
        $log_dir = $_SERVER['SINASRV_APPLOGS_DIR'] . 'log/';

        if (!is_dir($log_dir)) {
            mkdir($log_dir);
            chmod($log_dir, 0777);
        }
        $log_file = $log_dir . '' . date('Ymd') . '.log';
        $log      = array(
            'type'    => $type,
            'time'    => date("Y-m-d H:i:s"),
            'cip'     => Comm_Context::get_client_ip(),
            'dpool'   => Comm_Context::get_server('SERVER_ADDR'),
            'uid'     => $uid,
            'content' => $content,
        );
        @chmod($log_file, 0777);
        @error_log(self::format_log($log) . "\n", 3, $log_file);
        return;
    }

    /**
     * 记录HomeFeed曝光日志
     * 日志格式：
     * 第一位 为时间戳
     * 第二位 为登陆用户
     * 第三位 是曝光的微博id
     * 第四位 是应用来源id （主站PC来源id）
     * 第五位 是被查看uid （不传，保留）
     * 第六位 是曝光量类型（时间排序1，智能排序5）
     * 第七位 是返回的id个数
     * 第八位 是扩展信息
     *
     */
    public static function writeHomeFeedLog($content, $type) {
        if (!isset($_SERVER['SINASRV_APPLOGS_DIR']) || !is_dir($_SERVER['SINASRV_APPLOGS_DIR'])) {
            return;
        }
        $log_dir = $_SERVER['SINASRV_APPLOGS_DIR'] . self::HOMEFEED_LOG_DIR;
        if (!is_dir($log_dir)) {
            mkdir($log_dir);
            chmod($log_dir, 0777);
        }
        $viewer   = Comm_Context::get('viewer', false);
        $cuid     = isset($viewer['id']) ? $viewer['id'] : 0;
        $log_file = $log_dir . '' . date('Ymd') . '.log';
        $log      = array(
            'time'    => time(),
            'cuid'    => $cuid,
            'ids'     => $content['ids'],
            'appid'   => 174,
            'uid'     => 0,
            'type'    => $type,
            'total'   => $content['total'],
            'extends' => $content['extends'],
        );
        @chmod($log_file, 0777);
        @error_log(self::format_log($log, "\t") . "\n", 3, $log_file);
        return;
    }

    /**
     * 记录HomeFeed曝光日志
     *
     * @param array $feedlist
     * @param array $extends
     */
    public static function addHomeFeedLog($feedlist = array(), $type = 1, $extends = array()) {
        if (empty($feedlist) || !is_array($feedlist)) {
            return;
        }
        $content = $ids = $merge_ids = array();
        foreach ($feedlist as $k => $feed) {
            if (isset($feed['id']) && !empty($feed['id'])) {
                $ids[] = $feed['id'];
            }
            if (isset($feed['merge_list']) && !empty($feed['merge_list'])) {
                foreach ($feed['merge_list'] as $mk => $mfeed) {
                    if (isset($mfeed['id']) && !empty($mfeed['id'])) {
                        $merge_ids[] = $mfeed['id'];
                    }
                }
            }
        }
        if (empty($ids)) {
            return;
        }
        self::writeHomeFeedLog(self::formatFeedData($ids), $type);
        if ($type == 1 && !empty($merge_ids)) {
            //聚合Feed
            self::writeHomeFeedLog(self::formatFeedData($merge_ids, 'true'), $type);
        }
        return;
    }

    /**
     * 格式化
     *
     * @param array $ids
     */
    public static function formatFeedData($ids, $isMergeFeed = 'false') {
        $extends                = array(
            'cip'     => 'cip=>' . Comm_Context::get_client_ip(),
            'dpool'   => 'dpool=>' . Comm_Context::get_server('SERVER_ADDR'),
            'version' => 'version=>' . '6',
        );
        $extends['isMergeFeed'] = 'isMergeFeed=>' . $isMergeFeed;
        $content['ids']         = implode(',', $ids);
        $content['total']       = count($ids);
        $content['extends']     = implode(',', $extends);
        return $content;
    }

    /**
     * 格式化日志格式
     *
     * @param array  $log
     * @param string $str
     */
    public static function format_log($log = array(), $str = '|') {
        return join($str, $log);
    }

    /**
     * 记录初筛mission相关微博cron日志,保留每个进程的日志(mission项目专用)
     *
     * @param  $type     类型
     * @param  $content  内容
     * @param  $proc_num 进程号
     *                   日志格式：类型|时间|客户ip|日志内容
     * @author zhifeng6
     */
    public static function write_filterfeeds_log($type, $content, $proc_num) {
        if (!isset($_SERVER['SINASRV_APPLOGS_DIR']) || !is_dir($_SERVER['SINASRV_APPLOGS_DIR'])) {
            return;
        }
        $log_dir = $_SERVER['SINASRV_APPLOGS_DIR'] . 'filterfeeds/';

        if (!is_dir($log_dir)) {
            mkdir($log_dir);
            chmod($log_dir, 0777);
        }

        $viewer = Comm_Context::get('viewer', false);
        $uid    = 0;
        if (false !== $viewer) {
            $uid = $viewer->id;
        }

        $log_file = $log_dir . '' . date('Ymd') . '_proc_' . $proc_num . '.log';
        $log      = array(
            'type'    => $type,
            'time'    => date("Y-m-d H:i:s"),
            'cip'     => Comm_Context::get_client_ip(),
            'dpool'   => Comm_Context::get_server('SERVER_ADDR'),
            'uid'     => $uid,
            'content' => $content,
        );
        @chmod($log_file, 0777);
        @error_log(self::format_log($log) . "\n", 3, $log_file);
        return;
    }

    /**
     * 记录Mcq流水日志（V6访谈MCQ专用）
     *
     * @param  $type      记录结果---成功：MCQ_SUCC,错误：MCQ_ERROR,异常：MCQ_EXCEPTION
     * @param  $mcq_name  队列名
     * @param  $tpcid     访谈id
     * @param  $task_type 任务类型
     * @param  $data      任务数据
     * @author zhifeng6
     */
    public static function write_mcq_log($type, $mcq_name, $tpcid = '', $task_type = '', array $data = array()) {
        if (empty($data)) {
            $data_json = '';
        } else {
            $data_json = json_encode($data);
        }

        Tool_Log_Comm::write_log($type, 'mcq_name:' . $mcq_name . '|tpcid:' . $tpcid . '|task:' . $task_type . '|data:' . $data_json);
    }

    /**
     * 记录数据统计日志（V6访谈专用）
     *
     * @param  $type       类型
     * @param  $talk_info  访谈详情
     * @param  $author     用户
     * @param  $mid        微博id
     * @param  $comment_id 评论id
     * @param  $appid      微博来源appid
     * @author zhifeng6
     */
    public static function write_data_log($type, $appid, array $talk_info, $author, $mid, $comment_id = 0, $has_rt = false) {
        $topic_id = $talk_info['topic_id'];
        $is_host  = $author == $talk_info['host_uid'] ? 'Y' : 'N';
        switch ($type) {
            case 'ask':
                $type        = 'ASK_STATS';
                $status_type = '';
                $comment_id  = '';
                break;
            case 'status_answer':
                if ($is_host == 'Y' && $talk_info['host_is_guest'] == false) {
                    $type = 'COMM_STATS';
                } else {
                    $type = 'ANS_STATS';
                }

                $status_type = $has_rt ? 'REP' : 'ORI';
                $comment_id  = '';
                break;
            case 'status_host':
                $type        = 'COMM_STATS';
                $status_type = $has_rt ? 'REP' : 'ORI';
                $comment_id  = '';
                break;
            case 'comment_answer':
                $type        = 'ANS_STATS';
                $status_type = 'CMT';
                break;
        }
        $content = "$topic_id|$author|$is_host|$mid|$status_type|$comment_id|$appid";
        Tool_Log_Comm::write_log($type, $content);
    }

    /**
     * H5页面记录uicode和actcode
     *
     * @param string $uicode      本级uicode
     * @param string $luicode     上一级ui编码
     * @param string $act_code    行为码
     * @param string $uid         当前用户uid
     * @param string $ext         扩展字段，行为码key:value
     * @param string $oid         行为目标id
     * @param string $id          本级ui页面的标记主id
     * @param string $lid         上一级主id
     * @param string $cardid      cardid
     * @param string $lcardid     lcardid
     * @param string $featurecode 功能编码
     */
    public static function action_log($uicode = '', $luicode = '', $act_code = '', $uid = '', $ext = '', $oid = '', $id = '', $lid = '', $cardid = '', $lcardid = '', $featurecode = '') {
        $logArr   = array();
        $logArr[] = date("Y-m-d H:i:s");
        $logArr[] = $uid ? $uid : 0;
        $logArr[] = $act_code;
        $logArr[] = $oid;
        $logArr[] = $uicode;
        $logArr[] = $id;
        $logArr[] = $lid;
        $logArr[] = $luicode;
        $logArr[] = $cardid;
        $logArr[] = $lcardid;
        $logArr[] = $featurecode;
        $logArr[] = Comm_Context::param('from', "") ? Comm_Context::param('from', "") : $_COOKIE['from'];
        $logArr[] = Comm_Context::param('wm', "") ? Comm_Context::param('wm', "") : $_COOKIE['wm'];
        $logArr[] = (Comm_Context::param('oldwm', "") == "") ? Comm_Context::param('wm', "") : Comm_Context::param('oldwm', "");
        $logArr[] = Comm_Context::get_client_ip();
        $logArr[] = self::logVersion();
        $logArr[] = is_array($ext) ? implode("|", $ext) : $ext;
        if (!isset($_SERVER['SINASRV_APPLOGS_DIR']) || !is_dir($_SERVER['SINASRV_APPLOGS_DIR'])) {
            return;
        }
        /** 雷达一期部署在dianshi项目里，二期服用一期的推送设定，故使用该路径记录日志  by jiebin */
        $log_dir = '/data1/www/applogs/dianshi.weibo.com/actionlog/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }

        $log_file = $log_dir . date('Ymd') . '_tvradar_act.log';
        //保持和无线端日志格式一致，确保无线统计不受影响
        @chmod($log_file, 0777);
        @error_log(implode("`", $logArr) . "\n", 3, $log_file);
        return;
    }

    /**
     * 获取版本号
     *
     * @return number
     */
    private static function logVersion() {
        $uatype = Comm_Wap_Getuainfo::getuatype();
        if ("h5" == $uatype) {
            //h5
            return 4;
        } elseif ("wap" == $uatype) {
            //wap2.0
            return 3;
        } elseif ("wml" == $uatype) {
            //wap1.0
            return 1;
        } else {
            //其它情况也返回1
            return 1;
        }
    }
}
