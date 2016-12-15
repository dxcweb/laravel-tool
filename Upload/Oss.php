<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/4
 * Time: 23:57
 */

namespace Tool\Upload;


use OSS\OssClient;

class Oss
{
    private $config;
    private $error;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function checkSavePath($path)
    {
        return true;
    }

    public function mkdir($path)
    {
        return true;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 保存指定文件
     * @param  array $file 保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save($file, $replace = true)
    {
        $config = $this->config;
        if (empty($config['access_id']) || empty($config['access_key']) || empty($config['host']) || empty($config['bucket'])) {
            $this->error = 'OSS配置错误';
            return false;
        }
        $ossClient = new OssClient($config['access_id'], $config['access_key'], $config['host']);

        $object = $file['savepath'] . $file['savename'];
        $ossClient->uploadFile($config['bucket'], $object, $file['tmp_name']);
        return true;
    }
}