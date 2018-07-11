<?php

class Comm_Db_PdoMysql implements Comm_Db_Interface {
    static private $mode_auto  = 0;
    static private $mode_read  = 1;
    static private $mode_write = 2;

    protected $attributes   = array();
    protected $read_config  = array();
    protected $write_config = array();
    protected $read_inst;
    protected $write_inst;
    protected $last_inst;
    protected $mode         = 0;

    protected $alias = 'Undefined';

    //连接超时后是否重连
    protected $is_reconnect = true;

    static protected $config_keys = array('host', 'port', 'name', 'user', 'pass', '*attr');

    /* (non-PHPdoc)
     * @see Comm_Db_Interface::configure()
     */
    public function configure($alias, $config) {
        $this->alias  = $alias;
        $read_configs = $write_configs = array();
        foreach ($config as $k => $v) {
            self::check_config_format($v);
            foreach ($v as $key => $var) {
                $v[$key] = $_SERVER[$var];
            }

            if (strpos($k, 'read') !== false) {
                $read_configs[] = $v;
            }
            if (strpos($k, 'write') !== false) {
                $write_configs[] = $v;
            }
        }

        $read_configs AND $this->read_config = $read_configs[array_rand($read_configs)];
        $write_configs AND $this->write_config = $write_configs[array_rand($write_configs)];
        if (!$this->read_config && !$this->write_config) {
            throw new Comm_Exception_Program('Must define at least one db for "' . $this->alias . '"!');
        }
    }

    /**
     * 强制使用读库
     *
     * @return Comm_Db_PdoMysql
     */
    public function set_read() {
        $this->mode = 1;
        return $this;
    }

    /**
     * 强制使用写库
     *
     * @return Comm_Db_PdoMysql
     */
    public function set_write() {
        $this->mode = 2;
        return $this;
    }

    /**
     * 根据sql语句自行判断
     *
     * @return Comm_Db_PdoMysql
     */
    public function set_auto() {
        $this->mode = 0;
        return $this;
    }

    /**
     * 执行一个sql语句并返回影响行数。
     *
     * 如果在insert或者replace语句后需要获取 last insert id 请使用last_insert_id()方法
     *
     * @param string $sql sql语句。不能为select语句
     * @param array  $data
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     * @return integer
     */
    public function exec($sql, array $data = null) {
        $verb = self::extract_sql_verb($sql);
        if ($verb === 'select') {
            throw new Comm_Exception_Program('Can not execute a select sql');
        }

        $statement = $this->execute_sql($sql, $data);

        return $statement->rowCount();
    }

    /**
     * 执行提供的select语句并返回结果集。
     *
     * @param string $sql sql语句。只能为select语句
     * @param array  $data
     * @param bool   $fetch_index
     * @return array
     *
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     */
    public function fetch_all($sql, array $data = null, $fetch_index = false) {
        $verb = self::extract_sql_verb($sql);
        if ($verb !== 'select') {
            throw new Comm_Exception_Program('Can not fetch on a non-select sql');
        }

        $statement = $this->execute_sql($sql, $data);

        return $statement->fetchAll($fetch_index ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
    }

    /**
     * 执行提供的select语句并返回结果集(一行数据)。
     *
     * @param string $sql sql语句。只能为select语句
     * @param array  $data
     * @param bool   $fetch_index
     * @return array
     *
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     */
    public function fetch_row($sql, array $data = null, $fetch_index = false) {
        $verb = self::extract_sql_verb($sql);
        if ($verb !== 'select') {
            throw new Comm_Exception_Program('Can not fetch on a non-select sql');
        }

        $statement = $this->execute_sql($sql, $data);

        return $statement->fetch($fetch_index ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
    }

    /**
     * PDO同名方法封装
     *
     * @see PDO::prepare()
     * @param string $sql
     * @return PDOStatement
     *
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     */
    public function prepare($sql) {
        $pdo  = $this->get_inst($this->detect_sql_type($sql));
        $args = func_get_args();
        return call_user_func_array(array($pdo, 'prepare'), $args);
    }

    /**
     * PDO同名方法封装
     *
     * @param string $sql
     * @return PDOStatement
     *
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     */
    public function query($sql) {
        $pdo  = $this->get_inst($this->detect_sql_type($sql));
        $args = func_get_args();
        return call_user_func_array(array($pdo, 'query'), $args);
    }

    /**
     * PDO::getAttribute() 方法的重命名封装版
     *
     * @param int $attribute
     * @return mixed
     */
    public function get_attribute($attribute) {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
    }

    /**
     * PDO::setAttribute() 方法的重命名封装版
     *
     * @param int   $attribute
     * @param mixed $value
     */
    public function set_attribute($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    public function __call($func, $args) {
        //Convert do_something() style to dosomething()
        $func_lower = str_replace('_', '', strtolower($func));
        //Because of class method name is case insensitive in PHP, so, this simple
        //    process is enough and fast.

        $mode = self::$mode_auto;
        if (in_array($func_lower, array('lastinsertid', 'begintransaction', 'intransaction', 'commit', 'rollback'))) {
            $mode = self::$mode_write;
        }
        $rst = call_user_func_array(array($this->get_inst($mode), $func), $args);
        if (in_array($func_lower, array('begintransaction')) && $rst === false) {
            $this->write_inst = null;
            $this->read_inst  = null;
            $this->last_inst  = null;
            return call_user_func_array(array($this->get_inst($mode), $func), $args);
        }
        return $rst;
    }

    /**
     * 执行一个sql并返回PDOStatement对象和执行结果。
     *
     * @param string $sql
     * @param array  $data
     * @param bool   $fetch_index
     * @return mixed
     *
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     */
    protected function execute_sql($sql, array $data = null, $fetch_index = false) {
        $statement = $this->prepare($sql);
        /* @var $statement PDOStatement */
        if ($data) {
            $result = $statement->execute($data);
        } else {
            $result = $statement->execute();
        }
        if (!$result) {
            $error = $statement->errorInfo();
            list ($code, $sql_code, $info) = $error;
            if ($sql_code == 2006) { //MySQL server has gone away
                $this->last_inst = null;
                if ($this->detect_sql_type($sql) === self::$mode_write) {
                    $this->write_inst = null;
                } else {
                    $this->read_inst = null;
                }
                if ($this->is_reconnect) {
                    $this->is_reconnect = false;
                    return $this->execute_sql($sql, $data, $fetch_index);
                }
            }

            if (is_array($error)) {
                $error = implode(',', $error);
            } else {
                $error = strval($error);
            }
            throw new Comm_Db_PdoMysqlException($error);
        }

        $this->is_reconnect = true;
        return $statement;
    }

    /**
     * 根据指定的类型获取pdo实例。
     *
     * @param int $mode
     * @return PDO | mixed
     *
     * @throws Comm_Exception_Program
     * @throws Comm_Db_PdoMysqlException
     */
    protected function get_inst($mode) {
        if ($mode === self::$mode_auto) {
            if (null === $this->last_inst) {
                //default read, unless set write mode
                $this->last_inst = $this->get_inst(
                    $this->mode === self::$mode_write
                        ? self::$mode_write
                        : self::$mode_read
                );
            }
            return $this->last_inst;
        }
        if ($mode === self::$mode_read) {
            if (null === $this->read_inst) {
                if (!$this->read_config) {
                    return $this->get_inst(self::$mode_write);
                }
                $this->read_inst = $this->get_pdo($this->read_config);
            }
            $this->last_inst = $this->read_inst;
            return $this->read_inst;
        }
        if ($mode === self::$mode_write) {
            if (null === $this->write_inst) {
                if (!$this->write_config) {
                    throw new Comm_Exception_Program('Writable db must be defined');
                }
                $this->write_inst = $this->get_pdo($this->write_config);
            }
            $this->last_inst = $this->write_inst;
            return $this->write_inst;
        }

        return null;
    }

    protected function get_pdo($config) {
        try {
            $inst = new PDO("mysql:dbname={$config['name']};host={$config['host']};port={$config['port']};charset=UTF8", $config['user'], $config['pass']);
        } catch (Exception $ex) {
            throw new Comm_Db_PdoMysqlException($ex->getMessage());
        }
        if (!empty($config['attr']) && is_array($config['attr'])) {
            foreach ($config['attr'] as $k => $v) {
                $inst->setAttribute($k, $v);
            }
        }

        return $inst;
    }

    /**
     * 提取sql语句的动词
     *
     * @param string $sql
     * @return string 动词
     */
    static protected function extract_sql_verb($sql) {
        $sql_components = explode(' ', ltrim($sql), 2);
        $verb           = strtolower($sql_components[0]);
        return $verb;
    }

    /**
     * 检测sql所需的数据库类型
     *
     * @param string $sql
     * @return integer
     */
    static protected function detect_sql_type($sql) {
        if (self::extract_sql_verb($sql) === 'select') {
            return self::$mode_auto;
        }
        return self::$mode_write;
    }

    /**
     * 检查配置文件格式是否合格
     *
     * @param array $config
     * @throws Comm_Exception_Program
     */
    protected function check_config_format(array $config) {
        $valid_keys = array_fill_keys(self::$config_keys, 0);
        foreach ($config as $k => $v) {
            //检查是否是必选或者可选参数。可选参数以*号开头
            if (!isset($valid_keys[$k]) && !isset($valid_keys["*$k"])) {
                throw new Comm_Exception_Program('Unused PdoMysql "' . $this->alias . '" config "' . $k . '"');
            }
            unset($valid_keys[$k]);
        }

        if ($valid_keys) {
            $keys = array_keys($valid_keys);
            //忽略掉可选参数。可选参数以*号开头
            do {
                $key = array_pop($keys);
            } while ($key{0} === '*');
            if ($key && $key{0} !== '*') {
                throw new Comm_Exception_Program('Missing PdoMysql "' . $this->alias . '" config value "' . $key . '"');
            }
        }
    }
} 
