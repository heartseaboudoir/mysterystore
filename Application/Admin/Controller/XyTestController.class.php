<?php

namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Controller;


class XyTestController extends Controller {

    public function index()
    {
        echo '<span style="color:red;">XyTest::index</span>';

    }
    
    public function abc()
    {
        echo 'abc';
    }
    
    
    public function session()
    {
        print_r($_SESSION);
    }
    
    
    public function func()
    {
        $test = $this->checkFunc('test_add');
        
        var_dump($test);
        
        
        
    }
    
    
    public function  checkFunc($name)
    {
        $name = trim($name);
        
        if (empty($name)) {
            return false;
        }
        
        $one = M('funcs')->where(array(
            'func' => $name
        ))->find();
        
        
        if (empty($one)) {
            return false;
        }
        
        
        $func = $one['id'];
        
        
        
        if (!UID) {
            return false;
        }
        
        $groups = M('AuthGroupAccess')->where(array('uid' => UID))->find();
        
        if (empty($groups)) {
            return false;
        }
        
        $group = $groups['group_id'];
        
        
        $auths = M('AuthGroup')->where(array('id' => $group))->find();
        
        if (empty($auths) || empty($auths['funcs'])) {
            return false;
        }
        
        
        $funcs = explode(',', $auths['funcs']);
        
        
        
        if (in_array($func, $funcs)) {
            return true;
        } else {
            return false;
        }
        
        
        
        
        
        
        
        
        
        
        
        
        
    }





    public function get()
    {
        $filename = $_GET['f'];
        if (empty($filename) || !is_file('/data/debug/' . $filename)) {
            echo 'no file';
            exit;
        }
        header('Content-Type: text/html;charset=utf-8');
        echo file_get_contents('/data/debug/' . $filename);
    }


    public function del()
    {
        $filename = $_GET['f'];
        if (empty($filename) || !is_file('/data/debug/' . $filename)) {
            echo 'no file';
            exit;
        }

        header('Content-Type: text/html;charset=utf-8');
        echo file_put_contents('/data/debug/' . $filename, '');
    }







}

