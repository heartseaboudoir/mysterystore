<?php
namespace Internal\Controller;

use Think\Controller;

class DataViewController extends ApiController
{

    //定义数据所属类型
    const GOODS_SALE_TYPE = 1;//商品销售
    const GOODS_CAT_SALE_TYPE = 2;//商品类销售
    const SHEQU_SALE_TYPE = 3;//城市(门店)热点
    const STORE_SALE_TYPE = 4;//门店销售
    const YEAR_SALE_RATE_TYPE = 5;//年度销售比率
    const QUARTER_SALE_RATE_TYPE = 6;//季度销售环比
    const SHEQU_SALE_RATE_TYPE = 7;//城市增长率
    const YEAR_SALE_TOTAL_TYPE = 8;//年度销售总额
    const CURRENT_SALE_TOTAL_TYPE = 9;//当前销售总额
    const LAST_SALE_TOTAL_TYPE = 10;//昨天销售总额
    const CURRENT_SALE_RATE_TYPE = 11;//当前销售总额
    const CURRENT_SALE_TIMES_TYPE = 12;//当前消费次数
    const LAST_SALE_TIMES_TYPE = 13;//昨天消费次数

    public function _initialize()
    {
        /*        // 是否验证token
                $action = ACTION_NAME;
                $actions = array();
                $check = false; // true为指定的验证，false为指定的不验证
                parent::_initialize();*/
        $this->set_time = strtotime(date('Y', time()) . '-01-01');
    }

    /**
     * @name:商品销售额排名()
     * @params:
     * @author: Ard
     * @date: 2018-05-15
     */
    public function saleRankByGood()
    {
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::GOODS_SALE_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:商品类销售额排名()
     * @params:
     * @author: Ard
     * @date: 2018-05-15
     */
    public function saleRankByGoodCat()
    {
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::GOODS_CAT_SALE_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:城市销售额排名(区域排名)
     * @params:
     * @author: Ard
     * @date: 2018-05-15
     */
    public function saleRankByShequ()
    {
        //获取本月第一天
        $current_month_time = strtotime(date('Y-m-01',time()));
        $orderModel = D('Order');
        $getData = $orderModel->alias('O')
            ->join('hii_store as S ON S.id = O.store_id', 'LEFT')
            ->join('hii_shequ as SQ ON SQ.id = S.shequ_id', 'LEFT')
            ->WHERE('O.create_time > ' . $current_month_time . ' AND O.store_id > 0 AND SQ.newerp = 1 AND O.status = 5 ')
            ->field('SQ.id , SQ.title , convert(SUM(pay_money)/10000 , decimal) as total_money')
            ->GROUP('SQ.id')
            ->ORDER('total_money DESC')
            ->select();

        $flag = array();
        foreach ($getData as $k => $v) {
            $flag[] = $v['total_money'];
        }
        array_multisort($flag, SORT_DESC, $getData);
        echo json_encode($getData);
    }

    /**
     * @name: 季度同比增长比率(根据当前季度时间区间作为参考)
     * @params
     * @author:Ard
     * @date:2018-05-16
     */
    public function quarterGrowth(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::QUARTER_SALE_RATE_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:获取当前季度的销售额
     * @paras
     * @author:Ard
     * @date:2018-05-16
     */
    public function quarterCurrentSale(){
        //获取当前月份的季度
        $season = ceil(date('n') /3); //获取月份的季度
        //定义当前时间
        $current_time  = time();
        //本季度开始时间
        $current_quarter_time = mktime(0,0,0,($season - 1) *3 +1,1,date('Y'));
        $orderModel = D('order');
        $data = $orderModel->where(array('create_time' => array(array('EGT' , $current_quarter_time) ,array('ELT' , $current_time) , 'AND') , 'status' => 5))
            ->field('convert(SUM(pay_money)/10000,decimal) as money')->find();
        if(empty($data) || $data['money'] <= 0){
            echo json_encode(array(array('money' => 0)));
            exit;
        }
        echo json_encode(array(array('money' => $data['money'])));
    }

    /**
     * @name: 年份同比增长比率(根据当前年份时间区间作为参考)
     * @params
     * @author:Ard
     * @date:2018-05-16
     */
    public function yearGrowth(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::YEAR_SALE_RATE_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:季度销售进度
     * @paras:
     * @author:Ard
     * @date:2018-05-16
     *
     */
    public function quarterSalePlan(){
        //相关季度的销售目标
        $salePlanData = array(
            '2'=> 300.3,
            '3'=>449,
            '4'=>496.1
        );
        //获取当前月份的季度
        $season = ceil(date('n') /3); //获取月份的季度
        //定义当前时间
        $current_time  = time();
        //本季度开始时间
        $current_quarter_time = mktime(0,0,0,($season - 1) *3 +1,1,date('Y'));
        $orderModel = D('order');
        //获取本季度销售额度
        $current_sale_data = $orderModel->where(array('create_time' => array(array('EGT' , $current_quarter_time) ,array('ELT' , $current_time) , 'AND') , 'status' => 5))
            ->field('convert(SUM(pay_money)/10000,decimal) as money')->find();
        if(empty($current_sale_data) || $current_sale_data['money'] <= 0){
            echo json_encode(array(array('rate' => 0)));
        }
        $rate = sprintf("%.2f",($current_sale_data['money'] / $salePlanData[$season]));
        echo json_encode(array(array('rate' => $rate)));
    }

    /**
     * @name:获取增长速度最快城市(区域)
     * @params:
     * @author:Ard
     * @date:2018-05-16
     */
    public function saleStartByShequ(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::SHEQU_SALE_RATE_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:门店销售额TOP 10
     * @params:
     * @author:Ard
     * @date:2018-05-18
     */
    public function saleStartByStore(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::STORE_SALE_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }



    /**
     * @name:年度销售总额
     * @params:
     * @author:Ard
     * @date:2018-05-18
     */
    public function yearSaleTotal(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::YEAR_SALE_TOTAL_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:当前销售总额
     * @params:
     * @author:Ard
     * @date:2018-05-21
     */
    public function lastSaleTotal(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::LAST_SALE_TOTAL_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:当前销售总额
     * @params:
     * @author:Ard
     * @date:2018-05-18
     */
    public function currentSaleTotal(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::CURRENT_SALE_TOTAL_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }


    /**
     * @name:当前销售总额增长率
     * @params:
     * @author:Ard
     * @date:2018-05-21
     */
    public function currentSaleRate(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::CURRENT_SALE_RATE_TYPE))->find();
        if ($getData) {
            echo $getData['data'];
        } else {
            echo json_encode(array('status' => -999, 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:当前销售总额增长率
     * @params:
     * @author:Ard
     * @date:2018-05-21
     */
    public function currentSaleTimes(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::CURRENT_SALE_TIMES_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }

    /**
     * @name:当前销售总额增长率
     * @params:
     * @author:Ard
     * @date:2018-05-21
     */
    public function lastSaleTimes(){
        $ali_data_model = D('ali_data_view');
        $getData = $ali_data_model->WHERE(array('type' => self::LAST_SALE_TIMES_TYPE))->find();
        if($getData){
            echo $getData['data'];
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }


    /**
     * 全国大图气泡渲染
     *
     */
    public function nationwideData(){
        $orderModel = D('Order');
        $getData = $orderModel->alias('O')
            ->join('hii_store as S ON S.id = O.store_id', 'LEFT')
            ->WHERE('S.latitude >0 AND S.longitude >0')
            ->field('S.longitude , S.latitude , SUM(O.pay_money) as total_price')
            ->GROUP('S.id')
            ->select();
        if($getData){
            foreach($getData as $key => &$value){
                $chageData = $this->Convert_BD09_To_GCJ02($value['latitude'] , $value['longitude']);
                $value['latitude'] = $chageData['lat'];
                $value['longitude'] = $chageData['lng'];
            }
            echo json_encode($getData);
        }else{
            echo json_encode(array('status' => -999 , 'msg' => '获取数据失败'));
        }
    }


    /**
     * 百度地图转换
     *
     *
     */
    public function Convert_BD09_To_GCJ02($lat,$lng){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta);
        $lat = $z * sin($theta);
        return array('lng'=>$lng,'lat'=>$lat);
    }

    /**
     * @param $array
     * @param $key
     * @param bool $is_value
     * @return array
     */
    function valueToKey($array , $key , $is_value = false){
        $newArray = array();
        if($is_value){
            foreach ($array as $k => $v){
                $newArray[$v[$key]] = $v;
            }
        }else{
            foreach ($array as $k => $v){
                $newArray[$v[$key]][] = $v;
            }
        }
        return $newArray;
    }
}
