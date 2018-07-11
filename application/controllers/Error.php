<?php

class ErrorController extends Yaf_Controller_Abstract {
    public function errorAction($exception) {
        var_dump($exception);
    }
}




