<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/4
 * Time: 23:57
 */

namespace Tool\Upload;


class Local
{
    public function checkSavePath($path)
    {
        mkdirs($path);
        return true;
    }

    public function mkdir($path)
    {
        mkdirs($path);
        return true;
    }

    /**
     * 保存指定文件
     * @param  array $file 保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save($file, $replace = true)
    {
        $filename = $file['savepath'] . $file['savename'];
        /* 不覆盖同名文件 */
        if (!$replace && is_file($filename)) {
            $this->error = '存在同名文件' . $file['savename'];
            return false;
        }

        /* 移动文件 */
        if (!move_uploaded_file($file['tmp_name'], $filename)) {
            $this->error = '文件上传保存错误！';
            return false;
        }
        return true;
    }
}