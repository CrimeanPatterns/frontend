<?php

class NDInterface extends TInterface {

	function Init(){
		TBaseInterface::Init();
		$this->Skin = new NDSkin();
		$this->Skin->init();
	}

	public static function enabled(){
		$tokenStorage = getSymfonyContainer()->get('aw.security.token_storage');
		$authorizationChecker = getSymfonyContainer()->get('security.authorization_checker');
		$user = $tokenStorage->getUser();
		if(empty($user))
				return true;
		else
			return $authorizationChecker->isGranted('SITE_ND_SWITCH');
	}

	function DrawBeginBox($boxWidth = 400, $header = null, $closable = true, $classes = null, $closeButton = true){
		if(is_integer($boxWidth)) // old style call
			$boxWidth = "style='width: {$boxWidth}px;'";
		?>
		<div <?=$boxWidth?> class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-draggable ui-resizable ui-dialog-buttons<? if(isset($classes)) echo " ".$classes; ?>"
		role="dialog">
		<div>
		<? if(!empty($header) || $closable) { ?>
		<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix"><span id="ui-id-1" class="ui-dialog-title"><?=$header?>&nbsp;</span><? if($closable) { ?><button class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close" role="button" aria-disabled="false" title="close" onclick="cancelPopup(); return false;"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span><span class="ui-button-text">close</span></button><? } ?></div>
		<? }
		echo '<div class="ng-isolate-scope ui-dialog-content ui-widget-content">';
	}

	function DrawEndBox(){
		?>
		<div class='clear'></div>
		</div></div></div>
		<?
	}

	// draws button
	// sType: submit, button; sName: input name; sTitle: button title;
	// sAttrs: additional tag attributes
	// sColor: color scheme, Red or Blue
	function DrawButton($caption, $attr, $size=0, $buttonAttr = null, $type = 'submit', $class = 'btn-blue'){
		return '<button type="' . $type . '" class="' . $class . '" '.$attr." ".$buttonAttr.($size > 0?" style=\"width: {$size}px;\"":"").'>'.htmlspecialchars($caption).'</button>';
	}

    function FixDatepicker() {
        $this->FooterScripts[] = "
            $.datepicker._updateDatepicker_original = $.datepicker._updateDatepicker;
            $.datepicker._updateDatepicker = function (inst) {
                $.datepicker._updateDatepicker_original(inst);
                $('.ui-datepicker select').wrap('<div class=\"styled-select\"></div>');
            };
        ";
    }

}