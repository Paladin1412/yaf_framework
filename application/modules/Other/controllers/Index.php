<?php

class IndexController extends AbstractController {
    public $authorize = self::MAYBE_LOGIN;
    public $init_viewer = false;
    
    public function indexAction() {
        var_dump('other_index');
    }
}