<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 2017/10/26
 * Time: 09:56
 */

namespace Tool\Aliyun;

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;

Config::load();

class Sms
{
    public static function send($data, $config)
    {
        if (empty($config['access_key_id'])) {
            return _output('缺少配置access_key_id', false);
        }
        if (empty($config['access_key_secret'])) {
            return _output('缺少配置access_key_secret', false);
        }
        if (empty($config['template_code'])) {
            return _output('缺少配置template_code', false);
        }
        if (empty($config['sign_name'])) {
            return _output('缺少配置sign_name', false);
        }
        if (empty($config['phone'])) {
            return _output('缺少配置phone', false);
        }
        //短信API产品名
        $product = "Dysmsapi";
        //短信API产品域名
        $domain = "dysmsapi.aliyuncs.com";
        //暂时不支持多Region
        $region = "cn-hangzhou";

        //初始化访问的acsCleint

        $profile = DefaultProfile::getProfile($region, $config['access_key_id'], $config['access_key_secret']);
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
        $acsClient = new DefaultAcsClient($profile);

        $request = new SendSmsRequest();
        //必填-短信接收号码
        $request->setPhoneNumbers($config['phone']);
        //必填-短信签名
        $request->setSignName($config['sign_name']);
        //必填-短信模板Code
        $request->setTemplateCode($config['template_code']);
        //选填-假如模板中存在变量需要替换则为必填(JSON格式)
        if ($data) {
            $request->setTemplateParam(json_encode($data));
        }
        $outId = null;
        //选填-发送短信流水号
        if ($outId) {
            $request->setOutId($outId);
        }

        //发起访问请求
        $res = $acsClient->getAcsResponse($request);
        if (empty($res->Code) || $res->Code !== 'OK') {
            return _output('验证码发送失败', false);
        } else {
            return _output();
        }
    }
}