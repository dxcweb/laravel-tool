<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/2/25
 * Time: 16:34
 */

namespace Tool;


use Tool\Util\UserInfo;

class Model
{

    public $change_records = [];
    public $is_insert = true;
    public $is_update = true;
    public $is_delete = true;

    protected $connection;//数据库链接
    protected $table;
    protected $primary_key;//主键
    protected $foreign_key;//外键
    private $foreign_key_val;//外键数据，插入或更新时原始数据不存在的时候加上
    protected $fields;
    protected $redundant;//冗余字段
    protected $redundant_data;//冗余字段数据,插入或更新时原始数据不存在的时候加上
    protected $original_data;
    
    private $db;
    private $update_data;
    private $insert_data;
    private $delete_data;

    //多创建时间，更新时间，删除时间，创建人。
    protected $_update_data;
    protected $_insert_data;
    protected $_delete_data1;//单主键。whereIN删
    protected $_delete_data2;//多主键.单条删除
    protected $db_data;//数据库数据


    protected $auto_increment = true;//自增

    public function __construct()
    {
        if (empty($this->table)) {
            _pack("Model没有定义表名", false);
        }
        if (empty($this->fields)) {
            _pack("Model没有定义字段", false);
        }
        if (empty($this->primary_key)) {
            _pack("Model没有定义主键", false);
        }
        if (isset($this->connection) && $this->connection != "")
            $db = DB::connection($this->connection)->table($this->table);
        else
            $db = DB::table($this->table);
        $this->db = $db;
    }


    /**
     * 改变记录（需要重写改方法）
     */
    protected function changeRecords($type, $new, $old)
    {
        if ($type == 1) {
            //插入

        } else if ($type == 2) {
            //更新

        } else if ($type == 4) {
            //删除

        }
    }

    /**
     * 保存（无ID插入，有ID更新）并返回记录
     */
    public function save($original_data, $foreign_key_val = null, $redundant_data = null)
    {
        $this->foreign_key_val = $foreign_key_val;
        $this->redundant_data = $redundant_data;
        $this->original_data = $original_data;
        $this->clearsData();
        $this->init_data($original_data);
        $this->run();
        $data['insert'] = $this->insert_data;
        $data['update'] = $this->update_data;
        $data['delete'] = $this->delete_data;
        return $data;
    }

    private function run()
    {
        $this->insertData();
        $this->updateData();
        $this->deleteData();
    }

    /**
     * 清空之前操作数据
     */
    public function clearsData()
    {
        $this->update_data = [];
        $this->delete_data = [];
        $this->insert_data = [];
        $this->_update_data = [];
        $this->_delete_data = [];
        $this->_insert_data = [];
    }

    /**
     *
     */
    private function getDbByPrimaryKey($data)
    {
        $db = $this->db;
        if (is_array($this->primary_key)) {
            foreach ($this->primary_key as $val) {
                $db->where($val, $data[$val]);
            }
        } else {
            $db->where($this->primary_key, $data[$this->primary_key]);
        }
        return $db;
    }

    private function getPrimaryKey($data)
    {
        $primary_key = [];
        if (is_array($this->primary_key)) {
            foreach ($this->primary_key as $val) {
                $primary_key[$val] = $data[$val];
            }
        } else {
            $primary_key[$this->primary_key] = $data[$this->primary_key];
        }
        return $primary_key;
    }

    private function getForeignKey($data)
    {
        $foreign_key = [];
        if (is_array($this->foreign_key)) {
            foreach ($this->foreign_key as $val) {
                $foreign_key[$val] = $data[$val];
            }
        } else {
            $foreign_key[$this->foreign_key] = $data[$this->foreign_key];
        }
        return $foreign_key;
    }

    /**
     * 通过主键查询
     */
    private function queryByPrimaryKey($data)
    {
        $db = $this->getDbByPrimaryKey($data);
        $data = $db->get($this->fields);
        if ($this->auto_increment && empty($data))
            _pack($this->table . "无效主键" . json_encode($this->primary_key), false);
        $this->db_data = $data;
    }

    /**
     * 通过外键查询
     */
    private function queryByForeignKey($data)
    {
        if (empty($data)) {
            _pack("没有传入外键", false);
        }
        $db = $this->db;
        if (is_array($this->foreign_key)) {
            foreach ($this->foreign_key as $val) {

                $db->where($val, $data[$val]);
            }
        } else {
            $db->where($this->foreign_key, $data[$this->foreign_key]);
        }
        $data = $db->get($this->fields);
        $this->db_data = $data;
    }

    /**
     * 初始化数据
     */
    private function init_data($original_data)
    {
        //是否是map
        if (!$this->is_array_map($original_data)) {
            //多条数据
            $this->queryByForeignKey($this->foreign_key_val);

            $new_data_arr = [];
            foreach ($original_data as $val) {
                //组织数据,过滤不需要的字段

                if ($this->is_exists_primary_key($val)) {
                    $new_data = $this->assemblyUpdateData($val);
                    $new_data_arr[] = $new_data;
                    //初始化插入更新
                    $this->initUpdateData($new_data);
                } else {
                    //初始化插入数据
                    $new_data = $this->assemblyInsertData($val);
                    if (!empty($new_data))
                        $this->initInsertData($new_data);
                }
            }
            //初始化要删除的数据
            $this->initDeleteData($new_data_arr);
        } else {
            //单条数据
            if ($this->is_exists_primary_key($original_data)) {
                //通过主键查询
                $this->queryByPrimaryKey($original_data);
                if (empty($this->db_data)) {
                    //组织数据,过滤不需要的字段
                    $new_data = $this->assemblyInsertData($original_data);

                    $this->initInsertData($new_data);
                } else {
                    //组织数据,过滤不需要的字段
                    $new_data = $this->assemblyUpdateData($original_data);
                    //初始化插入更新
                    $this->initUpdateData($new_data);
                }
            } else {
                //组织数据,过滤不需要的字段
                $new_data = $this->assemblyInsertData($original_data);
                //初始化插入数据
                $this->initInsertData($new_data);
            }
        }
    }

    protected function initInsertDataCB(&$data)
    {
        return true;
    }

    /**
     * 初始化插入数据
     */
    private function initInsertData($data)
    {
        if ($this->is_insert) {
            if ($this->initInsertDataCB($data)) {
                $this->insert_data[] = $data;
                if (in_array('created_at', $this->fields)) {
                    $data['created_at'] = _now();
                }
                if (in_array('create_id', $this->fields)) {
                    $data['create_id'] = UserInfo::getMyId();
                }
                if (!empty($this->foreign_key_val)) {
                    $data = array_merge($data, $this->foreign_key_val);
                }
                if (!empty($this->redundant_data)) {
                    $data = array_merge($data, $this->redundant_data);
                }
                $this->_insert_data[] = $data;
                $this->changeRecords(1, $data, null);
            }
        }
    }

    protected function initUpdateDataCB(&$data, $old)
    {
        return true;
    }

    /**
     * 初始化插入更新
     */
    private function initUpdateData($data)
    {
        if ($this->is_update) {
            $data = $this->setForeignKeyVal($data);
            $data = $this->setRedundantData($data);
            foreach ($this->db_data as $val) {
                if ($this->is_equal_primary_key($val, $data)) {
                    $change = $this->contrastData($val, $data);
                    if (!empty($change)) {
                        if (!$this->initUpdateDataCB($change, $val)) {
                            continue;
                        }
                        $this->update_data[] = $change;
                        if (in_array('updated_at', $this->fields))
                            $change['updated_at'] = _now();
                        $change = array_merge($this->getPrimaryKey($val), $change);
                        $this->_update_data[] = $change;
                        $this->changeRecords(2, $change, $val);
                    }
                }
            }
        }
    }

    protected function initDeleteDataCB(&$data)
    {
        return true;
    }

    /**
     * 初始化要删除的数据
     */
    private function initDeleteData($data)
    {
        if ($this->is_delete) {
            foreach ($this->db_data as $val1) {
                $status = true;
                foreach ($data as $val2) {
                    if ($this->is_equal_primary_key($val1, $val2)) {
                        $status = false;
                        continue;
                    }
                }
                if ($status) {
                    //硬删除
                    if (!$this->initDeleteDataCB($val1)) {
                        continue;
                    }
                    $this->delete_data[] = $val1;
                    $primary_key = $this->getPrimaryKey($val1);
                    if (count($primary_key) > 1) {
                        $this->_delete_data2[] = $primary_key;
                    } else {
                        $this->_delete_data1[] = array_values($primary_key)[0];
                    }
                    $this->changeRecords(4, null, $val1);
                }
            }
        }
    }

    /**
     * 设置外键数据
     */
    private function setForeignKeyVal($data)
    {
        if (empty($this->foreign_key)) {
            return $data;
        }
        if (is_array($this->foreign_key)) {
            foreach ($this->foreign_key as $val) {
                if (empty($data[$val])) {
                    $data[$val] = $this->foreign_key_val[$val];
                }
            }
        } else {
            if (empty($data[$this->foreign_key])) {
                $data[$this->foreign_key] = $this->foreign_key_val[$this->foreign_key];
            }
        }
        return $data;
    }

    /**
     *  设置冗余数据
     */
    private function setRedundantData($data)
    {
        if (empty($this->redundant)) {
            return $data;
        }
        if (is_array($this->redundant)) {
            foreach ($this->redundant as $val) {
                if (empty($data[$val])) {
                    $data[$val] = $this->redundant_data[$val];
                }
            }
        } else {
            if (empty($data[$this->redundant])) {
                $data[$this->redundant] = $this->redundant_data[$this->redundant];
            }
        }
        return $data;
    }

    /**
     * 对比数据
     */
    private function contrastData($db_data, $data)
    {
        $change = [];
        foreach ($db_data as $key => $val) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            if ($val != null) {
                $val = strval($val);
            }
            if ($data[$key] != null) {
                $data[$key] = strval($data[$key]);
            }
            if ($val !== $data[$key]) {
                $change[$key] = $data[$key];
            }
        }
        return $change;
    }


    /**
     * 插入数据
     */
    protected function insertData()
    {
        if (empty($this->_insert_data)) {
            return true;
        }
		foreach ($this->_insert_data as $value) {
            $this->db->insert($value);
        }
        return true;
    }

    /**
     * 更新数据
     */
    protected function updateData()
    {
        if (empty($this->_update_data)) {
            return true;
        }
        foreach ($this->_update_data as $val) {
            $db = $this->getDbByPrimaryKey($val);
            $db->update($val);
        }
        return true;
    }

    protected function deleteData()
    {
        $db = $this->db;
        if (!empty($this->_delete_data1)) {
            $db->whereIn($this->primary_key, $this->_delete_data1)->delete();
        }
        if (!empty($this->_delete_data2)) {
            foreach ($this->_delete_data2 as $val) {
                $db->whereTp($val)->delete();
            }
        }
    }

    /**
     * 是否存在主键
     */
    private function is_exists_primary_key($data)
    {
        if (is_array($this->primary_key)) {
            foreach ($this->primary_key as $val) {
                if (!isset($data[$val]) || $data[$val] == '')
                    return false;
            }
        } else {
            if (!isset($data[$this->primary_key]) || $data[$this->primary_key] == '')
                return false;
        }
        return true;
    }

    private function is_equal_primary_key($data1, $data2)
    {
        if (is_array($this->primary_key)) {
            foreach ($this->primary_key as $val) {
                if ($data1[$val] != $data2[$val])
                    return false;
            }
        } else {
            if ($data1[$this->primary_key] != $data2[$this->primary_key])
                return false;
        }
        return true;
    }

    /**
     * 组织数据,过滤不需要的字段(更新)
     */
    private function assemblyUpdateData($original_data)
    {
        $new_data = [];
        foreach ($this->fields as $val) {
            if (array_key_exists($val, $original_data)) {
                if ($original_data[$val] === '') {
                    $new_data[$val] = null;
                } else {
                    $new_data[$val] = $original_data[$val];
                }
            }
        }
        return $new_data;
    }

    /**
     * 组织数据,过滤不需要的字段(插入)
     */
    private function assemblyInsertData($original_data)
    {
        $new_data = [];
        foreach ($this->fields as $val) {
            if (isset($original_data[$val]) && $original_data[$val] !== '')
                $new_data[$val] = $original_data[$val];
			
            /*else
                $new_data[$val] = null;*/
        }
        return $new_data;
    }

    /**
     * 检查是否是map
     */
    private function is_array_map($data)
    {
        if (empty($data)) {
            return false;
        }
        if (isset($data[0]) && is_array($data[0])) {
            return false;
        }
        return true;
    }
}