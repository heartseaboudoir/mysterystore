<?php
/**
 * Created by PhpStorm.
 * User: dehuang
 * Date: 2018-03-15
 * Time: 11:03
 * 月末库存快照后执行，用于设置商品区域价
 */

function set_shequ_price()
{
    header("Content-Type: text/html;charset=utf-8");
    defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
    date_default_timezone_set("PRC");
    $starttime = time();
    $time_limit = 1200;
    set_time_limit($time_limit);

    $ctime = time();
    $current_year = date('Y', $ctime);//当前年份
    $current_month = date('m', $ctime);//当前月份

// 连接数据库
    $db_config = @include ROOT_PATH . 'Application/Common/Conf/config.php';
    $db = new PDO("mysql:host={$db_config['DB_HOST']};dbname={$db_config['DB_NAME']}", $db_config['DB_USER'], $db_config['DB_PWD']);
    define('DB_PRE', $db_config['DB_PREFIX']);

//按区域分批处理
    $shequ_data = $db->query("select * from hii_shequ order by id ASC ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($shequ_data as $sd_key => $sd_val) {
        $update_data_array = array();
        $shequ_id = $sd_val["id"];
        //当前区域已设置的商品区域价
        $goods_shequ_data = $db->query("select * from hii_goods_shequ where shequ_id={$shequ_id} order by goods_id asc")->fetchAll(PDO::FETCH_ASSOC);
        if (!is_null($goods_shequ_data) && !empty($goods_shequ_data) && count($goods_shequ_data) > 0) {
            $goods_shequ_array = array();
            //构造区域价数组，主键为商品ID
            foreach ($goods_shequ_data as $goods_shequ_data_key => $goods_shequ_data_val) {
                $goods_shequ_array[$goods_shequ_data_val["goods_id"]] = $goods_shequ_data_val;
            }
            //已设置当前区域价的商品数组
            $goods_id_array = _array_column($goods_shequ_data, "goods_id");
            $goods_where = implode(",", $goods_id_array);
            //获取当前区域设置了区域价的门店商品库存信息
            $sql = "
      select GS.id,GS.goods_id,GS.store_id,GS.shequ_price 
      from hii_goods_store GS
      left join hii_store S on S.id=GS.store_id
      where GS.goods_id in ({$goods_where}) and S.shequ_id={$shequ_id}
      order by GS.goods_id asc
    ";
            $goods_store_data = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($goods_store_data as $goods_store_data_key => $goods_store_data_val) {
                //查找商品区域价
                $update_data_array[] = array(
                    "id" => $goods_store_data_val["id"],
                    "shequ_price" => $goods_shequ_array[$goods_store_data_val["goods_id"]]["price"] == 0 ? "NULL" : $goods_shequ_array[$goods_store_data_val["goods_id"]]["price"]
                );
            }
            if (count($update_data_array) > 0) {
                $sql = create_batch_update_sql($update_data_array, "id");
                //echo $sql;echo "<br/>";
                $ok = $db->query($sql);
                if (!$ok) {
                    echo " shequ_id:{$shequ_id} update fail!!!";
                    exit;
                }
            }
        }
        $goods_shequ_data = $db->query("select * from hii_goods_shequ where shequ_id={$shequ_id} and `status`=0 order by goods_id asc")->fetchAll(PDO::FETCH_ASSOC);
        //插入区域价快照表
        if (!is_null($goods_shequ_data) && !empty($goods_shequ_data) && count($goods_shequ_data) > 0) {
            $shequ_price_snapshot_array = array();
            foreach ($goods_shequ_data as $goods_shequ_data_key => $goods_shequ_data_val) {
                $shequ_price_snapshot_array_item = array(
                    "shequ_id" => $shequ_id,
                    "goods_id" => $goods_shequ_data_val["goods_id"],
                    "price" => $goods_shequ_data_val["price"],
                    "year" => $current_year,
                    "month" => $current_month,
                    "ctime" => $ctime,
                    "admin_id" => $goods_shequ_data_val["admin_id"]
                );
                $shequ_price_snapshot_array[] = "(" . implode(",", $shequ_price_snapshot_array_item) . ")";
            }
            $val_str = implode(',', $shequ_price_snapshot_array);
            $sql = "insert into hii_shequ_price_snapshot(`shequ_id`,`goods_id`,`price`,`year`,`month`,`ctime`,`admin_id`) value " . $val_str;
            $ok = $db->query($sql);
            if (!$ok) {
                echo $sql;
                echo "<br/>";
                echo "区域【{$shequ_id}】的价格快照保存失败";
                exit;
            }
        }
    }

    $ok = $db->query("update hii_goods_shequ set `status`=1 ");
    if (!$ok) {
        echo "update error!!!";
        exit;
    }

//echo "<pre>";print_r($update_data_array);echo "</pre><br>";

    $endtime = time();
    echo "success done!!!耗时：【" . ($endtime - $starttime) . "】毫秒";
}

set_shequ_price();

/**************
 * 生成批量更新SQL语句
 * @param $column_key 主键
 * @param $data 数据
 */
function create_batch_update_sql($datas, $pk)
{
    if (is_null($datas) || empty($datas) || count($datas) == 0) {
        echo "没有更新数据";
        exit;
    }
    $sql = ''; //Sql
    $tableName = "hii_goods_store";
    $lists = array(); //记录集$lists
    //$pk = $this->getPk();//获取主键
    foreach ($datas as $data) {
        foreach ($data as $key => $value) {
            if ($pk === $key) {
                $ids[] = $value;
            } else {
                $lists[$key] .= sprintf("WHEN %u THEN %s ", $data[$pk], $value);
            }
        }
    }
    foreach ($lists as $key => $value) {
        $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
    }
    $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s )', $tableName, rtrim($sql, ','), $pk, implode(',', $ids));
    return $sql;
}

function _array_column($input, $columnKey, $indexKey = null)
{
    if (!function_exists('array_column')) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array)$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    } else {
        return array_column($input, $columnKey, $indexKey);
    }
}