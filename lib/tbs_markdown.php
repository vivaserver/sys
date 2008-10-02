<?php
  /*
  +--------------------------------------------------------------------------
  |   PHP-Markdown 1.0.1oo plug-in for TinyButStrong 3.1.1
  |   ====================================================
  |   (c) 2006 Cristian R. Arroyo
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   LPGL License version 2.1, same as TBS
  +--------------------------------------------------------------------------
  */

  define('TBS_MARKDOWN','TinyButStrong_Markdown');
  $GLOBALS['_TBS_AutoInstallPlugIns'][] = TBS_MARKDOWN;  // Auto-install

  class TinyButStrong_Markdown extends Markdown_Parser {     
    function OnInstall() {
      return array('OnOperation');
    }
  
    function OnOperation($FieldName,&$Value,&$PrmLst,&$Source,&$PosBeg,&$PosEnd,&$Loc) {
      if ($PrmLst['ope']=='markdown') {
        $Value = $this->transform($Value);
      }
    }
  }
?>
