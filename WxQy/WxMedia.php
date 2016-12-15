<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/1
 * Time: 20:14
 */

namespace Tool\WxQy;


use Tool\Wx\WxExecute;

class WxMedia extends WxExecute
{
    /**
     * 素材下载图片
     */
    public function downloadImg($media_id, $reload = false)
    {
        $uri = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=ACCESS_TOKEN&media_id=$media_id";
        $url = $this->getRequestUrl($uri, $reload);
        $data = $this->getImage($url);
        if ($data['result']) {
            return $data;
        } else {
            $check_data = $this->checkData($data['data']);
            if ($reload) {
                //重发
                if ($this->isInvalidAccessToken($check_data['errorcode'])) {
                    //无效AccessToken重发
                    $content = "重发后ACCESS_TOKE一样失败！";
                } else {
                    $content = "重发后失败！";
                }
                log_file("error/wx/media_download_img", "素材下载图片", $data, $check_data['data'], $uri, $content);
                return $check_data;
            } else {
                if ($this->isInvalidAccessToken($check_data['errorcode'])) {
                    return $this->downloadImg($media_id, true);
                } else {
                    //有错误！
                    log_file("error/wx/media_download_img", "素材下载图片", $data, $check_data['data'], $uri, "失败！");
                    return $check_data;
                }
            }
        }
    }

    private function getImage($url, $path = "./php/storage/wxCache/qy/img/")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);//设置URL
        curl_setopt($ch, CURLOPT_POST, 1);//post
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');//传递一个作为HTTP “POST”操作的所有数据的字符串
        curl_setopt($ch, CURLOPT_HEADER, 1);//返回response头部信息
        curl_setopt($ch, CURLOPT_NOBODY, 0);//不返回response body内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不直接输出response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//如果返回的response 头部中存在Location值，就会递归请求
        $response = curl_exec($ch);

        if (!$response) {
            return _output(curl_error($ch), false);
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            //$header = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
        } else {
            //错误
            return _output($response, false);
        }

        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $path .= date("Y-m-d") . '/';
        switch ($content_type) {
            case "image/jpeg":
                $path .= create_guid() . '.jpg';
                break;
            case "image/png":
                $path .= create_guid() . '.png';
                break;
            default:
                return _output($body, false);
        }
        mkDirs(dirname($path));
        curl_close($ch);//关闭
        $fp = @fopen($path, 'a');
        fwrite($fp, $body);
        fclose($fp);
        return _output($path);
    }
}