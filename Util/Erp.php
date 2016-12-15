<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/7/5
 * Time: 21:51
 */

namespace Tool\Util;


class Erp
{
    static public function getFatReqhead()
    {
        $erp = _erpUserInfo();
        $reqhead = [
            'opreate_id' => $erp['emp_id'],
            'dept_id' => $erp['dept_id'],
        ];
        return $reqhead;
    }

    /**
     * 检查冯上泽返回的数据
     */
    public static function checkFsz($res, $prefix, $operation_name, $request_data)
    {
        if (!$res['result']) {
            _output($operation_name . ',请求失败', false);
            return false;
        }
        $return_data = json_decode($res['data'], true);
        if (!$return_data['result']) {
            if (empty($return_data['msg']) || !is_string($return_data['msg'])) {
                $msg = '输出格式错误';
            } else {
                $msg = $return_data['msg'];
                //冯上泽的errorcode转换
                //1：已经删除
                if (!empty($return_data['data']['code']) && $return_data['data']['code'] == 1) {
                    //6开头的都是与冯上泽的接口
                    return _output($msg, false, 6000);
                }
            }
            log_file($prefix, $operation_name, $request_data, $res['data'], '失败');
            Http::notice($prefix, $operation_name, $msg, '失败');
            return _output($msg, false);
        }
        return _output($return_data);
    }
}