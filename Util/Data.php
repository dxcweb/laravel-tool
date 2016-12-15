<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/27
 * Time: 14:00
 */

namespace Tool\Util;


class Data
{
    /**
     *    作用：array转xml
     */
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     *    作用：将xml转为array
     */
    public static function xmlToArray($xml)
    {
        //将XML转为array
        if (!is_string($xml)) {
            return null;
        }
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    public static function json_encode_cn($res)
    {
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }
}