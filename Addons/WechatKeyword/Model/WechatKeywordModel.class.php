<?php
namespace Addons\WechatKeyword\Model;
use Think\Model;

class WechatKeywordModel extends Model{
	/**
	 * 自动完成
	 * @var array
	 */
        protected $_validate = array(
                array('keyword', 'require', '关键词不能为空', self::MUST_VALIDATE),
        );
	protected $_auto = array(
		array('create_time', NOW_TIME, self::MODEL_INSERT),
		array('update_time', NOW_TIME, self::MODEL_BOTH),
	);
	protected function _after_find(&$result,$options) {
		isset($result['create_time']) && $result['create_time_text'] = date('Y-m-d H:i:s', $result['create_time']);
		isset($result['update_time']) && $result['update_time_text'] = date('Y-m-d H:i:s', $result['update_time']);
                if(isset($result['type'])){
                    $type = array('text' =>'文本', 'image' => '图片', 'news' => '图文', 'video' => '视频', 'voice' => '音频');
                    $result['type_text'] = isset($type[$result['type']]) ? $type[$result['type']] : '';
                }
                isset($result['content']) && $result['content'] = json_decode($result['content'], true);
	}

	protected function _after_select(&$result,$options){
		foreach($result as &$record){
			$this->_after_find($record,$options);
		}
	}
        
        protected function _before_insert(&$data, $options) {
            parent::_before_insert($data, $options);
            $data = $this->_set_data($data);
        }

        protected function _before_update(&$data, $options) {
            parent::_before_update($data, $options);
            $data = $this->_set_data($data);
        }
        
        private function _set_data(&$data){
            if(isset($data['keyword'])){
                $data['keyword'] = trim($data['keyword'], "|");
                strpos($data['keyword'], '|') !== false && $data['keyword'] = '|'.$data['keyword'].'|';
                $data['keyword'] = str_replace(" |", "|", $data['keyword']);
                $data['keyword'] = str_replace("| ", "|", $data['keyword']);
            }
            if(isset($data['content'])){
                isset($data['content'][$data['type']]) ? $data['content'][$data['type']] : array();
                $content = array();
                $content[$data['type']] = isset($data['content'][$data['type']]) ? $data['content'][$data['type']] : array();
                $data['content'] = json_encode($content);
            }
            return $data;
        }

        public function update($data = NULL){
            $data = $this->create($data);
            if(!$data){
                return false;
            }
            if(empty($data['id'])){
                $id = $this->add();
                if(!$id){
                    !$this->error && $this->error = '添加出错！';
                    return false;
                }
            } else {
                $status = $this->save();
                if(false === $status){
                    !$this->error && $this->error = '更新出错！';
                    return false;
                }
            }
            return $data;
        }
        
        public function get_content($keyword, $ukey = ''){
            $WechatKeyword = D('Addons://WechatKeyword/WechatKeyword');
            $where = array();
            $where['keyword'] = $keyword;
            $ukey && $where['ukey'] = $ukey;
            $data = $WechatKeyword->where($where)->order('listorder desc')->find();
            $result['type'] = 'text';
            $result['content'] = '';
            if($data){
                $result = $this->_get_data($data, $ukey);
            }else{
                $where['keyword'] = array('like', "%|$keyword|%");
                $data = $WechatKeyword->where($where)->order('listorder desc')->find();
                if($data){
                    $result = $this->_get_data($data, $ukey);
                }
            }
            return $result;
        }

        private function _get_data($data = array(), $ukey = ''){
            $result['content'] = '';
            $result['type'] = $data['type'];
            switch($data['type']){
                case 'text':
                    $result['content'] = $data['content']['text'];
                    break;
                case 'image':
                    $path = get_cover($data['content']['image'], 'path');
                    $media = A("Addons://Wechat/Wechatclass")->upload_file('image', realpath(trim($path, '/')));
                    $result['content'] = isset($media['media_id']) ? $media['media_id'] : '';
                    break;
                case 'news':
                    $ids = trim($data['content']['news']);
                    $where = array(
                        'id' => array('in', $ids),
                        'status' => 1,
                    );
                    $list = M('Document')->where($where)->order("find_in_set(id,'$ids')")->limit(10)->select();
                    $idata = array();
                    foreach($list as $v){
                        $path = get_cover($v['cover_id'], 'path');
                        $cover = $path ? (get_domain().$path) : ''; 
                        $idata[] = array(
                            $v['title'],
                            $v['description'],
                            $cover,
                            U('wechat/article/detail', array('id' => $v['id'], 'ukey' => $ukey)),
                        );
                    }
                    $result['content'] = $idata;
                    break;
                case 'video':
                    $medic_id = A("Addons://Wechat/Wechatclass")->upload_file('video', $data['content']['image']);
                    $result['content'] = $data['content']['video'];
                    break;
                case 'voice':
                    $medic_id = A("Addons://Wechat/Wechatclass")->upload_file('voice', $data['content']['image']);
                    $result['content'] = $data['content']['content'];
                    break;
                default:
                    $result['type'] = 'text';
                    $result['content'] = '';
                    break;
            }
            return $result;
        }

}