<?php

namespace Wap\Controller;

use Think\Controller;

class CodeController extends Controller {

    public function show_test()
    {
        
        echo '<span style="color:red;font-size:30px;">' . mt_rand(1, 99999) . '</span>';
    }
    
    
    public function show()
    {
        
        /*
        $id = $this->getId();
        
        if (empty($id)) {
            echo "~_~(404)啥也没有哦";
            exit;
        }
        */
        $id = I('id', 0,  'intval');
        
        $data = $this->getContent($id);
        if (empty($data)) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<span style="color:red; font-size:20px;">~_~(404)啥也没有哦';
            exit;
        } else {
            $this->assign('data', $data);
            
            
            if ($data['type'] == 0) {
                $this->display();
            } else if ($data['type'] == 1) {
                $info = json_decode($data['content'], true);
                
                if (!empty($info['cover'])) {
                    $cover = $info['cover'];
                } else {
                    $cover = '';
                }
                
                if (!empty($info['music'])) {
                    $music = $info['music'];
                } else {
                    $music = '';
                }
                
                $this->assign('cover', $cover);
                $this->assign('music', $music);
                $this->display('show_music');
            } else if ($data['type'] == 2) {
                header("Location: {$data['content']}");
                // $this->display('show_movie');
            } else {
                header('Content-Type: text/html; charset=utf-8');
                echo '<span style="color:red; font-size:20px;">~_~(404)啥也没有哦';
                exit;                
            }
            
            
        }

        
        
    }    
    
    
    /**
     * 获取内容ID
     */
    private function getId()
    {
        // 获取内容数目
        $count = M('code_content')->where(array(
            'status' => 1,
        ))->count();
        
        if (empty($count) || $count <= 0) {
            return 0;
        } else {
            $num = mt_rand(1, $count);
        }
        
        
    }
    
    /**
     * 获取具体内容
     */
    private function getContent($id = 0)
    {
        

        
        $where = array(
            'status' => 1,
        );
        
        
        if (!empty($id)) {
            $where['id'] = $id;
        }
        
        // 获取内容数目
        $count = M('code_content')->where($where)->count();
        
        if (empty($count) || $count < 1) {
            return array();
        }
        
        
        $num = mt_rand(0, $count-1);
    
        
        $one = M('code_content')->where($where)->limit("{$num},1")->select();
        
        
        // 获取内容失败
        if (empty($one[0])) {
            return array();
        }
        
        return $one[0];
    }
    
    
    
    
    
    

}
