<?php

/**
 * 商品操作记录
 * 当人工对商品的库存进行操作时，需要进行提供的记录  类型：1 入库 2 出库 3 找回 4 丢失  每天执行前一天的数据  2017推送
 */
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set("Asia/Shanghai");
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
defined('CAIJI_NAME') or define('CAIJI_NAME', 'change_sg_price');
$url = 'http://udeana.dev.hiiyun.com/api/Sale/operation_ls';
$api = array(
    'appid' => 'zhaike',
    'appkey' => 'a887ff563347d216a5d0cc0413f89gf0'
);
set_time_limit(300);


// 连接数据库
$db_config = @include ROOT_PATH.'Application/Common/Conf/config.php';
$db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
$sql = "select log_time from hii_goods_log_apiwiki where id = 2 ";
$query = $db->query($sql);
if($query !== false){
    $query = $query->fetch(PDO::FETCH_ASSOC);
    if(empty($query)){
        exit('没有时间');
    }
    $time = $query['log_time'];
    $e_time = $time;
    $s_time = $e_time-3600*24;
    if(date('Ym',$s_time) >= 201804){
        exit('只取201804之前的数据');
    }
}else{
    exit('没有时间');
}
/**************入库操作 1**********************/
$sql = "SELECT
            *
        FROM
            hii_goods_store_apply
        WHERE
            create_time >= {$s_time}
        AND create_time < {$e_time}
        AND type = 1 
        AND store_id not in(select id from hii_store where shequ_id = 3 and shequ_id = 18)
        ";
$goods = $db->query($sql);
$data1 = array();
$array = array();
if($goods !== false){
    $data = $goods->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $key=>$val){
        $goods_id = json_decode($val['data'],true);
        foreach($goods_id  as $k=>$v){
            if(array_key_exists($val['store_id'].'-'.$v['id'],$array)){
                $array[$val['store_id'].'-'.$v['id']] += $v['num'];
            }else{
                $array[$val['store_id'].'-'.$v['id']] = $v['num'];
                $a = array(
                    'store_id'=>$val['store_id'],
                    'goods_id'=>$v['id'],
                    'apply_admin'=>$val['uid'],
                    'check_admin'=>$val['uid'],
                    'type'=>1,
                    'log_time'=>$val['create_time'],
                );
                $data1[] = $a;
            }

        }

    }
}
foreach($data1 as $key=>$val){
        $sql = "SELECT
                    if(gs.price,gs.price,g.sell_price)price,
                    g.title,
                   g.cate_id,
                    gc.title as cate_title
                FROM
                    hii_goods_store gs
                LEFT JOIN hii_goods g ON g.id = gs.goods_id 
                LEFT JOIN hii_goods_cate gc ON gc.id = g.cate_id
                WHERE
                 gs.store_id={$val['store_id']}
                and gs.goods_id = {$val['goods_id']}";
    $goods = $db->query($sql);
    if($goods !== false){
        $goods = $goods->fetch(PDO::FETCH_ASSOC);
    }
    $data1[$key]['goods_title'] = $goods['title'];
    $data1[$key]['cate_id'] = $goods['cate_id'];
    $data1[$key]['cate_title'] = $goods['cate_title'];
    $data1[$key]['sell_price'] = $goods['price'];
    $data1[$key]['num'] = $array[$val['store_id'].'-'.$val['goods_id']];
}


/**********************出库操作 2******************************/
$sql = "SELECT
            *
        FROM
            hii_goods_store_apply
        WHERE
            create_time >= {$s_time}
        AND create_time < {$e_time}
        AND type = 2 
        AND store_id not in(select id from hii_store where shequ_id = 3 and shequ_id = 18)";
$goods = $db->query($sql);
$data2 = array();
$array = array();
if($goods !== false){
    $data = $goods->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $key=>$val){
        $goods_id = json_decode($val['data'],true);
        foreach($goods_id  as $k=>$v){
            if(array_key_exists($val['store_id'].'-'.$v['id'],$array)){
                $array[$val['store_id'].'-'.$v['id']] += $v['num'];
            }else{
                $array[$val['store_id'].'-'.$v['id']] = $v['num'];
                $a = array(
                    'store_id'=>$val['store_id'],
                    'goods_id'=>$v['id'],
                    'apply_admin'=>$val['uid'],
                    'check_admin'=>$val['uid'],
                    'type'=>2,
                    'log_time'=>$val['create_time'],
                );
                $data2[] = $a;
            }

        }

    }
}
foreach($data2 as $key=>$val){
    $sql = "SELECT
                    if(gs.price,gs.price,g.sell_price)price,
                    g.title,
                   g.cate_id,
                    gc.title as cate_title
                FROM
                    hii_goods_store gs
                LEFT JOIN hii_goods g ON g.id = gs.goods_id 
                LEFT JOIN hii_goods_cate gc ON gc.id = g.cate_id
                WHERE
                 gs.store_id={$val['store_id']}
                and gs.goods_id = {$val['goods_id']}";
    $goods = $db->query($sql);
    if($goods !== false){
        $goods = $goods->fetch(PDO::FETCH_ASSOC);
    }
    $data2[$key]['goods_title'] = $goods['title'];
    $data2[$key]['cate_id'] = $goods['cate_id'];
    $data2[$key]['cate_title'] = $goods['cate_title'];
    $data2[$key]['sell_price'] = $goods['price'];
    $data2[$key]['num'] = $array[$val['store_id'].'-'.$val['goods_id']];
}

/**************盘盈入库 3**********************/
$sql = "SELECT
            *
        FROM
            hii_goods_store_apply
        WHERE
            create_time >= {$s_time}
        AND create_time < {$e_time}
        AND type = 3 
        AND store_id not in(select id from hii_store where shequ_id = 3 and shequ_id = 18)";
$goods = $db->query($sql);
$data3 = array();
$array = array();
if($goods !== false){
    $data = $goods->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $key=>$val){
        $goods_id = json_decode($val['data'],true);
        foreach($goods_id  as $k=>$v){
            if(array_key_exists($val['store_id'].'-'.$v['id'],$array)){
                $array[$val['store_id'].'-'.$v['id']] += $v['num'];
            }else{
                $array[$val['store_id'].'-'.$v['id']] = $v['num'];
                $a = array(
                    'store_id'=>$val['store_id'],
                    'goods_id'=>$v['id'],
                    'apply_admin'=>$val['uid'],
                    'check_admin'=>$val['uid'],
                    'type'=>3,
                    'log_time'=>$val['create_time'],
                );
                $data3[] = $a;
            }

        }

    }
}
foreach($data3 as $key=>$val){
    $sql = "SELECT
                    if(gs.price,gs.price,g.sell_price)price,
                    g.title,
                   g.cate_id,
                    gc.title as cate_title
                FROM
                    hii_goods_store gs
                LEFT JOIN hii_goods g ON g.id = gs.goods_id 
                LEFT JOIN hii_goods_cate gc ON gc.id = g.cate_id
                WHERE
                 gs.store_id={$val['store_id']}
                and gs.goods_id = {$val['goods_id']}";
    $goods = $db->query($sql);
    if($goods !== false){
        $goods = $goods->fetch(PDO::FETCH_ASSOC);
    }
    $data3[$key]['goods_title'] = $goods['title'];
    $data3[$key]['cate_id'] = $goods['cate_id'];
    $data3[$key]['cate_title'] = $goods['cate_title'];
    $data3[$key]['sell_price'] = $goods['price'];
    $data3[$key]['num'] = $array[$val['store_id'].'-'.$val['goods_id']];
}



/**********************盘亏出库操作 4******************************/
$sql = "SELECT
            *
        FROM
            hii_goods_store_apply
        WHERE
            create_time >= {$s_time}
        AND create_time < {$e_time}
        AND type = 4 
        AND store_id not in(select id from hii_store where shequ_id = 3 and shequ_id = 18)";
$goods = $db->query($sql);
$data4 = array();
$array = array();
if($goods !== false){
    $data = $goods->fetchAll(PDO::FETCH_ASSOC);
    foreach($data as $key=>$val){
        $goods_id = json_decode($val['data'],true);
        foreach($goods_id  as $k=>$v){
            if(array_key_exists($val['store_id'].'-'.$v['id'],$array)){
                $array[$val['store_id'].'-'.$v['id']] += $v['num'];
            }else{
                $array[$val['store_id'].'-'.$v['id']] = $v['num'];
                $a = array(
                    'store_id'=>$val['store_id'],
                    'goods_id'=>$v['id'],
                    'apply_admin'=>$val['uid'],
                    'check_admin'=>$val['uid'],
                    'type'=>4,
                    'log_time'=>$val['create_time'],
                );
                $data4[] = $a;
            }

        }

    }
}
foreach($data4 as $key=>$val){
    $sql = "SELECT
                    if(gs.price,gs.price,g.sell_price)price,
                    g.title,
                   g.cate_id,
                    gc.title as cate_title
                FROM
                    hii_goods_store gs
                LEFT JOIN hii_goods g ON g.id = gs.goods_id 
                LEFT JOIN hii_goods_cate gc ON gc.id = g.cate_id
                WHERE
                 gs.store_id={$val['store_id']}
                and gs.goods_id = {$val['goods_id']}";
    $goods = $db->query($sql);
    if($goods !== false){
        $goods = $goods->fetch(PDO::FETCH_ASSOC);
    }
    $data4[$key]['goods_title'] = $goods['title'];
    $data4[$key]['cate_id'] = $goods['cate_id'];
    $data4[$key]['cate_title'] = $goods['cate_title'];
    $data4[$key]['sell_price'] = $goods['price'];
    $data4[$key]['num'] = $array[$val['store_id'].'-'.$val['goods_id']];
}

$data = array_merge($data1,$data2,$data3,$data4);
unset($data1,$data2,$data3,$data4);
if(empty($data)){
    $time = $time + (3600*24);
    $db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=2");
    exit('没有出入库记录');
}
$md5 = md5(strtolower($url).$api['appid'].$api['appkey'].date('Y-m-d').$time);
$header = array('st:'.$time,'utoken:'.$md5);
$int = 0;
$tiao = 200;
while (true) {
    $array = array_slice($data,$int,$tiao);
    $count = count($array);
    if(empty($array)){
        break;
    }
    $array = json_encode($array);

    $a =  http_post($url, array('data'=>$array,'row'=>$count), $header);
    $a = json_decode($a,true);
    if($a['status'] == 1){
        if(!empty($a['data']['fail'])){
            echo '推送数据未执行的门店id和商品id'.json_encode($a['data']['fail']);
        }
    }
    $int += $tiao;
}


/*********************损耗**************************************/
$time = $time + (3600*24);
$db->exec("update hii_goods_log_apiwiki set log_time = {$time} where id=2");
die;

function http_post($url,$data,$header){
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return $data;
}

function set_log($data, $time = 1)
{
    $log_dir = ROOT_PATH . 'Runtime/' . CAIJI_NAME . '_logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_dir . date('Y-m') . '.txt', ($time ? "[" . date('Y-m-d H:i:s') . "] " : '') . $data . "\r\n", FILE_APPEND);
}