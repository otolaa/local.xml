<?php

namespace Local\Xml;

use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Application;

/**
 * Class Api
 * @package Local\Xml
 */
class Api
{
    public $MODULE_ID = "local.xml";
    public $IBLOCK_ID_ARR;
    public $f_patch;
    public $teg_name;
    public $teg_company;
    public $teg_url;

    public function __construct()
    {
        $this->teg_name = Option::get("local.xml", "LXML_TEG_NAME");
        $this->teg_company = Option::get("local.xml", "LXML_TEG_COMPANY");
        $this->teg_url = Option::get("local.xml", "LXML_TEG_URL");
        $this->f_patch = Option::get("local.xml", "LXML_F_PATCH");

        $IBLOCK_ARR = [];
        foreach (explode(',', Option::get("local.xml", "LXML_IBLOCK_ID")) as $id)
            $IBLOCK_ARR[]= intval($id);

        $this->IBLOCK_ID_ARR = $IBLOCK_ARR;
    }

    public function setFile()
    {
        $str = '<yml_catalog date="'.date("Y-m-d H:i:s",time()).'"><shop><name>'.$this->teg_name.'</name><company>'.$this->teg_company.'</company><url>'.$this->teg_url.'</url><platform>1C-Bitrix</platform>';
        $fd = fopen($_SERVER["DOCUMENT_ROOT"].$this->f_patch, 'w') or die();
        fwrite($fd, $str.PHP_EOL);
        fclose($fd);
    }

    public function addXmlTeg($str, $transfer=false)
    {
        $fd = fopen($_SERVER["DOCUMENT_ROOT"].$this->f_patch, 'a') or die();
        fwrite($fd, $str.($transfer?PHP_EOL:''));
        fclose($fd);
    }

    public function cleanText($DETAIL_TEXT)
    {
        $a = trim(str_replace(["\r\n", "\r", "\n", "&ndash;"], '', strip_tags($DETAIL_TEXT)));
        $a = str_replace('  ', ' ',$a);
        return $a;
    }
}