<?php

namespace Admin\Model;
use Think\Model;

class UcModel extends Model {
    protected $connection = 'UC_DB_CONFIG';
    protected $tablePrefix = '';
    
    public function __construct($name = '', $tablePrefix = '', $connection = '') {
        $this->tablePrefix =  C('UC_DB_TABLE_PREFIX');
        parent::__construct($name, $tablePrefix, $connection);
    }
}
