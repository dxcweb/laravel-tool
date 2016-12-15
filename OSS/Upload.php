<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/19
 * Time: 17:44
 */

namespace Tool\OSS;


class Upload extends Oss
{
    public function simple_upload($object,$file,$options=null)
    {
        if(!file_exists($file))
        {
            _pack("找不到文件！",false);
        }
        $bucket=$this->bucket;
        $this->common_check($bucket,$object,$options);
        $options['fileUpload']=$file;
        $options['Content-Length']=filesize($file);
        $options['Content-Type']=mime_content_type($file);
        $options['method']='PUT';
        $options['bucket']=$bucket;
        $options['object']=$object;

    }

}