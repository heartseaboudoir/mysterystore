<?php
/**
 * Created by PhpStorm.
 * User: Ard
 * Date: 2018-05-02
 * 月末库存快照后执行，用于设置商品售价
 */

function set_selling_price()
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

    //获取商品售价表
    $get_selling_data = $db->query("SELECT id , sell_price FROM hii_goods")->fetchAll(PDO::FETCH_ASSOC);
    if($get_selling_data){
        //记录更新id
        $ids = array();
        foreach ($get_selling_data as $key => $val) {
            //售价快照表
            $shequ_price_snapshot_array_item = array(
                "goods_id" => $val["id"],
                "price" => $val["sell_price"],
                "year" => $current_year,
                "month" => $current_month,
                "ctime" => $ctime,
                "admin_id" => 6041
            );
            $selling_price_snapshot_array[] = "(" . implode(",", $shequ_price_snapshot_array_item) . ")";
        }

        //插入快照信息
        $val_str = implode(',', $selling_price_snapshot_array);
        $sql = "insert into hii_selling_price_snapshot(`goods_id`,`price`,`year`,`month`,`ctime`,`admin_id`) value " . $val_str;
        $ok = $db->query($sql);
        if (!$ok) {
            echo 'add goods_selling_snapshot error<br/>';
            set_log('操作语句为: '.$sql.'\t');
        }
    }else{
        echo 'nothing can do it<br/>';
    }

    $ok = $db->query("update hii_goods_selling set `status`=1 ");
    if (!$ok) {
        echo "update error!!!";
        exit;
    }

    $endtime = time();
    echo "success done!!!耗时：【" . ($endtime - $starttime) . "】毫秒";
}

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
    $tableName = "hii_goods";
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

/**
 * @param $input
 * @param $columnKey
 * @param null $indexKey
 * @return array
 */
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

//set_selling_price();