<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;

loc::loadMessages(__FILE__);

Class local_xml extends CModule
{
    public $MODULE_ID = "local.xml";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_CSS;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__.'/version.php');
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("xml_module_name");
        $this->MODULE_DESCRIPTION = Loc::getMessage("xml_module_desc");
        $this->PARTNER_NAME = 'Alex Noodles';
        $this->PARTNER_URI = '//github.com/otolaa/local.xml';
    }

    public function getPageLocal($page)
    {
        return str_replace('index.php', $page, Loader::getLocal('modules/'.$this->MODULE_ID.'/install/index.php'));
    }

    public function InstallFiles($arParams = [])
    {
        CopyDirFiles($this->getPageLocal('admin'), $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles($this->getPageLocal('admin'), $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION;
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
        Option::set($this->MODULE_ID, 'LXML_IBLOCK_ID', '2');
        Option::set($this->MODULE_ID, 'LXML_COUNT_ELEMENT', '5');
        Option::set($this->MODULE_ID, 'LXML_TEG_NAME', 'Одежда');
        Option::set($this->MODULE_ID, 'LXML_TEG_COMPANY', 'Инет-магазин Одежда');
        Option::set($this->MODULE_ID, 'LXML_TEG_URL', 'http://127.0.0.3');
        Option::set($this->MODULE_ID, 'LXML_F_PATCH', '/bitrix/catalog_export/local_xls_export.xml');
        $APPLICATION->IncludeAdminFile("Установка модуля ".$this->MODULE_ID, $this->getPageLocal('step.php'));
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallFiles();
        Option::delete($this->MODULE_ID); // Will remove all module variables
        $APPLICATION->IncludeAdminFile("Деинсталляция модуля ".$this->MODULE_ID, $this->getPageLocal('unstep.php'));
        return true;
    }
}