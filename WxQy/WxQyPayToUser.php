<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/6/7
 * Time: 15:23
 */

namespace Tool\WxQy;

class WxQyPayToUser extends WxQyPayUtil
{
    /**
     * emp_id 员工id
     * check_name 校验用户姓名选项[
     *  NO_CHECK：不校验真实姓名,
     *  FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账）
     *  OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
     *  ]
     *  emp_name 员工名称 收款用户姓名 如果check_name设置为FORCE_CHECK或OPTION_CHECK，则必填用户真实姓名
     * amount 金额单位元
     * describe  企业付款描述信息  对应微信的desc
     */
    public function pay($data)
    {
        $res = $this->checkPayToUserData($data);
        if (!$res['result']) {
            return $res;
        }
        $request = $res['data'];
        $request['mchid'] = $this->mchId;
        $request['nonce_str'] = $this->createRandStr();
        $request['partner_trade_no'] = create_guid();
        $request['spbill_create_ip'] = '127.0.0.1';//支付IP白名单未开启。
        $request['sign'] = $this->getSign($request);
        return $this->_payCurl($request);
    }

    private function _payCurl($request, $retry = true)
    {
        $xml = $this->arrayToXml($request);
        $res = $this->payCurl($xml, 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers');
        $res_arr = $this->xmlToArray($res);
        if ($res_arr['return_code'] != 'SUCCESS') {
            log_file("error/wxqy/pay_to_user", "请求错误！", $xml, $res, "失败");
            return _output($res, false);
        }
        if ($res_arr['result_code'] != 'SUCCESS') {
            switch ($res_arr['err_code']) {
                case 'SYSTEMERROR':
                    if ($retry)
                        return $this->_payCurl($xml, false);
                    break;
                case 'NAME_MISMATCH':
                    $res_arr['return_msg'] = "微信认证的姓名与系统中的姓名不一致，无法转账。";
                    break;
                case 'NOTENOUGH':
                    $res_arr['return_msg'] = "余额不足，请先充值！";
                    break;
                case 'FREQ_LIMIT':
                    $res_arr['return_msg'] = "对同一个用户的转账过于频繁，请稍后重试。";
                    break;
                case 'SENDNUM_LIMIT':
                    $res_arr['return_msg'] = "对此用户的转账次数已达上限，请明天再转。";
                    break;
            }
            return _output($res_arr['return_msg'], false, $res_arr['err_code']);
        }
        $data['payment_no'] = $res_arr['payment_no'];
        $data['partner_trade_no'] = $res_arr['partner_trade_no'];
        $data['payment_time'] = strtotime($res_arr['payment_time']);
        return _output($data);
    }

    private function checkPayToUserData($data)
    {
        $request = [];
        if (empty($data['emp_id'])) {
            return _output("缺少员工ID", false);
        }
        $res = $this->getOpenid($data['emp_id'], 0);
        if (!$res['result']) {
            if ($res['errorcode'] == '43004') {
                $res['data'] = "该用户没有关注企业号,无法转账！";
            }
            return $res;
        }
        $request['openid'] = $res['data']['openid'];
        $request['mch_appid'] = $res['data']['appid'];
        if (empty($data['check_name'])) {
            return _output("缺少校验用户姓名选项", false);
        } else if ($data['check_name'] == 'NO_CHECK') {
            $request['check_name'] = 'NO_CHECK';
        } else if ($data['check_name'] == 'FORCE_CHECK' || $data['check_name'] == 'OPTION_CHECK') {
            if (empty($data['emp_name'])) {
                return _output("缺少收款用户姓名", false);
            }
            $request['check_name'] = $data['check_name'];
            $request['re_user_name'] = $data['emp_name'];
        }
        if (empty($data['amount'])) {
            return _output("缺少企业付款金额", false);
        } else {
            $request['amount'] = bcmul($data['amount'], 100);//微信单位为分。wxqy_base单位为元
        }
        if (empty($data['describe'])) {
            return _output("缺少企业付款描述信息", false);
        } else {
            $request['desc'] = $data['describe'];
        }
        return _output($request);
    }
}