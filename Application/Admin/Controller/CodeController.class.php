<?php
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

/**
 * 功能：内容管理
 * 
 */
class CodeController extends AdminController {
    
    /**
     * 内容列表
     */
    public function index()
    {
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $where = array();
        $list   = $this->lists(M('code_content'), $where, 'id desc');
        
        //$list   = $this->lists(D('Addons://CashCoupon/CashCouponUser'));
        

        $this->assign('list', $list);        
        
        
        
        $this->meta_title = '内容列表';
        $this->display();
    }
    
    /** 
     * 添加内容
     */
    public function add()
    {
        
        if (IS_POST) {
            
            
            $data = array();
            
            
            // 判断内容类型
            $title = I('title', '');
            
            if (empty($title)) {
                $this->error('标题不能为空');
            } else {
                $data['title'] = $title;
            }
            
            
            $info = I('info', '');
            
            if (empty($info)) {
                $this->error('说明不能为空');
            } else {
                $data['info'] = $info;
            }
            
            $author = I('author', '');
            
            if (empty($author)) {
                $this->error('作者不能为空');
            } else {
                $data['author'] = $author;
            }            
            
            
            $status = I('status', 0);
            if (empty($status)) {
                $status = 0;
            } else {
                $status = 1;
            }
            
            $data['status'] = $status;
            
            // 文档类型
            $type = I('type', 0);
            
            if (!in_array($type, array(0, 1, 2))) {
                $type = 0;
            }
            
            $data['type'] = $type;
            
            // 图文
            if ($type == 0) {
                $imgtxt = I('imgtxt', '');
                if (empty($imgtxt)) {
                    $this->error('图文内容不能为空');
                } else {
                    $data['content'] = $imgtxt;
                }
                
            // 音频
            } else if ($type == 1) {
                $cover = I('cover', '');
                $music = I('music', '');
                
                if (empty($cover)) {
                    $this->error('封面不能为空');
                }
                
                if (empty($music)) {
                    $this->error('音频不能为空');
                }
                
                $content = array(
                    'cover' => $cover,
                    'music' => $music,
                );
                
                $content = json_encode($content);
                
                $data['content'] = $content;
            
            // 视频
            } else {
                $content = I('content', '');
                if (empty($content)) {
                    $this->error('视频链接不能为空');
                } else {
                    $data['content'] = $content;
                }                
            }
            
            $time = time();
            $data['update_time'] = $time;
            
            $id = I('id', 0, 'intval');
            
            if (empty($id)) {
                $data['create_time'] = $time;
                // 数据入库
                $res = M('code_content')->add($data);
            } else {
                $res = M('code_content')->where(array(
                    'id' => $id,
                ))->save($data);
            }
            
            if (empty($res)) {
                $this->error('处理失败');
            } else {
                $this->success('处理成功', U("Code/index"));
            }
            
        } else {
            
            $id = I('id', 0);
            
            
            $data = array(
                'id' => 0,
                'type' => 0,
                'status' => 0,
                'title' => '',
                'info' => '',
                'content' => '',
            );
            
            if (empty($id)) {
                // 文档类型
                $type = I('type', 0);
            } else {
                
                
                $one = M('code_content')->where(array(
                    'id' => $id,
                ))->find();
                
                if (empty($one)) {
                    $this->error('内容不存在');
                } else {
                    $data = $one;
                }
                
                
                $type = $one['type'];
             
            }
            
            
            $this->assign('data', $data);
            
            if (empty($data['id'])) {
                $new = true;
            } else {
                $now = false;
            }

            $this->assign('new', $new);            
            
            // 图文
            if ($type == 0) {
                $this->display();   
            
            // 音频
            } else if ($type == 1) {
                
                $content = json_decode($one['content'], true);
                
                
                if (!empty($content['cover'])) {
                    $cover = $content['cover'];
                } else {
                    $cover = '';
                }
                
                if (!empty($content['music'])) {
                    $music = $content['music'];
                } else {
                    $music = '';
                }                
                
                $this->assign('cover', $cover);
                $this->assign('music', $music);
                
                
                
                
                $this->display('add_music');
            // 视频
            } else {
                $this->display('add_movie');
            }            

        }
        
    }
    
    /**
     * 删除内容
     */
    public function del()
    {
        
        if (IS_GET) {
            $id = I('id', 0, 'intval');        
            $res = M('code_content')->where(array(
                'id' => $id
            ))->delete();
            
            
            if (empty($res)) {
                $this->error('操作失败');
            } else {
                $this->success('操作成功');
            } 
        } else {
            $this->error('非法操作');
        }        
    }
    
    /**
     * 修改内容
     */
    public function modify()
    {
        
    }
    
    
    
    
}
