<?php
namespace Addons\Warehouse\Model;

use Think\Model;
/*************************
 * 更改门店调拨申请状态
 ******************************/
class StoreRequestModel extends Model
{
    /*************************************
     * 根据出库验货单  更改  门店调拨申请状态
     * @param $w_out_id 出库验货单id
     ***************************************/
    public function saveWarehouseOutToStoreRequest($w_out_id){
        $sql = "
        Select A.w_out_id,A.s_r_id,A.w_out_status,B.w_out_d_id,B.s_r_d_id,B.g_num,B.in_num,B.out_num
        From hii_warehouse_out A left join hii_warehouse_out_detail B on A.w_out_id=B.w_out_id
        Where A.w_out_id =  $w_out_id and w_out_type = 1
        ";
        $Model = M('WarehouseOut');
        $list = $Model -> query($sql);
        if(is_array($list) && count($list) > 0) {
            foreach ($list as $k1 => $v1) {
                if ($list[$k1]['g_num'] == $list[$k1]['in_num']) {
                    //全部有货
                    $sql = "
                    Update hii_store_request_detail set is_pass = 2,pass_num = " . $list[$k1]['in_num'] . "  where s_r_d_id = " . $list[$k1]['s_r_d_id'] . "
                    ";
                    $Model1 = M('StoreRequest');
                    $UpdateS = $Model1->execute($sql);
                    if (!$UpdateS) {
                        $error = $Model1->getError();
                        $this->err = array('code'=>1,'msg'=>'修改门店发货申请出错1' .$error);
                    }
                }else{
                    if ($list[$k1]['g_num'] == $list[$k1]['out_num']) {
                        //全部拒绝
                        $sql = "
                        Update hii_store_request_detail set is_pass = 1,pass_num = 0  where s_r_d_id = " . $list[$k1]['s_r_d_id'] . "
                        ";
                        $Model1 = M('StoreRequest');
                        $UpdateS = $Model1->execute($sql);
                        if (!$UpdateS) {
                            $error = $Model1->getError();
                            $this->err = array('code'=>1,'msg'=>'修改门店发货申请出错2' .$error);
                        }
                    }else{
                        if($list[$k1]['out_num'] == 0 && $list[$k1]['in_num'] == 0){
                            //全部拒绝
                            $sql = "
                        Update hii_store_request_detail set is_pass = 1,pass_num = 0  where s_r_d_id = " . $list[$k1]['s_r_d_id'] . "
                        ";
                            $Model1 = M('StoreRequest');
                            $UpdateS = $Model1->execute($sql);
                            if (!$UpdateS) {
                                $error = $Model1->getError();
                                $this->err = array('code'=>1,'msg'=>'修改门店发货申请出错2' .$error);
                            }
                        }else {
                            //部分有货，部分拒绝
                            $sql = "
                        Update hii_store_request_detail set is_pass = 2,pass_num = " . $list[$k1]['in_num'] . "  where s_r_d_id = " . $list[$k1]['s_r_d_id'] . "
                        ";
                            $Model1 = M('StoreRequest');
                            $UpdateS = $Model1->execute($sql);
                            if (!$UpdateS) {
                                $error = $Model1->getError();
                                $this->err = array('code' => 1, 'msg' => '修改门店发货申请出错3' . $error);
                            }
                        }
                    }
                }
                $sql = "Select * from hii_store_request_detail where s_r_d_id = " . $list[$k1]['s_r_d_id'];
                $ModelMain = M('StoreRequestDetail');
                $listMain = $ModelMain -> query($sql);
                $s_r_id = $listMain[0]['s_r_id'];
                $res = $this -> checkStoreRequest($s_r_id);
                if($res > 0){
                    //$this->success('入库批次成功', Cookie('__forward__'));
                }else{
                    $this->err = array('code'=>1,'msg'=>'更改门店发货申请单状态失败：' .$res->err['msg']);
                }
            }
        }else{
            $this->err = array('code'=>1,'msg'=>'没有找到该单据：' .$w_out_id);
        }
        return $w_out_id;
    }
    /*************************************
     * 检测门店发货申请单状态，更改状态
     * @param $s_r_id 门店发货申请单id
     ***************************************/
    public function checkStoreRequest($s_r_id){
        $pass0 = 0;
        $pass1 = 0;
        $pass2 = 0;
        $sql = "
        Select A.*,B.s_r_d_id,B.goods_id,B.g_num,B.is_pass,B.pass_num
        From hii_store_request A left join hii_store_request_detail B on A.s_r_id=B.s_r_id
        Where A.s_r_id =  $s_r_id
        ";
        $Model = M('StoreRequest');
        $list = $Model -> query($sql);
        if(is_array($list) && count($list) > 0) {
            $sumcount = count($list);
            $pass2 = $list[0]['g_nums'];
            foreach ($list as $k1 => $v1) {
                $pass0 += $list[$k1]['is_pass'];
                $pass1 += $list[$k1]['pass_num'];
            }
            if($sumcount == $pass0){
                //全部未通过
                $sql = "
                    Update hii_store_request set s_r_status = 4
                    Where s_r_id =  $s_r_id
                    ";
                $res = $Model -> execute($sql);
            }else{
                if($sumcount == $pass0*2){
                    //全部出库中
                    $sql = "
                    Update hii_store_request set s_r_status = 1
                    Where s_r_id =  $s_r_id
                    ";
                    $res = $Model -> execute($sql);
                    if($pass2 > $pass1){
                        //部分发货
                        $sql = "
                        Update hii_store_request set s_r_status = 2
                        Where s_r_id =  $s_r_id
                        ";
                        $res = $Model -> execute($sql);
                    }else{
                        //全部发货
                        $sql = "
                        Update hii_store_request set s_r_status = 3
                        Where s_r_id =  $s_r_id
                        ";
                        $res = $Model -> execute($sql);
                    }
                }else{
                    //部分出库中
                    $sql = "
                    Update hii_store_request set s_r_status = 1
                    Where s_r_id =  $s_r_id
                    ";
                    $res = $Model -> execute($sql);
                }

            }
            if($res > 0){
                //$this->success('入库批次成功', Cookie('__forward__'));
            }else{
                $error = $Model->getError();
                $this->err = array('code'=>1,'msg'=>'更改门店发货申请单状态失败：' .$error);
            }
        }else{
            $this->err = array('code'=>1,'msg'=>'没有找到该单据：' .$s_r_id);
        }
        return $s_r_id;
    }
}