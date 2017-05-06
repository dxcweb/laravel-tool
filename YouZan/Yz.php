<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 17/5/4
 * Time: 下午8:41
 */

namespace Tool\YouZan;


class Yz
{
    public static function handle($method, $params = array())
    {
        static $yz;
        if (empty($yzBase)) {
            $yz = new YzBase(env('YZ_APP_ID'), env('YZ_APP_SECRET'));
        }
        return $yz->get($method, $params);
    }
}