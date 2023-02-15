<?php

// +----------------------------------------------------------------------
// | Title: 模块
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | type: 客户端
// +----------------------------------------------------------------------
namespace Apiv2\Controller;

class ModuleController extends ApiController {
    /**
     * @name page_index
     * @title 推荐页
     * @param   string $keyword  关键词（默认为空）
     * @return [top] => 顶部推荐<br>[shop] => 商品数据(<br>[type] => 类型：renqi 人气单品,shuhui 书会 xian 闲逛区<br>[item] => 数据数组<br>)<br>----统一数据参数----<br>
                [title] => 标题 <br>[description] => 介绍 <br> [pic_url] => 图片 <br>[bind_id] => 对应的文章ID<br>[b_type] => 显示类型，对应首页设计1~4<br>[url] => 内容url（只有神秘书会有）<br>----以下是除神秘书会类型外返回的数据----<br>[collect_num] => 收藏数<br>[comment_num] => 评论数
                [zan_num] => 点赞数<br> [read_num] => 阅读数<br>[uid] => 用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[is_follow] => 是否关注 1 是 0 否<br><br>
                [xian] => 闲逛区(字段参数见Shop/lists接口)<br>
     * @remark 闲逛区为列表数据，加载下一页的数据时，通过Shop/lists获取，调用时不需要附加其他请求参数
     */
    public function page_index(){
        $this->check_account_token(false);
        
        $pics_top_w = I('pics_top_w', 0, 'intval');
        $pics_top_h = I('pics_top_h', 0, 'intval'); 
        
        $PositionModel = D('Addons://Position/Position');
        $data_top1 = $this->set_data($PositionModel->get_position('account_index_top'),1);

        if ($pics_top_w > 0 && $pics_top_h > 0) {
            foreach ($data_top1 as $tkey => &$tval) {
                $tval['pic_url'] .= "?x-oss-process=image/resize,m_fill,w_{$pics_top_w},h_{$pics_top_h},limit_0";
            }
        } 

        
        $data['top'] = $data_top1;
        
        
        
        $renqi_data = $this->set_data($PositionModel->get_position('account_index_renqi'),2);
        $shuhui_data = $this->set_data($PositionModel->get_position('account_index_shuhui'), 3);
        $goods = A('Shop')->lists(0, 1, true);
        foreach($goods['data'] as $k => $v){
            $v['b_type'] = 4;
            $v['bind_id'] = $v['id'];
            $goods['data'][$k] = $v;
        }
        $data['shop'] = array(
            array(
                'type' => 'renqi',
                'item' => $renqi_data,
            ),
            array(
                'type' => 'shuhui',
                'item' => $shuhui_data,
            ),
            array(
                'type' => 'xian',
                'item' => $goods['data'],
            ),
        );
        $this->return_data(1, $data, '', array('offset' => $goods['offset'], 'row' => $goods['row'], 'total' => $goods['total'], 'count' => $goods['count']));
    }     
     
    // 获取顶部活动
    private function get_top_act($data_tops)
    {
        

        if (empty($data_tops)) {
            $data_tops = array();
        }
        
        
        
        $config = M('act_product_config')->find();
        
        if (empty($config['is_open']) || $config['is_open'] != 1) {
            return $data_tops;
        }
        
        
        
        
        $topAct = array(
            'id' =>  "99999",
            'title' => $config['title'],
            'description' => $config['info'],    
            'url' => U('/Wap/SmAct/index'),
            'bind_type' => "content",
            'bind_id' => "99999",            
            //'pic_url' => get_domain() . '/Public/res/Wap/images/common/day_act.png',   
            'pic_url' => get_domain() . $config['toppic'],   
            'b_type' => "1",
            't_type' => "1"
        );

        
        //$data_tops[] = $topAct;
        
        array_unshift($data_tops, $topAct);
        
        return $data_tops;
        
/*
            {
                "id": "66",
                "title": "#神秘电台#孤独的人是可耻的",
                "description": "",
                "url": "",
                "bind_type": "content",
                "bind_id": "146",
                "pic_url": "https:\/\/test.imzhaike.com\/Public\/u\/p\/2018-02-23\/5a90122c27635.png?x-oss-process=image\/resize,m_fill,w_300,h_100,limit_0",
                "b_type": "1",
                "t_type": "1"
            },
*/        
    }      
     

    // 获取顶部活动
    private function get_top_wc($data_tops)
    {
        

        if (empty($data_tops)) {
            $data_tops = array();
        }
        
        
        
        $config = M('wc_config')->find();
        
        if (empty($config['is_open']) || $config['is_open'] != 1) {
            return $data_tops;
        }
        
        
        $cstr = $this->getUserAuth();
        
        
        
        $topAct = array(
            'id' =>  "99998",
            'title' => $config['title'],
            'description' => $config['info'],    
            'url' => U('/Wap/WorldCup/wc', array('cstr' => $cstr)),
            'bind_type' => "content",
            'bind_id' => "99998",            
            //'pic_url' => get_domain() . '/Public/res/Wap/images/common/day_act.png',   
            'pic_url' => get_domain() . $config['toppic'],   
            'b_type' => "1",
            't_type' => "1"
        );

        
        //$data_tops[] = $topAct;
        
        array_unshift($data_tops, $topAct);
        
        return $data_tops;
           
    }


    private function getUserAuth()
    {
        $this->check_account_token(false);
        
        $uid = $this->_uid;
        if (empty($uid)) {
            return 'null';
        }
        
        $wcauth = $this->getWcauth();
        
        M('ucenter_member')->where(array(
            'id' => $uid,
        ))->save(array(
            'wcauth' => $wcauth,
        ));
        
        
        return $wcauth;
        
    }
    
    
    private function getWcauth()
    {
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $str = str_shuffle(str_repeat($str, 5));
        
        $sub_str = substr($str, 5, 20);
        
        $time = date('YmdHis');
        $time_str = strtr($time, '0123456789', 'abcdefghij');

        $wcauth = $sub_str . $time_str;
        
        $have = M('ucenter_member')->where(array(
            'wcauth' => $wcauth
        ))->find();
        
        if (empty($have)) {
            return $wcauth;
        } else {
            return $this->getWcauth();
        }
        
    }
     
     
    public function page_index_new(){
        $this->check_account_token(false);
        
        $pics_top_w = I('pics_top_w', 0, 'intval');
        $pics_top_h = I('pics_top_h', 0, 'intval');             
        
        $PositionModel = D('Addons://Position/Position');
        $data_top1 = $this->set_data($PositionModel->get_position('account_index_top'),1);
        $data_top2 = $this->set_data($PositionModel->get_position('account_index_top'),2);

        
        foreach ($data_top1 as $key_t1 => $val_t1) {
            $data_top1[$key_t1]['t_type'] = 1;
        }
        
        foreach ($data_top2 as $key_t2 => $val_t2) {
            $data_top2[$key_t2]['t_type'] = 2;
        }        
        
        $data_tops = array_values(array_merge($data_top1, $data_top2));
        
        $listorder = array_column($data_tops, 'listorder');
        
        array_multisort($listorder, SORT_DESC, $data_tops);
        
        
        if ($pics_top_w > 0 && $pics_top_h > 0) {
            foreach ($data_tops as $tkey => &$tval) {
                $tval['pic_url'] .= "?x-oss-process=image/resize,m_fill,w_{$pics_top_w},h_{$pics_top_h},limit_0";
            }
        } 
        

        $data_tops = $this->get_top_act($data_tops);
        
        $data_tops = $this->get_top_wc($data_tops);


        
        
        $data['top'] = $data_tops;

        
        
        
        
        $renqi_data = $this->set_data($PositionModel->get_position('account_index_renqi'),2);
        $shuhui_data = $this->set_data($PositionModel->get_position('account_index_shuhui'), 3);
        $goods = A('Shop')->lists(0, 1, true);
        foreach($goods['data'] as $k => $v){
            $v['b_type'] = 4;
            $v['bind_id'] = $v['id'];
            $goods['data'][$k] = $v;
        }
        $data['shop'] = array(
            array(
                'type' => 'renqi',
                'item' => $renqi_data,
            ),
            array(
                'type' => 'shuhui',
                'item' => $shuhui_data,
            ),
            array(
                'type' => 'xian',
                'item' => $goods['data'],
            ),
        );
        $this->return_data(1, $data, '', array('offset' => $goods['offset'], 'row' => $goods['row'], 'total' => $goods['total'], 'count' => $goods['count']));
    }    
    
    
    public function shuhui_all_old()
    {
        $this->check_account_token(false);
        
        $page = I('page');
        $page = intval($page);
        $page < 1 && $page = 1;
        $size = 20;      
        
        
        $position_data = M('position')->where(array(
            'name' => 'account_index_shuhui',
        ))->find();
        
        
        if (empty($position_data)) {
            $this->return_data(1, array(), '');
        }
        
        $Model = D('Addons://Position/PositionData');
        $where = array(
            'pos_id' => $position_data['id'], 
            'status' => 1
        );
        
        $total = $Model->where($where)->count();
        
        
        $data = $Model->where($where)->field('id, title,description,cover_id,url,bind_type, bind_id')
        ->order('listorder desc, create_time asc')
        ->page($page, $size)
        ->select();
        
        
        
        !$data && $data = array();
        foreach($data as $k => $v){
            $v['pic_url'] = get_cover_url($v['cover_id']);
            unset($v['cover_id']);
            $data[$k] = $v;
        }
        
        $count = count($data);
        
      
        
        $data = $this->set_data($data, 3);
        
        $this->return_data(1, $data, '', array('offset' => $page, 'row' => $size, 'total' => $total, 'count' => $count));           
    }
    
    private function set_data($position, $type = 0){
        $aid = array();
        if(in_array($type, array(1,3))){
            $bind_type = 'content';
        }else{
            $bind_type = 'shop_article';
        }
        foreach($position as $k => $v){
            if($v['bind_type'] != $bind_type){
                unset($position[$k]);
                continue;
            }
            $aid[] = $v['bind_id'];
        }
        if(!$aid){
            return array();
        }
        if($bind_type == 'content'){
            $CModel = M('Document');
            $lists = $CModel->where(array('id' => array('in', $aid, 'status' => 1)))->field('id,title,description,cover_id')->select();
            $lists = reset_data($lists, 'id');
            $i = 0;
            foreach($position as $k => $v){
                if(!isset($lists[$v['bind_id']])){
                    unset($position[$k]);
                    continue;
                }
                $item = $lists[$v['bind_id']];
                empty($v['description']) && $v['description'] = mb_substr($item['content'], 0, 50, 'utf-8');
                if(empty($v['pic_url'])){
                    $v['pic_url'] = get_cover_url($item['cover_id']);
                }
                switch($type){
                    case 3:
                        $v['b_type'] = $i%2 == 0 ? 3 : 5;
                        break;
                    default:
                        $v['b_type'] = $type;
                }
                $i++;
                $position[$k] = $v;
            }
        }else{
            $ShopArtModel = D('Addons://Shop/ShopArticle');
            $lists = $ShopArtModel->where(array('id' => array('in', $aid), 'status' => 1))->field('id,title,cover_id,uid,content,pics,read_num,collect_num,zan_num,comment_num')->select();
            $lists = reset_data($lists, 'id');
            $fids = array();
            foreach($position as $k => $v){
                if(!isset($lists[$v['bind_id']])){
                    unset($position[$k]);
                    continue;
                }
                $item = $lists[$v['bind_id']];
                empty($v['description']) && $v['description'] = mb_substr($item['content'], 0, 50, 'utf-8');
                if(empty($v['pic_url'])){
                    $v['pic_url'] = get_cover_url($item['cover_id']);
                }
                $v['read_num'] =  $item['read_num'];
                $v['collect_num'] = $item['collect_num'];
                $v['comment_num'] = $item['comment_num'];
                $v['zan_num'] = $item['zan_num'];
                $v['uid'] = $item['uid'];
                $v['nickname'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $fids[] = $v['uid'];
                unset($v['id']);
                $v['b_type'] = $type;
                $position[$k] = $v;
            }
            $result = ($fids && $this->_uid) ? $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $fids)) : array();
            foreach($position as $k => $v){
                $v['is_follow'] = in_array($v['uid'], $result) ? 1 : 0;
                $position[$k] = $v;
            }
        }
        $position = array_values($position);
        return $position;
    }
    
    
    
    
    public function shuhui_all()
    {
        $this->check_account_token(false);
        
        $page = I('page');
        $page = intval($page);
        $page < 1 && $page = 1;
        $size = 20;      
        
        $Model = M('Document');
        
        
        $where = array(
            'status' => array('in', array(0, 1, 2)),
            'pid' => 0,
            'category_id' =>2,
        );
        
        $total = $Model->where($where)->count();
        
        $data = $Model->where($where)->field('id,title,description,cover_id')
        ->order('level desc, create_time desc')
        ->page($page, $size)        
        ->select();
    

        
        
        
        /*
        $data = $Model->where($where)->field('id, title,description,cover_id,url,bind_type, bind_id')
        ->order('level desc, create_time asc')
        ->page($page, $size)
        ->select();
        */
        
        
        !$data && $data = array();
        $i = 0;
        foreach($data as $k => $v){
            $v['pic_url'] = get_cover_url($v['cover_id']);
            unset($v['cover_id']);
            
            
            
            $v['b_type'] = $i%2 == 0 ? 3 : 5;                       
               
            $v['bind_id'] = $v['id'];
            $v['bind_type'] = 'content';
            $data[$k] = $v;
            
            $i++;
        }
        
        $count = count($data);
        
      
        
        //$data = $this->set_data($data, 3);
        
        $this->return_data(1, $data, '', array('offset' => $page, 'row' => $size, 'total' => $total, 'count' => $count));           
    }    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * @name page_follow
     * @title 关注页
     * @param  string $keyword  关键词（默认为空）
     * @param  string $page 页码
     * @return [position_member] => 推荐关注的用户，只有第一页才会返回（数组返回见下）<br>
                [uid] => 用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[is_follow] => 是否关注 1 是 0 否<br>--------<br>
                [lists] => 商品内容数组（详细参数可参见Shop/lists接口）<br><br>
     * @remark 
     */
    public function page_follow(){
        $this->check_account_token(false);
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $result = $this->_uid > 0 ? $this->uc_api('User', 'get_follow', array('uid' => $this->_uid)) : array();
        if($result){
            $goods = A('Shop')->lists($result, $page, true);
            $lists = $goods['data'];
            $total = $goods['total'];
            $count = $goods['count'];
            $size = $goods['row'];
        }else{
            $lists = array();
            $count = 0;
            $total = 0;
        }
        $position_member = array();
        if($page == 1){
            $position = D('Addons://Position/Position')->get_position('account_follow_member');
            $fids = array();
            if($position){
                foreach($position as $k => $v){
                    if($v['bind_type'] != 'member'){
                        unset($position[$k]);
                        continue;
                    }
                    $nickname = get_nickname($v['bind_id']);
                    if(!$nickname){
                        continue;
                    }
                    $position_member[] = array(
                        'uid' => $v['bind_id'],
                        'nickname' => $nickname,
                        'header_pic' => get_header_pic($v['bind_id']),
                    );
                    $fids[] = $v['bind_id'];
                }
                $result = ($fids && $this->_uid > 0) ? $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $fids)) : array();
                foreach($position_member as $k => $v){
                    $v['is_follow'] = in_array($v['uid'], $result) ? 1 : 0;
                    $position_member[$k] = $v;
                }
            }
        }
        $this->return_data(1, array('position_member' => $position_member, 'lists' => $lists), '', array('offset' => $page, 'row' => $size, 'total' => $total, 'count' => $count));
    }
}
