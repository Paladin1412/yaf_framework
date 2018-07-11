<?php
/**
 */
class Aj_TestController extends AbstractController {
	public $authorize = self::MAYBE_LOGIN;
	public $check_referer = true;

	public function indexAction(){
        var_dump('aj/test');
    }

}
