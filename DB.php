<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/16
 * Time: 17:19
 */

namespace Tool;

class DB extends \Illuminate\Support\Facades\DB
{
    static public function connection($name = null)
    {
        return new DBConnection(self::getFacadeRoot(),$name);
    }
    static public function select($sql)
    {
        $DBConnection = new DBConnection(self::getFacadeRoot());
        return $DBConnection->select($sql);
    }
    static public function table($table)
    {
        $DBConnection = new DBConnection(self::getFacadeRoot());
        $DBConnection->table($table);
        return $DBConnection;
    }

    static public function enableQueryLog()
    {
        self::getFacadeRoot()->connection()->enableQueryLog();
    }

    static public function getQueryLog()
    {
        return self::getFacadeRoot()->connection()->getQueryLog();
    }

    /**
     *  开始事务
     */
    static public function beginTransaction()
    {
        return self::getFacadeRoot()->connection()->beginTransaction();
    }

    /**
     *  回滚
     */
    static public function rollback()
    {
        return self::getFacadeRoot()->connection()->rollback();
    }

    /**
     * 提交
     */
    static public function commit()
    {
        return self::getFacadeRoot()->connection()->commit();
    }
}