<?php

namespace Addons\Scorebox\Model;
use Admin\Model\UcModel;

class ScoreboxExchangeModel extends UcModel{
    
    protected $_auto = array(
            array('create_time', NOW_TIME, self::MODEL_INSERT), 
            array('update_time', NOW_TIME, self::MODEL_BOTH),
    );
}