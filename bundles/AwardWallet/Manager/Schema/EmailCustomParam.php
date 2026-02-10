<?php

namespace AwardWallet\Manager\Schema;

class EmailCustomParam extends \TBaseSchema
{
    public function TuneList(&$list): void
    {
        parent::TuneList($list);

        $list->ShowExport = false;
        $list->ShowImport = false;
    }

    public function GetFormFields(): array
    {
        $result = parent::GetFormFields();
        unset($result['UpdateDate']);

        $result['Type']['Options'] = \AwardWallet\MainBundle\Entity\EmailCustomParam::TYPES;

        $result['EventDate']['Note'] = 'If the event date matches the mailing date, the "Message" field will be inserted into the letter. (If there are several such entries, the most recent one will be used)';

        $result['Subject']['Note'] = 'Default: AwardWallet weekly blog digest 📓';

        $result['Preview']['InputType'] = 'textarea';

        $result['Message']['InputType'] = 'htmleditor';
        $result['Message']['Note'] = 'Do not use complex text formatting or insert images, as there is no certainty that it will be displayed correctly in the mail interface.';
        $result['Message']['HTML'] = true;
        $result['Message']['ToolbarSet'] = [
            [
                'name' => 'document',
                'items' => [
                    'NewPage',
                    'Preview',
                    '-',
                    'Templates',
                    '-',
                    'Cut',
                    'Copy',
                    'Paste',
                    'PasteText',
                    'PasteFromWord',
                    '-',
                    'Undo',
                    'Redo',
                    '-',
                    'Find',
                    'Replace',
                    'SelectAll',
                ],
            ],
            '/',
            [
                'name' => 'basicStyles',
                'items' => [
                    'Bold',
                    'Italic',
                    'Underline',
                    'Strike',
                    'Subscript',
                    'Superscript',
                    '-',
                    'RemoveFormat',
                    '-',
                    'NumberedList',
                    'BulletedList',
                    'Outdent',
                    'Indent',
                    'Blockquote',
                    '-',
                    'JustifyLeft',
                    'JustifyCenter',
                    'JustifyRight',
                    'JustifyBlock',
                ],
            ],
            '/',
            [
                'name' => 'styles',
                'items' => [
                    'Link',
                    'Unlink',
                    'Anchor',
                    '-',
                    'Table',
                    'HorizontalRule',
                    'Smiley',
                    'SpecialChar',
                    '-',
                    'TextColor',
                    'BGColor',
                    'Maximize',
                    '-',
                    'Source',
                ],
            ],
        ];
        $result['Message']['htmleditorCustomConfig'] = [
            'coreStyles_bold' => ['element' => 'b'],
        ];

        $result['BlogDigestExcludeID']['Caption'] = 'Exclude Post ID';
        $result['BlogDigestExcludeID']['Note'] = 'A comma-separated list of PostIDs to be excluded from the weekly digest email';

        return $result;
    }

    public function ShowForm()
    {
        parent::ShowForm();

        echo <<<HTML
<script>
    setTimeout(function(){
        const ckDoc = document.getElementsByClassName('cke_wysiwyg_frame')[0].contentDocument;
        const style = ckDoc.createElement('style');
        style.textContent = '.bold, span.bold {font-weight: bold !important;}';
        ckDoc.body.appendChild(style);
    }, 2000);
</script>
HTML;
    }
}
