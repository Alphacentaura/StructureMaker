<?
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/events_log.txt");
define('IBLOCK_PROJECTS', 1);
define('IBLOCK_RESPONSIBLES', 2);
define('IBLOCK_FINANCE', 3);
define('IBLOCK_INDICATORS', 4);
define('IBLOCK_RESULTS', 5);
define('IBLOCK_MO', 6);
define('IBLOCK_OBJECTS', 7);
define('IBLOCK_TARGETS', 8);

AddEventHandler("iblock", "OnAfterIBlockSectionAdd", Array("StructureMaker", "OnAfterIBlockSectionAddHandler"));
AddEventHandler("iblock", "OnAfterIBlockSectionUpdate", Array("StructureMaker", "OnAfterIBlockSectionUpdateHandler"));
AddEventHandler("iblock", "OnBeforeIBlockSectionDelete", Array("StructureMaker", "OnBeforeIBlockSectionDeleteHandler"));

AddEventHandler("iblock", "OnAfterIBlockElementAdd", Array("StructureMaker", "OnAfterIBlockElementAddHandler"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("StructureMaker", "OnAfterIBlockElementUpdateHandler"));
AddEventHandler("iblock", "OnBeforeIBlockElementDelete", Array("StructureMaker", "OnBeforeIBlockElementDeleteHandler"));

class StructureMaker
{
    static $linkedIblocks = array(IBLOCK_FINANCE, IBLOCK_INDICATORS,IBLOCK_RESULTS,IBLOCK_OBJECTS,IBLOCK_TARGETS);

	private static function checkLinkedIblocks($iblocksArray) {
		foreach ($iblocksArray as $iblock) {
			yield $iblock;
		};
	}
	
	private static function exceptionShow($ex) {
		global $APPLICATION;
		$APPLICATION->throwException($ex);
	}

	private static function addSection($IBLOCK_ID, $fields) {
		if(CModule::IncludeModule("iblock")) {
			$SECTION_ID = false;
			if($fields['IBLOCK_SECTION']) {
				$rsSections = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'UF_NAT_PROJECT' => $fields['IBLOCK_SECTION'][0]), false, Array("ID"));
				if ($arSection = $rsSections->GetNext()) {
					$SECTION_ID = $arSection['ID'];
				} else {
					// not created yet
					$natSections = CIBlockSection::GetList(array(), array('IBLOCK_ID' => IBLOCK_PROJECTS, 'ID' => $fields['IBLOCK_SECTION'][0]), false, Array("NAME"));
					if ($natSection = $natSections->GetNext()) {
						$bs = new CIBlockSection;
						$arFields = Array("ACTIVE" => 'Y',"IBLOCK_SECTION_ID" => false,"IBLOCK_ID" => $IBLOCK_ID, "NAME" => $natSection['NAME'], "UF_NAT_PROJECT" => $SECTION_ID);
						$SECTION_ID = $bs->Add($arFields);
						$res = ($SECTION_ID>0);
						if(!$res) {
							self::exceptionShow($bs->LAST_ERROR);
							return false;
						}
					}
				}
				
				// creating second level section (for regional projects)
				$arFields = Array("IBLOCK_SECTION_ID" => $SECTION_ID,"IBLOCK_ID" => $IBLOCK_ID, 'UF_REG_PROJECT' => $fields['ID']);
				$rsSections = CIBlockSection::GetList(array(), $arFields, false, Array("ID","NAME"));
				if ($arSection = $rsSections->GetNext()) {
					// update existing section
					$bs = new CIBlockSection;
					$arFields["NAME"] = $fields['NAME'];
					$arFields = Array("IBLOCK_SECTION_ID" => $SECTION_ID,"IBLOCK_ID" => $IBLOCK_ID,"NAME" => $fields['NAME'],'UF_REG_PROJECT' => $fields['ID']);
					$res = $bs->Update($arSection['ID'], $arFields);
					if(!$res) {
						self::exceptionShow($bs->LAST_ERROR);
						return false;
					}
				} else {
					$bs = new CIBlockSection;
					$arFields["NAME"] = $fields['NAME'];
					$NEW_ID = $bs->Add($arFields);
					$res = ($NEW_ID>0);
					if(!$res) {
						self::exceptionShow($bs->LAST_ERROR);
						return false;
					}
				}
			} else {
				// creating first level (national projects)
				$arFields = Array("IBLOCK_SECTION_ID" => false, "IBLOCK_ID" => $IBLOCK_ID, 'UF_NAT_PROJECT' => $fields['ID']);
				$rsSections = CIBlockSection::GetList(array(), $arFields, false, Array("ID","NAME"));
				if ($arSection = $rsSections->GetNext()) {
					// update existing section
					$bs = new CIBlockSection;
					$arFields["NAME"] = $fields['NAME'];
					$res = $bs->Update($arSection['ID'], $arFields);
					if(!$res) {
						self::exceptionShow($bs->LAST_ERROR);
						return false;
					}
				} else {
					$bs = new CIBlockSection;
					$arFields["NAME"] = $fields['NAME'];
					$NEW_ID = $bs->Add($arFields);
					$res = ($NEW_ID>0);
					if(!$res) {
						self::exceptionShow($bs->LAST_ERROR);
						return false;
					}
				}
			}
		}
	}
	
	function OnAfterIBlockSectionAddHandler(&$arFields)
    {
		// Create linked sections in all iblocks
        if($arFields['IBLOCK_ID'] != IBLOCK_PROJECTS)
			return true;

		foreach (self::checkLinkedIblocks(self::$linkedIblocks) as $iblock) {
			self::addSection($iblock, $arFields);
		};
    }
	
    function OnAfterIBlockSectionUpdateHandler(&$arFields)
    {
        if($arFields['IBLOCK_ID'] != IBLOCK_PROJECTS)
			return true;

        foreach (self::checkLinkedIblocks(self::$linkedIblocks) as $iblock) {
			self::addSection($iblock, $arFields);
		};
    }
	
	function OnBeforeIBlockSectionDeleteHandler($ID)
    {
        // TODO ? Delete all links in all iblocks
		return true;
    }
	
	function OnAfterIBlockElementAddHandler(&$arFields)
    {
        if($arFields['IBLOCK_ID'] != IBLOCK_PROJECTS)
			return true;
        foreach (self::checkLinkedIblocks(self::$linkedIblocks) as $iblock) {
			self::addSection($iblock, $arFields);
		};
    }
	
	function OnAfterIBlockElementUpdateHandler(&$arFields)
    {
        if($arFields['IBLOCK_ID'] != IBLOCK_PROJECTS)
			return true;
			
        foreach (self::checkLinkedIblocks(self::$linkedIblocks) as $iblock) {
			self::addSection($iblock, $arFields);
		};
    }
	
	function OnBeforeIBlockElementDeleteHandler($ID)
    {
        // TODO ? Delete all links in all iblocks
		return true;
    }
}
?>