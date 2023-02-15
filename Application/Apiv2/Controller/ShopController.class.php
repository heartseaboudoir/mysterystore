<?php
// +----------------------------------------------------------------------
// | Title: 店铺/商品
// +----------------------------------------------------------------------
// | Author: 小马
// +----------------------------------------------------------------------
// | Type: 客户端
// +----------------------------------------------------------------------
namespace Apiv2\Controller;

class ShopController extends ApiController {
    /**
     * @name tags
     * @title 所有标签
     * @remark 返回为数组形式
     */
    public function tags(){
        $result = array();
        $data = M('Tags')->field('tag')->order('listorder desc, id asc')->select();
        if($data){
            foreach($data as $v){
                $result[] = $v['tag'];
            }
        }
        $this->return_data(1, $result);
    }
    /**
     * @name lists
     * @title 列表
     * @param   string $keyword  关键词（默认为空）
     * @param   string $tag  标签（默认为空）
     * @param   string $uid  用户ID（默认全部）
     * @param   string $page  页码 默认为1
     * @return [id] => 文章ID<br>[bind_id] => 文章ID<br> [title] => 标题 <br>[description] => 介绍 <br> [pic_url] => 图片 <br>[collect_num] => 收藏数<br>[comment_num] => 评论数
                [zan_num] => 点赞数<br> [read_num] => 阅读数<br>[create_time] => 创建时间<br>[uid] => 用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[is_follow] => 是否关注 1 是 0 否
     * @remark 
     */
    public function lists($uid = '', $page = 1, $is_return = false){
        $this->check_account_token(false);
        $tag = I('tag', '');
        $keyword = I('keyword', '', 'trim');
        if($is_return){
            $uid = is_array($uid) ? implode(',', $uid) : $uid;
        }else{
            $uid = I('uid', 0, 'intval');
        }
        $page = intval($page);
        $page < 1 && $page = 1;
        $size = 20;
        $where = array();
        $field = 'a.id, a.title,a.cover_id,a.content,create_time,a.read_num,a.zan_num,a.collect_num,a.comment_num,a.uid,a.create_time';
        $join = '';
        if($tag){
            $where['b.tag'] = $tag;
            $join = '__SHOP_ARTICLE_TAGS__ as b ON a.id = b.aid';
        }
        $keyword && $where['a.title'] = array('like', '%'.$keyword.'%');
        $uid && ((is_numeric($uid) && $uid > 0) ? $where['a.uid'] = $uid : $where['a.uid'] = array('in', $uid));
        $where['a.status'] = 1;
        $where['a.is_shelf'] = 1;
        $Model = D('Addons://Shop/ShopArticle');
        $lists = $Model->alias('a')->join($join)->where($where)->order('a.create_time desc')->page($page, $size)->field($field)->select();
        if($lists){
            $sid_arr = reset_data_field($lists, 'id', 'id');
            $fids = array();
            foreach($lists as $k => $v){
                $v['bind_id'] = $v['id']; 
                $v['description'] = mb_substr($v['content'], 0, 50, 'utf-8');
                $v['nickname'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $v['pic_url'] = get_cover_url($v['cover_id']);
                $fids[] = $v['uid'];
                unset($v['pic'],$v['content'],$v['cover_id']);
                $lists[$k] = $v;
            }
            $result = $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $fids));
            foreach($lists as $k => $v){
                $v['is_follow'] = in_array($v['uid'], $result) ? 1 : 0;
                $lists[$k] = $v;
            }
        }else{
            $lists = array();
        }
        $count = count($lists);
        $total = $Model->alias('a')->join($join)->where($where)->count();
        if($is_return){
            return array('data' => $lists, 'offset' => $page, 'row' => $size, 'total' => $total, 'count' => $count);
        }else{
            $this->set_field = array('uid' => 'I', 'read_num' => 'I', 'collect_num' => 'I', 'comment_num' => 'I', 'uid' => 'I');
            $this->return_data(1, $lists, '', array('offset' => $page, 'row' => $size, 'total' => $total, 'count' => $count));
        }
    }
    /**
     * @name info
     * @title 店铺文章商品
     * @param string    $aid        文章id
     * @return [title] => 标题 <br>[content] => 内容 <br> [pic_url] => 文章封面图 <br> [pics_data] => 图片集合 <br>[collect_num] => 收藏数<br>[comment_num] => 评论数
                [zan_num] => 点赞数<br> [read_num] => 阅读数<br> [create_time] => 创建时间<br>[uid] => 用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[is_follow] => 是否关注 1 是 0 否<br>
                [has_goods] => 是否有商品 1 是 0 否 <br>[goods_id] => 商品ID<br> [goods_title] => 商品名<br>[goods_pic] => 商品图片<br>[price] => 价格<br>[express_money] => 运费<br>[num] => 库存<br>
                [zan_data] => 点赞的用户集合<br> [comment_data] => 留言集合，返回字段见留言列表接口<br>[is_zan] => 是否已赞 1 是 0 否 <br>[is_collect] => 是否已收藏 1 是 0 否<br>
                [im_userid] => im用户名 <br>[status] => 状态：-1 已删除 0 待审核 1 已审核 2 未通过<br>[is_shelf] => 上架状态： 0 未上架  1 已上架 2 已下架<br>
                [share] => 页面分享(<br>[title] =>标题<br>[desc] => 描述<br>[pic_url] => 图片地址<br>[url] => 分享地址<br>) <br>
     */
    public function info(){
        $this->_check_param('aid');
        $this->check_account_token(false);
        
        // 九宫格
        $pics_data_w = I('pics_data_w', 0, 'intval');
        $pics_data_h = I('pics_data_h', 0, 'intval');
        
        
        // 顶部图片
        $pics_pic_w = I('pics_pic_w', 0, 'intval');
        $pics_pic_h = I('pics_pic_h', 0, 'intval');

        
        // 商品图片
        $pics_goods_w = I('pics_goods_w', 0, 'intval');
        $pics_goods_h = I('pics_goods_h', 0, 'intval');        
        
        $aid = I('aid', 0, 'intval');
        if(!($aid > 0)){
            $this->return_data(0, '', '请选择文章');
        }
        $data = D('Addons://Shop/ShopArticle')->where(array('id' => $aid))->field('id,title,cover_id,uid,content,pics,read_num,zan_num,collect_num,comment_num,status,is_shelf,create_time')->find();
        if(!$data){
            $this->return_data(0, '', '文章不存在');
        }
        
        // 九宫格图片
        $pics_data = array();
        if($data['pics']){
            $data['pics'] = explode(',', $data['pics']);
            foreach($data['pics'] as $v){
                $v = intval($v);
                if($v > 0){
                    $_pic = get_cover_url($v);
                    $_pic && $pics_data[] = $_pic;
                }
            }
        }
        $data['pic_url'] = get_cover_url($data['cover_id']);
        
        if ($pics_pic_w > 0 && $pics_pic_h > 0) {
            $data['pic_url'] .= "?x-oss-process=image/resize,m_fill,w_{$pics_pic_w},h_{$pics_pic_h},limit_0";
        }
        
        $data['pics_data'] = $pics_data;
        
        $pics_data_thum = $pics_data;
        
        if ($pics_data_w > 0 && $pics_data_h > 0) {
            foreach ($pics_data_thum as $tkey => &$tval) {
                $tval .= "?x-oss-process=image/resize,m_fill,w_{$pics_data_w},h_{$pics_data_h},limit_0";
            }
        }        
        $data['pics_data_thum'] = $pics_data_thum;
        $data['nickname'] = get_nickname($data['uid']);
        $data['header_pic'] = get_header_pic($data['uid']);
        $result = $this->_uid > 0 ? $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $data['uid'])) : array();
        $data['is_follow'] =  in_array($data['uid'], $result) ? 1 : 0;
        if($data['zan_num'] > 0){
            $zan_data = M('ShopMemberAct')->where(array('aid' => $aid, 'type' => 1))->field('uid')->limit(4)->order('id desc')->select();
            foreach($zan_data as $k => $v){
                $v['nickname'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $zan_data[$k] = $v;
            }
        }else{
            $zan_data = array();
        }
        
        // add by xuyuan 2017/8/22
        if (count($zan_data) < 4) {
            $data['zan_num'] = count($zan_data);
        } elseif ($data['zan_num'] < 4) {
            $data['zan_num'] = 4;
        }
        
        $data['zan_data'] = $zan_data;
        $data['is_zan']  = ($this->_uid > 0 && M('ShopMemberAct')->where(array('aid' => $aid, 'type' => 1, 'uid' => $this->_uid))->find()) ? 1 : 0;
        $data['is_collect']  = ($this->_uid > 0 && M('ShopMemberAct')->where(array('aid' => $aid, 'type' => 2, 'uid' => $this->_uid))->find()) ? 1 : 0;
        if($data['comment_num'] > 0){
            //$comment_lists = $this->_comment_lists($aid, 1, 4, false);
            //$comment = $comment_lists['data'];
            
            $comment = $this->getComment($aid);
        }else{
            $comment = array();
        }
        
        // add by xuyuan 2017/8/22 

        $data['comment_num'] = count($comment);


        /*
        if (count($comment) < 4) {
            $data['comment_num'] = count($comment);
        } elseif ($data['comment_num'] < 4) {
            $data['comment_num'] = 4;
        }
        */


        
        $data['comment_data'] = $comment;
        $goods = M('ShopGoods')->where(array('aid' => $aid))->field('id,title,pic,num,sell_num,price,express_money')->find();
        $data['has_goods'] = $goods ? 1 : 0;
        $data['goods_id'] = $data['has_goods'] ? $goods['id'] : 0;
        $data['goods_title'] = $data['has_goods'] ? $goods['title'] : '';
        $data['goods_pic'] = $data['has_goods'] ? get_cover_url($goods['pic']) : '';
        if (!empty($data['goods_pic']) && $pics_goods_w > 0 && $pics_goods_h > 0) {
            $data['goods_pic'] .= "?x-oss-process=image/resize,m_fill,w_{$pics_goods_w},h_{$pics_goods_h},limit_0";
        }
        
        
        $data['num'] = $data['has_goods'] ? $goods['num'] : 0;
        $data['sell_num'] = $data['has_goods'] ? $goods['sell_num'] : 0;
        $data['price'] = $data['has_goods'] ? $goods['price'] : 0;
        $data['express_money'] = $data['has_goods'] ? $goods['express_money'] : '';
        
        $im_info = D('Common/Member')->get_im($data['uid']);
        $data['im_userid'] = $im_info['userid'];
        $share_config = mb_strlen($data['content'], 'utf-8') > 50 ? msubstr($data['content'], 0, 50, 'utf-8', true) : $data['content'];
        $data['share'] = array(
            'title' => $data['title'],
            'desc' => $share_config,
            'pic_url' => $data['pic_url'],
            'url' => U('wap/shop/article', array('id' => $data['id'])),
        );
        unset($data['pics'],$data['cover_id']);
        // 只有上架中的才会添加阅读量
        if($data['status'] == 1 && $data['is_shelf'] == 1){
            D('Addons://Shop/ShopArticle')->where(array('id' => $aid, 'status' => 1, 'is_shelf' => 1))->setInc('read_num');
        }
        $this->return_data(1, $data);
    }
    
    
    private function getComment($aid)
    {
        $datas = M('ShopComment')->where(array(
            'aid' => $aid,
        ))->limit(100)->select();
        
        foreach ($datas as $k=> &$v) {
            $v['nickname'] = get_nickname($v['uid']);
            $v['header_pic'] = get_header_pic($v['uid']);
            $v['p_nickname'] = '';
            $v['p_header_pic'] = '';
            $v['child'] = array();
        }
        
        

        
        $comment = array();
        foreach ($datas as $k2=> $v2) {
            if ($v2['pid'] == 0) {
                $comment[$v2['id']] = $v2;
            }
        }
        
        /*
        if ($_POST['xy']) {
            $this->return_data(1, $comment);
            exit;
        }
        */

        foreach ($datas as $k3=> $v3) {
            if ($v3['pid'] != 0 && !empty($comment[$v3['pid']])) {
                
                $v3['p_nickname'] = $comment[$v3['pid']]['nickname'];
                $comment[$v3['pid']]['child'][] = $v3;
            }
        }
      
        return array_values($comment);
        //return array_slice($comment, 0, 4);
        
        
        
    }
    
    
    /**
     * @name act_user_lists
     * @title 赞过的人
     * @param string    $aid        文章id
     * @return [uid] => 用户ID <br> [nickname] => 昵称 <br> [header_pic] => 头像 <br> [is_follow] => 是否关注：0 否 1 是
     */
    public function act_user_lists(){
        $this->check_account_token(false);
        $this->_check_param('aid');
        $aid = I('aid', 0, 'intval');
        $type = 1;
        if(!($aid > 0)){
            $this->return_data(0);
        }
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 50;
        $Model = M('ShopMemberAct');
        $where = array(
            'aid' => $aid,
            'type' => $type,
        );
        $lists = $Model->where($where)->page($page, $row)->select();
        if($lists){
            $uids = reset_data_field($lists, 'id', 'uid');
            $result = $this->_uid > 0 ? $this->uc_api('User', 'check_follow', array('uid' => $this->_uid, 'check_uids' => $uids)) : array();
            foreach($lists as $k => $v){
                $v['nickname'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $v['is_follow'] = in_array($v['uid'], $result) ? 1 : 0;
                $lists[$k] = $v;
            }
        }else{
            $lists = array();
        }
        $total = $Model->where($where)->count();
        $count = count($lists);
        $this->return_data(1, $lists, '', array('offset' => $page, 'row' => $row, 'total' => $total, 'count' => $count));
    }
    /**
     * @name zan_act
     * @title 添加/取消赞
     * @param string $aid 文章ID
     * @return [act] => 操作：1 添加 2 取消 <br>[zan_num] => 点赞数
     */
    public function zan_act(){
        $this->check_account_token();
        $this->_check_param('aid');
        $aid = I('aid', 0, 'intval');
        if(!($aid > 0)){
            $this->return_data(0);
        }
        $result = D('Addons://Shop/ShopArticle')->do_act($this->_uid, $aid, 1);
        if($result){
            $this->return_data(1, array('act' => $result['act'], 'zan_num' => $result['num']), '操作成功');
        }else{
            $this->return_data(0, '', '操作失败');
        }
    }
    /**
     * @name collect_act
     * @title 添加/取消收藏
     * @param string $aid 文章ID
     * @return [act] => 操作：1 添加 2 取消 <br>[collect_num] => 收藏数
     */
    public function collect_act(){
        $this->check_account_token();
        $this->_check_param('aid');
        $aid = I('aid', 0, 'intval');
        if(!($aid > 0)){
            $this->return_data(0);
        }
        $result = D('Addons://Shop/ShopArticle')->do_act($this->_uid, $aid, 2);
        if($result){
            $this->return_data(1, array('act' => $result['act'], 'collect_num' => $result['num']), '操作成功');
        }else{
            $this->return_data(0, '', '操作失败');
        }
    }
    /**
     * @name collect_lists
     * @title 收藏列表
     * @param string $page  页码（默认为1）
     * @return [bind_id] => 文章ID<br>[title] =>标题<br>[pic_url]=>封面图<br>[content]=>描述<br>[zan_num]=>点赞数 <br>[collect_num] => 收藏数 <br>[comment_num] => 评论数
     */
    public function collect_lists(){
        $this->check_account_token();
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $where = array();
        $where['uid'] = $this->_uid;
        $where['type'] = 2;
        
        $result = $this->_lists(M('ShopMemberAct'), $where, '', $page, $row, 'aid,title,cover_id');
        if($result['data']){
            $aid = reset_data_field($result['data'], 'aid', 'aid');
            $adata = reset_data(M('ShopArticle')->where(array('id' => array('in', $aid)))->field('id,title,content,comment_num,zan_num,collect_num')->select(), 'id');
            foreach($result['data'] as $k => $v){
                if(empty($adata[$v['aid']])){
                    unset($result['data'][$k]);
                    continue; 
                }
                $item = $adata[$v['aid']];
                $v['pic_url'] = get_cover_url($v['cover_id']);
                $v['bind_id'] = $v['aid'];
                $v['content'] = $item['content'];
                $v['comment_num'] = $item['comment_num'];
                $v['zan_num'] = $item['zan_num'];
                $v['collect_num'] = $item['collect_num'];
                unset($v['bind_id'], $v['cover_id']);
                $result['data'][$k] = $v;
            }
        }
        $this->return_lists_by_arr(1, $result);
    }
    /**
     * @name comment_lists
     * @title 留言列表
     * @param string $aid   文章ID
     * @param string $page  页码（默认为1）
     * @return 字段返回参见 comment_info接口
     */
    public function comment_lists(){
        $this->_check_param('aid');
        $aid = I('aid', 0, 'intval');
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        if(!($aid > 0)){
            $this->return_data(0);
        }
        $lists = $this->_comment_lists($aid, $page, $row, true);
        $this->return_data(1, $lists['data'], '', array('offset' => $page, 'row' => $row, 'total' => $lists['total'], 'count' => $lists['count']));
    }
    private function _comment_lists($aid, $page = 1, $row = 20, $get_total = false){
        $where = array('aid' => $aid, 'tid' => 0);
        $Model = M('ShopComment');
        $lists = $Model->where($where)->field('id,uid,puid,content,create_time,c_num')->page($page, $row)->order('id desc')->select();
        if($lists){
            $tid_arr = array();
            foreach($lists as $k => $v){
                $v['nickname'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $v['p_nickname'] = '';
                $v['p_header_pic'] = '';
                $v['c_num'] > 0 && $tid_arr[] = $v['id'];
                $v['child'] = array();
                $lists[$k] = $v;
            }
            if($tid_arr){
                $tdata = $Model->where(array('tid' => array('in', $tid_arr)))->field('id,uid,puid,tid,content,create_time')->order('id desc')->select();
                $cdata = array();
                foreach($tdata as $k => $v){
                    $v['nickname'] = get_nickname($v['uid']);
                    $v['header_pic'] = get_header_pic($v['uid']);
                    $v['p_nickname'] = get_nickname($v['puid']);
                    $v['p_header_pic'] = get_header_pic($v['puid']);
                    $item = $v;
                    unset($item['tid']);
                    $cdata[$v['tid']][] = $item;
                }
                foreach($lists as $k => $v){
                    $v['child'] = isset($cdata[$v['id']]) ? $cdata[$v['id']] : array();
                    $lists[$k] = $v;
                }
            }
        }else{
            $lists = array();
        }
        $result['data'] = $lists;
        $result['count'] = count($lists);
        if($get_total){
            $result['total'] = $Model->where($where)->count();
        }
        return $result;
    }
    /**
     * @name comment_info
     * @title 留言详情
     * @param string $cid   留言ID
     * @return [id] => id<br>[uid]=>用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[puid]=>被回复的用户ID<br>[p_nickname] => 被回复的昵称<br>[p_header_pic] => 被回复的头像<br>[content] => 内容<br>[create_time] => 创建时间<br>[c_num] => 留言回复数量<br>
                [child] => 该留言下面的所有回复的留言，返回信息参见上方
     */
    public function comment_info(){
        $this->_check_param('cid');
        $cid = I('cid');
        $Model = M('ShopComment');
        $info = $Model->where(array('id' => $cid, 'tip' => 0))->field('id,uid,puid,content,create_time,c_num')->find();
        if(!$info){
            $this->return_data(0, '', '留言不存在');
        }
        $info['nickname'] = get_nickname($info['uid']);
        $info['header_pic'] = get_header_pic($info['uid']);
        $info['p_nickname'] = '';
        $info['p_header_pic'] = '';
        $tdata = $info['c_num'] > 0 ? $Model->where(array('tid' => $cid))->field('id,uid,puid,content,create_time')->order('id desc')->select() : array();
        if($tdata){
            foreach($tdata as $k => $v){
                $v['nickname'] = get_nickname($v['uid']);
                $v['header_pic'] = get_header_pic($v['uid']);
                $v['p_nickname'] = get_nickname($v['puid']);
                $v['p_header_pic'] = get_header_pic($v['puid']);
                $tdata[$k] = $v;
            }
        }else{
            $tdata = array();
        }
        $info['child'] = $tdata;
        $this->return_data(1, $info);
    }
    /**
     * @name add_comment
     * @title 添加留言
     * @param string $aid  文章ID
     * @param string $cid  回复的留言ID（默认为0）
     * @param string $content  留言内容
     * @return [id] => id<br>[uid]=>用户ID<br>[nickname] => 昵称<br>[header_pic] => 头像<br>[content] => 内容<br>[create_time] => 创建时间
     */
    public function add_comment(){
        $this->check_account_token();
        $this->_check_param(array('aid', 'content'));
        $cid = I('cid', 0, 'intval');
        $aid = I('aid', 0, 'intval');
        $content = I('content', '', 'trim');
        $cid < 0 && $cid = 0;
        if(!$content){
            $this->return_data(0, '', '留言不能为空');
        }
        $shopArt = D('Addons://Shop/ShopArticle')->info($aid, true);
        if(!$shopArt){
            $this->return_data(0, '', '文章不存在');
        }
        $Model = M('ShopComment');
        $puid = 0;
        $tid = 0;
        if($cid > 0){
            $info = $Model->where(array('id' => $cid, 'aid' => $aid))->find();
            if(!$info){
                $this->return_data(0, '', '留言不存在');
            }
            $puid = $info['uid'];
            $info['tid'] > 0 && $tid = $info['tid'];
        }
        $data = array(
            'uid' => $this->_uid,
            'aid' => $aid,
            'pid' => $cid,
            'puid' => $puid,
            'tid' => $tid,
            'content' => $content,
            'create_time' => NOW_TIME,
        );
        $data = $Model->create($data);
        if(!$data){
            $this->return_data(0, '', '留言失败');
        }
        $result = $Model->add();
        if($data){
            $data['id'] = $result;
            if($tid == 0){
                M('ShopArticle')->where(array('id' => $aid))->setInc('comment_num');
                $api = new \User\Client\Api();
                $param = array(
                    'nickname' => get_nickname($this->_uid),
                    'header_pic' => get_header_pic($this->_uid),
                    'title' => $shopArt['title'],
                    'pic_url' => get_cover_url($shopArt['cover_id']),
                );
                $api->execute('Message', 'add_notice', array('act_uid' => $this->_uid, 'act_id' => $aid, 'type' => 'comment', 'uid' => $shopArt['uid'], 'param' => $param, 'hid' => $result));
            }else{
                $Model->where(array('id' => $tid))->setInc('c_num');
            }
            $this->return_data(1, $data, '留言成功');
        }else{
            $this->return_data(0, '', '留言失败');
        }
        
    }
    /**
     * @name add_goods
     * @title 添加商品
     * @param string    $title      文章标题
     * @param string    $content    文章内容
     * @param string    $cover      文章封面图
     * @param string    $pics       文章图片集合，多个图片id间用,分格(需要使用图片上传接口上传图片)
     * @param string    $goods_title    商品名
     * @param string    $goods_pic    商品封面ID
     * @param string    $num          库存数量（大于0的整数）
     * @param string    $price        价格（保留两位小数）
     * @param string    $express_money     运费（保留两位小数）
     * @param string    $tag                标签，多个标签间用,间隔（非必选）
     * @param string    $sheng      省份ID
     * @param string    $shi        城市ID
     * @param string    $qu         城市ID
     * @param string    $token      用户token
     * @return 
     */
    public function add_goods(){
        $this->check_account_token();
        $this->_check_param(array('title', 'content', 'cover', 'pics', 'sheng', 'shi', 'qu', 'goods_title', 'num', 'price', 'goods_pic'));
        $Model = D('Addons://Shop/ShopArticle');
        $num = I('num', 0, 'intval');
        if($num < 1){
            $this->return_data(0, '', '库存数必须大于0');
        }
        $price = round(I('price', 0), 2);
        if($price <= 0){
            $this->return_data(0, '', '价格必须大于0');
        }
        $express_money = round(I('express_money', 0), 2);
        $express_money < 0 && $express_money = 0;
        $sheng = I('sheng', 0, 'intval');
        $shi = I('shi', 0, 'intval');
        $qu = I('qu', 0, 'intval');
        $data = array(
            'uid' => $this->_uid,
            'title' => I('title'),
            'content' => I('content'),
            'cover_id'  => I('cover', 0, 'intval'),
            'pics' => I('pics'),
            'sheng' => $sheng,
            'shi' => $shi,
            'qu' => $qu,
            'goods_title' => I('goods_title'),
            'goods_pic' => I('goods_pic', 0, 'intval'),
            'num' => I('num', 0, 'intval'),
            'price' => $price,
            'express_money' => $express_money,
            'tag' => I('tag', '', 'trim'),
        );
        $_POST['uid'] = $this->_uid;
        $result = $Model->add_goods($data);
        if(!$result){
            $error = $Model->getError();
            !$error && $error = '添加失败';
            $this->return_data(0, '', $error);
        }
        $this->return_data(1, $result, '添加成功');
    }
    /**
     * @name edit_num
     * @title 修改库存
     * @param string    $goods_id       商品ID
     * @param string    $num            新的库存数量
     * 
     */
    public function edit_num(){
        $this->check_account_token();
        $this->_check_param(array('goods_id', 'num'));
        $num = I('num', 0, 'intval');
        if($num <= 0){
            $this->return_data(0, '', '库存数必须大于0');
        }
        $goods_id = I('goods_id', 0, 'intval');
        if(!($goods_id > 0)){
            $this->return_data(0, '', '请选择商品');
        }
        $Model = M('ShopGoods');
        if($Model->where(array('id' => $goods_id, 'uid' => $this->_uid))->save(array('num' => $num, 'update_time' => NOW_TIME))){
            $this->return_data(1, '', '修改成功');
        }else{
            $this->return_data(0, '', '修改失败');
        }
    }
    /**
     * @name user_goods_lists
     * @title 用户发布的商品列表
     * @param string    $type      类型：1 待审核  2 发布中 3 已下架
     * @return [id] => 文章ID<br>[bind_id] => 文章ID<br>[goods_id] => 商品id<br>[title] => 商品标题<br>[goods_pic] => 商品封面图<br>[price] => 售价<br>[num] => 库存<br>[express_money] => 运费<br>
                [status] => 状态：0 待审核 1 已通过 2 审核不通过<br>[is_shelf] => 上架状态： 0 未操作 1 已上架 2 已下架<br>[create_time] => 添加时间<br>[review_time] => 审核时间<br>[remark] => 商品备注信息（审核不通过的原因）
     */
    public function user_goods_lists(){
        $this->check_account_token();
        $type = I('type', 1, 'intval');
        $page = I('page', 1, 'intval');
        $page < 1 && $page = 1;
        $row = 20;
        $where = array();
        switch($type){
            case 1:
                $where['a.status'] = array('in', array(0, 2));
                break;
            case 2:
                $where['a.status'] = 1;
                $where['a.is_shelf'] = 1;
                break;
            case 3:
                $where['a.status'] = 1;
                $where['a.is_shelf'] = 2;
                break;
            default:
                $this->return_data(1, array(), '', array('offset' => $page, 'row' => $row, 'total' => 0, 'count' => 0));
        }
        $where['a.uid'] = $this->_uid;
        $Model = D('Addons://Shop/ShopArticle');
        $field = 'a.id, b.id as goods_id, b.title as goods_title, b.pic, a.create_time, a.status, a.is_shelf, a.review_time, price, num, express_money, a.remark';
        $join = '__SHOP_GOODS__ as b ON a.id = b.aid';
        $lists = $Model->alias('a')->join($join)->where($where)->page($page, $row)->field($field)->select();
        if($lists){
            foreach($lists as $k => $v){
                $v['bind_id'] = $v['id'];
                $v['goods_pic'] = get_cover_url($v['pic']);
                $lists[$k] = $v;
            }
        }else{
            $lists = array();
        }
        $count = count($lists);
        $total = $Model->alias('a')->join($join)->where($where)->count();
        $this->return_data(1, $lists, '', array('offset' => $page, 'row' => $row, 'total' => $total, 'count' => $count));
    }
    /**
     * @name to_publish
     * @title 发布商品
     * @param string    $goods_id       商品ID
     */
    public function to_publish(){
        $this->check_account_token();
        $this->_check_param('goods_id');
        $aid =I('goods_id', 0, 'intval');
        if(!($aid > 0)){
            $this->return_data(0, '', '商品不存在');
        }
        if(D('Addons://Shop/ShopArticle')->to_publish($aid, $this->_uid)){
            $this->return_data(1, '', '发布成功');
        }else{
            $this->return_data(0, '', '发布失败');
        }
    }
    /**
     * @name to_down
     * @title 下架商品
     * @param string    $goods_id       商品ID
     */
    public function to_down(){
        $this->check_account_token();
        $this->_check_param('goods_id');
        $aid =I('goods_id', 0, 'intval');
        if(!($aid > 0)){
            $this->return_data(0, '', '商品不存在');
        }
        if(D('Addons://Shop/ShopArticle')->to_down($aid, $this->_uid)){
            $this->return_data(1, '', '下架成功');
        }else{
            $this->return_data(0, '', '下架失败');
        }
    }
    /**
     * @name del_goods
     * @title 删除商品
     * @param string    $goods_id       商品ID
     * @remark  只能删除 待审核、审核不通过、已下架的商品
     */
    public function del_goods(){
        $this->check_account_token();
        $this->_check_param('goods_id');
        $aid =I('goods_id', 0, 'intval');
        if(!($aid > 0)){
            $this->return_data(0, '', '商品不存在');
        }
        if(D('Addons://Shop/ShopArticle')->del_by_user($aid, $this->_uid)){
            $this->return_data(1, '', '删除成功');
        }else{
            $this->return_data(0, '', '删除失败');
        }
    }
}
