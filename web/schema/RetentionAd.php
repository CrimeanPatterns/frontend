<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 14.07.15
 * Time: 13:54
 */

require_once __DIR__."/../lib/classes/TBaseSchema.php";
class TRetentionAdSchema extends TBaseSchema {

    function TRetentionAdSchema(){
        global $arProviderKind;
        parent::TBaseSchema();
        $this->TableName = "SocialAd";
        $this->Fields = array(
            "Name" => array(
                "Type" => "string",
                "Size" => 80,
                "Required" => True,
                "InputAttributes" => "style=\"width: 800px;\"",
                "RegExp" => NO_RUSSIAN_REGEXP,
                "RegExpErrorMessage" => 'Possible russian letters in this field',
            ),
            "Content" => array(
                "Type" => "string",
                "Size" => 4000,
                "Required" => False,
                "HTML" => true,
                "RequiredGroup" => "content",
                "InputType" => "htmleditor",
                "RegExp" => NO_RUSSIAN_REGEXP,
                "RegExpErrorMessage" => 'Possible russian letters in this field',
            ),
            "BeginDate" => array(
                "Type" => "date",
                "Required" => False,
            ),
            "EndDate" => array(
                "Type" => "date",
                "Required" => False,
            ),
        );
    }

    function TuneList(&$list){
        parent::TuneList($list);

        $arSQLFields = array();
        foreach ($list->Fields as $sField => $arField )
            if( ArrayVal( $arField, "Database", True ) &&  $sField != "AdStatus")
                $arSQLFields[] = $sField;
        $list->SQL = "
        SELECT
            ".implode( ", ", $arSQLFields ).",
            IF(
                (NOW() >= BeginDate AND NOW() <= EndDate OR NOW() >= BeginDate AND EndDate IS NULL)
                , 1, 0) AS AdStatus
        FROM
            SocialAd sa
        WHERE
			1 = 1
		AND sa.Kind = ".ADKIND_RETENTION."
			[Filters]
        GROUP BY SocialAdID
        ";
    }

    function TuneForm(\TBaseForm $form){
        parent::TuneForm($form);
        $form->SQLParams["Kind"] = ADKIND_RETENTION;
    }

    function GetFormFields() {
        $arFields = parent::GetFormFields();
        ArrayInsert($arFields, "Content", true, array("Preview" => array(
            "Type" => "string",
            "InputType" => "html",
            "Database" => false,
            "HTML" => "<input type='button' value='Send Preview to my email' onclick='sendPreview($(this)); return false;'>
			<script>
			function sendPreview(elem){
			    elem.attr('disabled','disabled');
                $.ajax({
                    url: '/manager/sendRetention',
                    type: 'post',
                    data: elem.closest('form').serialize(),
                    success: function(data){
                        if(data.answer === undefined)
			                try {
                                JSON.parse(data);
                            } catch(e){
                                alert('Server error');
                            }

                        alert(data.answer);
			            elem.removeAttr('disabled');
			        },
                    error: function(){
			            alert('Server error');
			            elem.removeAttr('disabled');
			        }
                });
            }
			</script>",
        )));

        return $arFields;
    }
}
