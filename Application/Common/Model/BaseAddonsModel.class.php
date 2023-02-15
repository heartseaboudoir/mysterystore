<?php

namespace Common\Model;

use Think\Model;

class BaseAddonsModel extends Model {

    public function update($data = NULL) {
        $data = $this->create($data);
        if (!$data) {
            return false;
        }
        if (empty($data[$this->pk])) {
            $id = $this->add();
            if (!$id) {
                !$this->error && $this->error = '添加出错！';
                return false;
            }
        } else {
            $status = $this->save();
            if (false === $status) {
                !$this->error && $this->error = '更新出错！';
                return false;
            }
        }
        return $data;
    }

}
