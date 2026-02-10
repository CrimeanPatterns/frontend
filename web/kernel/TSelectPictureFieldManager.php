<?php


class TSelectPictureFieldManager extends TAbstractFieldManager
{
    function InputHTML($sFieldName = null, $arField = null)
    {
        $aircrafts = TAircraftSchema::getAircraftsIcons();

        $html = "<div><select name='{$this->FieldName}' id='selector-select'>";
        $selector = "<div id='selector' style='margin-top: 10px'>";
        foreach ($aircrafts as $aircraft) {
            $selected = $this->Field['Value'] == $aircraft ? ' selected' : '';
            $html .= "<option value='{$aircraft}'{$selected}>{$aircraft}</option>";
            $selector .= "<i class='{$aircraft}' data-name='{$aircraft}' style='margin-right: 10px'></i>";
        }

        $html .= "</select>";
        $html .= $selector . '</div>';

        $html .= "
            <script>
                $(function() {
                    var images = $('#selector i');
                    $('#selector-select').on('change', function(e){
                        $(images).css('background-color', 'white');
                        var selected = $(e.target).find('option:selected');
                        $('#selector i[data-name=' + $(selected).val() + ']').css('background-color', 'lightblue');
                    });

                    $(images).on('click', function(e) {
                        var val = $(e.target).data('name');
                        $('#selector-select').find('option').removeAttr('selected');
                        $('#selector-select').find('option[value=' + val + ']').attr('selected', 'select');
                        $('#selector-select').trigger('change');
                    });
                    $('#selector-select').trigger('change');
                })
            </script>
        ";

        return $html;
    }
}