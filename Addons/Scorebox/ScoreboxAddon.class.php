<?php

namespace Addons\Scorebox;

use Common\Controller\Addon;

class ScoreboxAddon extends Addon {

    public $info = array(
        'name' => 'Scorebox',
        'title' => '积分盒子',
        'description' => '用于积分管理及分销',
        'status' => 1,
        'author' => '小马',
        'version' => '0.1',
        'adminlist_url' => 'Addons/execute?_addons=Scorebox&_controller=Scorebox&_action=index',
    );
    public $admin_list = array(
        'listKey' => array(
            'title' => '标题',
            'create_time_text' => '添加时间',
            'create_time_text' => '更新时间',
        ),
        'model' => 'Scorebox',
        'order' => 'update_time desc'
    );
    public $custom_adminlist = 'adminlist.html';

    public function install() {
        $data = array(
            'title' => '积分盒子',
            'pid' => 0,
            'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxAdmin&_action=index'
        );
        $id = M('Menu')->add($data);
        $id1 = M('Menu')->add(array('title' => '用户信息列表', 'pid' => $id, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxAdmin&_action=index', 'group' => '积分盒子', 'sort' => 1));
        M('Menu')->add(array('title' => '编辑', 'pid' => $id1, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxAdmin&_action=save', 'group' => '积分盒子'));
        M('Menu')->add(array('title' => '保存', 'pid' => $id1, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxAdmin&_action=update', 'group' => '积分盒子'));
        
        $id2 = M('Menu')->add(array('title' => '积分配置', 'pid' => $id, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxConfigAdmin&_action=index', 'group' => '积分盒子', 'sort' => 2));
        M('Menu')->add(array('title' => '添加编辑', 'pid' => $id2, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxConfigAdmin&_action=save', 'group' => '积分盒子'));
        M('Menu')->add(array('title' => '保存', 'pid' => $id2, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxConfigAdmin&_action=update', 'group' => '积分盒子'));
        M('Menu')->add(array('title' => '删除', 'pid' => $id2, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxConfigAdmin&_action=remove', 'group' => '积分盒子'));
        
        $id3 = M('Menu')->add(array('title' => '积分记录', 'pid' => $id, 'url' => 'Addons/ex_scorebox?_addons=Scorebox&_controller=ScoreboxLogAdmin&_action=index', 'group' => '积分盒子', 'sort' => 3));
        
        $install_sql = './Addons/Scorebox/install.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function uninstall() {
        M('Menu')->where(array('url' => array('like', 'Addons/ex_scorebox?_addons=Scorebox%')))->delete();
        $install_sql = './Addons/Scorebox/uninstall.sql';
        if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
        }
        return true;
    }

    public function app_begin($param) {
        return true;
    }

}
