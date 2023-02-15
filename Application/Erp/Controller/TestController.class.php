<?php

namespace Erp\Controller;
use Think\Controller;


class TestController extends AdminController {


    
    public function index()
    {

        $data = $this->gv();
        $this->response(self::CODE_OK, $data);
    }
    
    



}

