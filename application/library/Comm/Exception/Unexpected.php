<?php
/**
 * 非预期错误，和Comm_Exception_Program相比，抛出该异常意味着发生了程序不可控制的错误，涵盖
 * 	用户输入错误的参数
 *  依赖的服务出现了异常
 *  etc...
 *
 * @package lib
 * @subpackage exception
 * @copyright copyright(2011) weibo.com all rights reserved
 * @author weibo.com php team
 */
 
class Comm_Exception_Unexpected extends Yaf_Exception {
    protected $type_code = '200';
    
    public function action() {
        // when unexpected throwed, you should do something
        throw $this;
    }
}
