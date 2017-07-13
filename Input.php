<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/4/15
 * Time: 21:21
 */

namespace Tool;


class Input
{
    private static $filters_data;//过滤后的数据
    private static $original_data;//原始的数据

    public static function getInput($is_filters = true)
    {
        //看原始数据有没有，没有获取
        if (!isset(self::$original_data)) {
            $original_data = self::get_original_data();
            if (config("myapp.input_check")) {
                $original_data = check_original_data($original_data);
            }
            self::$original_data = $original_data;
            self::$filters_data = self::data_filters($original_data);
        }
        if ($is_filters) {
            return self::$filters_data;
        } else {
            return self::$original_data;
        }
    }

    //设置数据
    public static function setData($data)
    {
        //看原始数据有没有，没有获取
        if (!isset(self::$original_data)) {
            $original_data = self::get_original_data();
            if (config("myapp.input_check")) {
                $original_data = check_original_data($original_data);
            }
            self::$original_data = $original_data;
            self::$filters_data = self::data_filters($original_data);
        }

        if (is_array($data)) {
            if (empty(self::$filters_data)) {
                self::$filters_data = $data;
            } else {
                self::$filters_data = array_merge(self::$filters_data, $data);
            }
            if (empty(self::$original_data)) {
                self::$original_data = $data;
            } else {
                self::$original_data = array_merge(self::$original_data, $data);
            }
        }
    }

    //获取原始数据
    private static function get_original_data()
    {
        $put_data = file_get_contents('php://input', 'r');
        if (!empty($put_data)) {
            $data = json_decode($put_data, true);
            if (is_array($data)) {
                $data = array_merge($data, $_GET);
                return $data;
            }
        }
        $input = $_REQUEST;
        if (isset($input['data'])) {
            $data = json_decode($input['data'], true);
        } else {
            $data = $input;
        }
        if (!is_array($data)) {
            $data = [];
        }
        $data = array_merge($data, $_GET);
        return $data;
    }

    private static function array_map_recursive($filter, $data)
    {
        $result = array();
        foreach ($data as $key => $val)
        {
            $result[$key] = is_array($val)
                ? self::array_map_recursive($filter, $val)
                : call_user_func($filter, $val);
        }

        return $result;
    }
    //字段过滤。防止sql注入或xss注入
    private static function data_filters($data)
    {
        $filters = 'addslashes,htmlspecialchars';
        if ($filters) {
            if (is_string($filters)) {
                if (0 === strpos($filters, '/')) {
                    if (1 !== preg_match($filters, (string)$data)) {
                        // 支持正则验证
                        return isset($default) ? $default : null;
                    }
                } else {
                    $filters = explode(',', $filters);
                }
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }

            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    if (function_exists($filter)) {
                        $data = is_array($data) ? self::array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                    } else {
                        $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                        if (false === $data) {
                            return isset($default) ? $default : null;
                        }
                    }
                }
            }
        }
        return $data;
    }
}