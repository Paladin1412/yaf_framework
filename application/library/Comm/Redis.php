<?php

class Comm_Redis {

    private $_redis;

    static private $single;

    public static    $configs             = array();
    protected static $default_config_name = 'redis_pool';

    static public function auto_configure_pool($config_name = null) {
        if ($config_name === null) {
            $config_name = self::$default_config_name;
        }
        $configs = Tool_Conf::get($config_name);
        if (!empty($configs)) {
            foreach ($configs as $type => $aliases) {
                if ($type != 'Redis') {
                    throw new Comm_Exception_Program('Wrong config type in redis_pool.ini');
                }
                foreach ($aliases as $redis_alias => $config) {
                    self::$configs[$redis_alias] = array(
                        'master' => array('host' => $_SERVER[$config['write']['host']], 'port' => $_SERVER[$config['write']['port']]),
                        'slaver' => array('host' => $_SERVER[$config['read']['host']], 'port' => $_SERVER[$config['read']['port']])
                    );
                }
            }
        }
    }

    public function __construct($redis_alias = '', $isPconnect = false, $master = true) {
        if (!$this->_redis) {
            if (empty($redis_alias) || empty(self::$configs[$redis_alias])) {
                throw new Comm_Exception_Program('Redis alias "' . $redis_alias . '" not exist');
            }
            $config       = self::$configs[$redis_alias];
            $this->_redis = new Redis();

            $conf_key = $master ? 'master' : 'slaver';

            if ($isPconnect) {
                $this->_redis->pconnect($config[$conf_key]['host'], $config[$conf_key]['port'], 0);
            } else {
                $this->_redis->connect($config[$conf_key]['host'], $config[$conf_key]['port'], 3);
            }
        }
        return $this->_redis;
    }

    static public function init($redis_alias = '', $isPconnect = false, $master = true) {
        if (!$isPconnect) {
            if (!isset(self::$single)) {
                self::$single = new self($redis_alias, $isPconnect, $master);
            }
            return self::$single;
        }
        return new self($redis_alias, $isPconnect, $master);
    }

    public function multi($pipe = Redis::MULTI) {
        return $this->_redis->multi($pipe);
    }

    public function exec() {
        return $this->_redis->exec();
    }

    public function set($key, $value) {
        $status = $this->_redis->set($key, $value);
        return $status;
    }

    public function get($key) {
        return $this->_redis->get($key);
    }

    public function incrCnt($key) {
        return $this->_redis->incr($key);
    }

    public function incrBy($key, $num) {
        return $this->_redis->incrBy($key, $num);
    }

    public function zIncr($key, $member) {
        return $this->_redis->zIncrBy($key, 1, $member);
    }

    public function zSco($key, $member) {
        return $this->_redis->zScore($key, $member);
    }

    public function exists($key) {
        return $this->_redis->exists($key);
    }

    public function dbSize() {
        return $this->_redis->dbSize();
    }

    public function del($key) {
        return $this->_redis->delete($key);
    }

    public function watch($key) {
        $this->_redis->watch($key);
    }

    public function sAdd($key, $value) {
        return $this->_redis->sAdd($key, $value);
    }

    public function sIsMember($key, $value) {
        return $this->_redis->sIsMember($key, $value);
    }

    public function zRangeByScore($key, $start, $end, $withscores) {
        return $this->_redis->zRangeByScore($key, $start, $end, $withscores);
    }

    public function zRevRangeByScore($key, $start, $end, $withscores) {
        return $this->_redis->zRevRangeByScore($key, $start, $end, $withscores);
    }

    public function zRange($key, $start, $end, $withscores) {
        return $this->_redis->zRange($key, $start, $end, $withscores);
    }

    public function zRevRange($key, $start, $end, $withscores) {
        return $this->_redis->zRevRange($key, $start, $end, $withscores);
    }

    public function zDelete($key, $value) {
        return $this->_redis->zDelete($key, $value);
    }

    public function zRemRangeByRank($key, $start, $stop) {
        return $this->_redis->zRemRangeByRank($key, $start, $stop);
    }

    public function zAdd($key, $score, $value) {
        return $this->_redis->zAdd($key, $score, $value);
    }

    /*
     * zAddMulti 
     * @author xiaoli25
     * @param  $data = array('score1', 'val1', 'score2', 'val2')
     * 
     */
    public function zAddMulti($key, $data) {
        array_unshift($data, $key);
        return call_user_func_array(array($this->_redis, 'zAdd'), $data);
    }

    public function zSize($key) {
        return $this->_redis->zCard($key);
    }

    public function keys($pattern) {
        return $this->_redis->keys($pattern);
    }

    public function expire($key, $ttl) {
        return $this->_redis->expire($key, $ttl);
    }

    public function expireAt($key, $time) {
        return $this->_redis->expireAt($key, $time);
    }

    public function zCount($key, $start, $end) {
        return $this->_redis->zCount($key, $start, $end);
    }

    public function ttl($key) {
        return $this->_redis->ttl($key);
    }

    public function hSet($key, $field, $value) {
        return $this->_redis->hSet($key, $field, $value);
    }

    public function hGet($key, $field) {
        return $this->_redis->hGet($key, $field);
    }

    public function hGetAll($key) {
        return $this->_redis->hGetAll($key);
    }

    public function hMGet($key, array $fields) {
        return $this->_redis->hMGet($key, $fields);
    }

    public function hMSet($key, array $hashKeys) {
        return $this->_redis->hMSet($key, $hashKeys);
    }

    public function hLen($key) {
        return $this->_redis->hLen($key);
    }

    public function hDel($key, $field) {
        return $this->_redis->hDel($key, $field);
    }

    public function hKeys($key) {
        return $this->_redis->hKeys($key);
    }

    public function hIncrBy($key, $field, $incr_num) {
        return $this->_redis->hIncrBy($key, $field, $incr_num);
    }

    public function __destruct() {
        $this->_redis->close();
        unset($this->_redis);
    }

    public function lPush($key, $value) {
        return $this->_redis->lPush($key, $value);
    }

    public function rPop($key) {
        return $this->_redis->rPop($key);
    }

    public function lLen($key) {
        return $this->_redis->llen($key);
    }

    public function lRange($key, $start, $end) {
        return $this->_redis->lRange($key, $start, $end);
    }

    public function lTrim($key, $start, $stop) {
        return $this->_redis->lTrim($key, $start, $stop);
    }
}
