<?php

class Comm_Db {
    static protected $default_config_name = 'db_pool';
    static protected $dbs                 = array();

    static public function auto_configure_pool($config_name = null) {
        if ($config_name === null) {
            $config_name = self::$default_config_name;
        }
        $configs = Tool_Conf::get($config_name);

        foreach ($configs as $type => $aliases) {
            $class = "Comm_Db_$type";
            if (class_exists($class) && in_array('Comm_Db_Interface', class_implements($class))) {
            } elseif (class_exists($type) && in_array('Comm_Db_Interface', class_implements($type))) {
                $class = $type;
            } else {
                throw new Comm_Exception_Program("Db type \"$type\" must implements Comm_Db_Interface");
            }

            foreach ($aliases as $alias => $config) {
                self::$dbs[$alias] = new $class();
                self::$dbs[$alias]->configure($alias, $config);
            }
        }
        return;
    }

    /**
     *
     * @param  $db_alias
     * @return Comm_Db_Interface
     * @throws Comm_Exception_Program
     */
    static public function pool($db_alias) {
        if (empty(self::$dbs[$db_alias])) {
            throw new Comm_Exception_Program('Db alias "' . $db_alias . '" not exist');
        }

        return self::$dbs[$db_alias];
    }

    static public function clear_all() {
        $ret       = self::$dbs;
        self::$dbs = array();
        return $ret;
    }
}