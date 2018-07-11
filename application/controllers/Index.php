<?php
class IndexController extends AbstractController {
    public $authorize   = self::MAYBE_LOGIN;
    public $init_viewer = true;

    public function indexAction() {
        var_dump(1);
//        phpinfo();
    }
}
