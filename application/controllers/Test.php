<?php
class TestController extends AbstractController {
    public $authorize   = self::MAYBE_LOGIN;
    public $init_viewer = true;

    public function indexAction() {
        var_dump('test');
//        phpinfo();
    }
}
