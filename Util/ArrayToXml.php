<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/27
 * Time: 17:03
 */

namespace Tool\Util;


class ArrayToXml
{
    private $version;
    private $encoding;
    private $start_element_prefix;
    private $start_element;
    private $start_element_uri;
    private $xml;

    public function __construct($start_element = 'xml', $start_element_uri = null, $start_element_prefix = null, $version = '1.0', $encoding = 'UTF-8')
    {
        $this->version = $version;
        $this->encoding = $encoding;
        $this->start_element_prefix = $start_element_prefix;
        $this->start_element = $start_element;
        $this->start_element_uri = $start_element_uri;
        $this->xml = new \XmlWriter();
    }

    public function toXml($data)
    {
        $this->xml->openMemory();
        $this->xml->startDocument($this->version, $this->encoding);
        $this->xml->startElementNS($this->start_element_prefix, $this->start_element, $this->start_element_uri);
        $this->_toXml($data);
        $this->xml->endElement();
        return $this->xml->outputMemory(true);
    }

    private function _toXml($data)
    {
        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $this->xml->startElement($key);
                $this->_toXml($value);
                $this->xml->endElement();
                continue;
            }
            $this->xml->writeElement($key, $value);
        }
    }
}