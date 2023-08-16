<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Local\Xml\Api;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\SystemException;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

// подключим языковой файл
Loc::loadMessages(__FILE__);
//
if(!Loader::includeModule("iblock")) return;
if(!Loader::includeModule("local.xml")) return;

// получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight("local.xml");
// если нет прав - отправим к форме авторизации с сообщением об ошибке
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = [
    ["DIV" => "edit1", "TAB" => 'Экспорт', "ICON"=>"main_user_edit", "TITLE"=>'Параметры экспорта'],
];
$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

//
$xml = new Api();
$IBLOCK_ID_ARR = $xml->IBLOCK_ID_ARR;

$queue = intval(Option::get("local.xml", "LXML_COUNT_ELEMENT", 10));
$arError = [];
$bShowRes = false;
$FORMAT = "text";
$sheet_arr = 0;
$sheet_count = 0;

//
if ($_REQUEST['process'] == "Y") {

    if (!check_bitrix_sessid())
        RaiseErrorAndDie(GetMessage("DUMP_MAIN_SESISON_ERROR"));

    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

    $NS =& \Bitrix\Main\Application::getInstance()->getSession()['BX_FID_STATE'];
    if ($_REQUEST['action'] == 'start') {
        $NS = [];
        $NS['finished_steps'] = 0;
        $NS['fid_state'] = '';
        $NS['format'] = $_REQUEST['format'];
        $NS['time_sleep'] = intval($_REQUEST['time_sleep'])>=1?intval($_REQUEST['time_sleep']):1;
        $NS['queue'] = intval($_REQUEST['queue'])>=5?intval($_REQUEST['queue']):$queue;
        $NS["step"] = 1;

        $xml->setFile(); // создаем файл

        // add teg categories
        $arFilterB = ['IBLOCK_ID' => $IBLOCK_ID_ARR, 'GLOBAL_ACTIVE'=>'Y', 'ACTIVE'=>'Y'];
        $arSelectB = ['ID','CODE','IBLOCK_SECTION_ID','IBLOCK_ID','NAME','ACTIVE','SECTION_PAGE_URL'];
        $rsSect = CIBlockSection::GetList(['left_margin' => 'asc'], $arFilterB, false, $arSelectB);
        $ct = '<categories>';
        while ($arSect = $rsSect->GetNext())
            $ct .= '<category id="'.$arSect['ID'].'" '.($arSect['IBLOCK_SECTION_ID']?'parentId="'.$arSect['IBLOCK_SECTION_ID'].'"':'').' url="'.$xml->teg_url.$arSect['SECTION_PAGE_URL'].'">'.$arSect['NAME'].'</category>';

        $ct .= '</categories>';
        $xml->addXmlTeg($ct, true); // add && end <categories></categories>
        $xml->addXmlTeg('<offers>', true); // add start teg <offers>
    }

    // если выполняется условие, то тогда ++
    if (true === true) {
        // $NS['queue'] - очередь количество элементов
        $arSelect = ["ID", "NAME", "CODE", "IBLOCK_ID", "IBLOCK_SECTION_ID", "DETAIL_PICTURE", "CATALOG_PRICE_1", "DETAIL_TEXT"];
        $arFilter = ["IBLOCK_ID"=>$IBLOCK_ID_ARR, "ACTIVE"=>"Y"];
        $rsData = CIBlockElement::GetList(['id'=>'acs'], $arFilter, false, ["nPageSize"=>$NS['queue'], "iNumPage"=>$NS["step"]], $arSelect);

        $offer = '';
        $sheet_count = $rsData->NavRecordCount;
        $sheet_arr =  $rsData->NavPageCount;
        while ($rows = $rsData->GetNext()) {
            $offer .= '<offer><id>'.$rows['ID'].'</id><categoryId>'.$rows['IBLOCK_SECTION_ID'].'</categoryId><name>'.$rows['NAME'].'</name>                  
                        '.($rows['DETAIL_PICTURE']?'<picture>'.$xml->teg_url.CFile::GetPath($rows['DETAIL_PICTURE']).'</picture>':'').'
                        <currencyId>'.$rows['CATALOG_CURRENCY_1'].'</currencyId>
                        <price>'.number_format($rows['CATALOG_PRICE_1'], 0, '.', '').'</price>
                        <description>'.$xml->cleanText($rows['~DETAIL_TEXT']).'</description></offer>';
        }

        $xml->addXmlTeg($offer, true);

        $NS["step"]++;
    }

    CAdminMessage::ShowMessage([
        "MESSAGE"=>"Экспорт в файл",
        "DETAILS"=> "#PROGRESS_BAR#",
        "HTML"=>true,
        "TYPE"=>"PROGRESS",
        "PROGRESS_TOTAL" => $sheet_arr,
        "PROGRESS_VALUE" => $NS["step"],
    ]);

    if ($NS["step"] <= $sheet_arr) { ?>
        <script>
            window.setTimeout("if(!stop)AjaxSend('?process=Y&<?=bitrix_sessid_get()?>')",<?= 1000*$NS['time_sleep'] ?>);
        </script>
    <? } else {
        $xml->addXmlTeg('</offers>', true); // закрываем тэг offers
        $xml->addXmlTeg('</shop></yml_catalog>'); // закрываем тэг файл xml
        $status_msg = '';
        $status_msg .= 'Количество очередей '.$sheet_arr.'<br>';
        $status_msg .= 'Количество записей '.$sheet_count;

        CAdminMessage::ShowMessage(array(
            "MESSAGE" => 'Экспорт зашивершился:',
            "DETAILS" => $status_msg,
            "TYPE" => "OK",
            "HTML" => true));
        ?>
       <script>
           BX('start_button').disabled=false;
       </script>
    <? }
    require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
    die();
}

//
$APPLICATION->SetTitle('Экспорт в файл xml');
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
// error
if(count($arError)>0)
{
    $e = new CAdminException($arError);
    $message = new CAdminMessage("Ошибка экспорта:", $e);
    echo $message->Show();
}

// div from message
?><div id="dump_result_div"></div>

<? // button +5
$aMenu = [
    [
        "TEXT"	=> 'Файл xml экспорта',
        "LINK"	=> "../..".$xml->f_patch,
        "TITLE"	=> '',
        "ICON"	=> "btn_list"
    ]
];
$context = new CAdminContextMenu($aMenu);
$context->Show(); ?>

<form ENCTYPE="multipart/form-data" action="<?= $APPLICATION->GetCurPage() ?>"
      method="GET" name="attr1">
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr class="heading">
        <td colspan="2"><?= 'Экспорт в файл xml' ?></td>
    </tr>
    <tr>
        <td><?= 'Очередь:' ?></td>
        <td><input type="text" name="queue" value="<?=$queue?>" size="2"> записей</td>
    </tr>
    <tr>
        <td><?= 'Интервал экспорта:' ?></td>
        <td><input type="text" name="time_sleep" value="1" size="2"> сек. </td>
    </tr>
    <tr>
        <td><?= 'Формат:' ?></td>
        <td><input id="format_id" name="format" type="radio" value="text"<?if($FORMAT == "text") echo " checked"?>><label for="format_id"><?= 'Текст' ?></label>&nbsp;/<input id="format_2" name="format" type="radio" value="html"<?if($FORMAT == "html") echo " checked"?>><label for="format_2">HTML</label></td>
    </tr>
    <?
    $tabControl->Buttons();
    ?>
    <input id="start_button" class="adm-btn-save" type="button" name="Import" value="<?= 'Экспорт'?>" OnClick="StartDump();">
    <input type="hidden" name="lang" value="<?= LANG?>">
    <?
    $tabControl->End();
    ?>
</form>

    <script type="text/javascript">
        var stop;
        var counter_started = false;
        var counter_sec = 0;

        const StartDump = () => {
            counter_sec = 0;
            var queryString = 'lang=<?= htmlspecialcharsbx(LANGUAGE_ID)?>&process=Y&action=start';
            queryString += `&format=${document.attr1.format.value}`;
            queryString += `&time_sleep=${document.attr1.time_sleep.value}`;
            queryString += `&queue=${document.attr1.queue.value}`;
            queryString += '&<?=bitrix_sessid_get()?>';

            BX('dump_result_div').innerHTML='';
            AjaxSend('local_xls_export.php', queryString);
            window.scrollTo(0, 0);
        }

        const StartCounter = () => { counter_started = true; }

        const StopCounter = (result) => {
            counter_started = false;
            // console.log(result);
        }

        const EndDump = (conditional) => {
            // BX('stop_button').disabled = stop = true;
        }

        const AjaxSend = (url, data) => {
            stop = false;
            BX('start_button').disabled=true;

            StartCounter();
            CHttpRequest.Action = function(result)
            {
                StopCounter(result);
                if (stop) {
                    EndDump();
                    BX('dump_result_div').innerHTML = '';
                } else
                    BX('dump_result_div').innerHTML = result;
            };
            if (data)
                CHttpRequest.Post(url, data);
            else
                CHttpRequest.Send(url);
        }

    </script>

<? // информационная подсказка
//echo BeginNote();
//echo EndNote();?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>