<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight("local.xml") > "D") {

    require_once(Loader::getLocal('modules/local.xml/prolog.php'));

    // the types menu  dev.1c-bitrix.ru/api_help/main/general/admin.section/menu.php
    $aMenu = [
        "parent_menu" => "global_menu_settings", // global_menu_content - раздел "Контент" global_menu_settings - раздел "Настройки"
        "section" => "local.xml",
        "sort" => 350,
        "module_id" => "local.xml",
        "text" => 'Экспорт XML',
        "title"=> 'Модуль для добавления элементов в XMl файл',
        "icon" => "fileman_menu_icon", // sys_menu_icon bizproc_menu_icon util_menu_icon
        "page_icon" => "fileman_menu_icon", // sys_menu_icon bizproc_menu_icon util_menu_icon
        "items_id" => "menu_local_xml",
        "items" => [
            [
                "text" => 'Настройки',
                "title" => 'Настройки',
                "url" => "settings.php?mid=local.xml&lang=".LANGUAGE_ID,
            ],
            [
                "text" => 'Экспорт',
                "title" => 'Экспорт',
                "url" => "local_xls_export.php?lang=".LANGUAGE_ID,
            ],
        ]
    ];

    return $aMenu;
}

return false;