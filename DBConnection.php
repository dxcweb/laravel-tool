<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/16
 * Time: 20:22
 */

namespace Tool;


class DBConnection
{
    private $connection;
    private $connection_name;
    private $facadeRoot;
    private $table;

    public function __construct($facadeRoot, $connection = null)
    {
        $this->connection_name = $connection;
        $this->facadeRoot = $facadeRoot;
        $this->connection = $this->facadeRoot->connection($this->connection_name);
    }

    public function sharedLock()
    {
        $this->connection->sharedLock();
        return $this;
    }

    public function lockForUpdate()
    {
        $this->connection->lockForUpdate();
        return $this;
    }

    public function getPdo()
    {
        return $this->connection->getPdo();
    }

    public function get($columns = ['*'])
    {
        $data = $this->connection->get($columns)->all();
        if (config('database.fetch') == \PDO::FETCH_ASSOC) {
            $res = $data;
        } else {
            $res = $this->objectToArray($data);
        }
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $res;
    }

    public function table($table)
    {
        $this->table = $table;
        $this->connection = $this->connection->table($table);
        return $this;
    }

    public function selectRaw($expression, array $bindings = [])
    {
        $this->connection = $this->connection->selectRaw($expression, $bindings);
        return $this;
    }

    public function select($sql)
    {
        $data = $this->connection->select($sql);
        return $this->objectToArray($data);
    }

    public function orderBy($column, $direction = 'desc')
    {
        $this->connection = $this->connection->orderBy($column, $direction);
        return $this;
    }


    public function insertGetId(array $values)
    {
        $data = $this->connection->insertGetId($values);
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    public function insert(array $values)
    {
        $data = $this->connection->insert($values);
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    public function count()
    {
        $data = $this->connection->count();
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    public function whereTp($where = [])
    {
        foreach ($where as $key => $val) {

            $boolean = 'and';
            if (is_array($val)) {
                $operator = $val[0];
                $value = $val[1];
                if (isset($val[2]))
                    $boolean = $val[2];
            } else {
                $operator = "=";
                $value = $val;
            }
            $this->connection = $this->connection->where(trim($key), $operator, $value, $boolean);
        }
        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->connection = $this->connection->where($column, $operator, $value, $boolean);
        return $this;
    }

    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $this->connection = $this->connection->whereBetween($column, $values, $boolean, $not);
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        $this->connection = $this->connection->orWhere($column, $operator, $value);
        return $this;
    }

    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $this->connection = $this->connection->whereNull($column, $boolean, $not);
        return $this;
    }

	public function orderByRaw($column)
    {
        $this->connection = $this->connection->orderByRaw($column);
        return $this;
    }
	public function whereRaw($column)
    {
        $this->connection = $this->connection->whereRaw($column);
        return $this;
    }

    public function groupBy($data)
    {
        $this->connection = $this->connection->groupBy($data);
        return $this;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->connection = $this->connection->whereIn($column, $values, $boolean, $not);
        return $this;
    }

    public function whereNotIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->connection = $this->connection->whereNotIn($column, $values, $boolean, $not);
        return $this;
    }

    public function first($columns = ['*'])
    {
        $data = $this->connection->first($columns);
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        if (config('database.fetch') == \PDO::FETCH_ASSOC) {
            return $data;
        } else {
            return (array)$data;
        }
    }

    public function max($columns)
    {
        $data = $this->connection->max($columns);
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    public function min($columns)
    {
        $data = $this->connection->min($columns);
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
    {
        $this->connection = $this->connection->join($table, $one, $operator, $two, $type = 'inner', $where);
        return $this;
    }

    public function page($page, $pageSize)
    {
        if (isset($pageSize) && is_numeric($pageSize)) {
            $take = $pageSize;
        } else {
            $take = 10;
        }
        if (isset($page) && is_numeric($page) && $page != 0) {
            $skip = ($page - 1) * $take;
        } else {
            $skip = 1;
        }
        $this->connection = $this->connection->skip($skip)->take($take);
        return $this;
    }

    public function update($data)
    {
        $data = $this->connection = $this->connection->update($data);
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    public function delete()
    {
        $data = $this->connection = $this->connection->delete();
        $this->connection = $this->facadeRoot->connection($this->connection_name);
        $this->table($this->table);
        return $data;
    }

    private function objectToArray($e)
    {
        $e = (array)$e;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') return false;
            if (gettype($v) == 'object' || gettype($v) == 'array')
                $e[$k] = (array)$this->objectToArray($v);
        }
        return $e;
    }

    public function distinct()
    {
        $this->connection = $this->connection->distinct();
        return $this;
    }

    public function leftJoin($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
    {
        $this->connection = $this->connection->leftJoin($table, $one, $operator, $two, $type = 'inner', $where);
        return $this;
    }
    public function rightJoin($table, $one, $operator = null, $two = null, $type = 'inner', $where = false)
    {
        $this->connection = $this->connection->rightJoin($table, $one, $operator, $two, $type = 'inner', $where);
        return $this;
    }

    public function extjs()
    {
        if (isset($_GET['page']) && $_GET['pagesize'])
            $this->page($_GET['page'], $_GET['pagesize']);
        if (isset($_GET['sort'])) {
            $sort = json_decode($_GET['sort'], true);
            if (isset($sort[0]['property']) && isset($sort[0]['direction'])) {
                $this->orderBy($sort[0]['property'], $sort[0]['direction']);
            }
        }
        return $this;
    }

    public function antPage()
    {
        $p = _getInput();
        $page = 1;
        $pageSize = 10;
        if (isset($p['page'])) {
            $page = $p['page'];
        }
        if (isset($p['pageSize'])) {
            $pageSize = $p['pageSize'];
        }
        $this->page($page, $pageSize);
//        if (isset($_GET['sort'])) {
//            $sort = json_decode($_GET['sort'], true);
//            if (isset($sort[0]['property']) && isset($sort[0]['direction'])) {
//                $this->orderBy($sort[0]['property'], $sort[0]['direction']);
//            }
//        }
        return $this;
    }

    public function extjs2()
    {
        if (isset($_GET['page']) && $_GET['limit'])
            $this->page($_GET['page'], $_GET['limit']);
        if (isset($_GET['sort'])) {
            $sort = json_decode($_GET['sort'], true);
            if (isset($sort[0]['property']) && isset($sort[0]['direction'])) {
                $this->orderBy($sort[0]['property'], $sort[0]['direction']);
            }
        }
        return $this;
    }

    public function increment($key, $val)
    {
        return $this->connection = $this->connection->increment($key, $val);
    }

    public function decrement($key, $val)
    {
        return $this->connection = $this->connection->decrement($key, $val);
    }

    public function beginTransaction()
    {
        $this->connection = $this->connection->beginTransaction();
        return $this;
    }

    public function rollback()
    {
        $this->connection = $this->connection->rollback();
        return $this;
    }

    public function commit()
    {
        $this->connection = $this->connection->commit();
        return $this;
    }

}