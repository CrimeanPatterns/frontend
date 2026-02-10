<?php

namespace AwardWallet\Manager\Schema\Account;

use Symfony\Component\Routing\RouterInterface;

class AccountInfoList extends \TBaseList
{
    private RouterInterface $router;

    public function __construct($table, $fields)
    {
        foreach ($fields as $code => $field) {
            if (!isset($field['FilterField'])) {
                $fields[$code]['FilterField'] = 't.' . $code;
            }
        }

        $this->router = getSymfonyContainer()->get('router');

        parent::__construct($table, $fields);
    }

    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        $this->Query->Fields['AccountID'] = sprintf(
            '%d <a href="%s">details</a>',
            $this->OriginalFields['AccountID'],
            $this->router->generate('aw_enhanced_action', [
                'schema' => 'AccountInfo',
                'action' => 'info',
                'id' => $this->OriginalFields['AccountID'],
            ])
        );

        if (!empty($this->OriginalFields['UserID'])) {
            $this->Query->Fields['UserID'] = sprintf(
                '<a target="_blank" href="%s">%s</a>%s',
                sprintf('/manager/list.php?UserID=%d&Schema=UserAdmin', $this->OriginalFields['UserID']),
                $this->OriginalFields['UserName'],
                $this->OriginalFields['FamilyMemberName'] ? sprintf('<div style="color:grey">fm: %s</div>', $this->OriginalFields['FamilyMemberName']) : ''
            );
        }

        if (!empty($this->OriginalFields['ProviderID'])) {
            $this->Query->Fields['ProviderID'] = sprintf(
                '<a target="_blank" href="%s">%s</a>',
                sprintf('/manager/list.php?Schema=Provider&ProviderID=%d', $this->OriginalFields['ProviderID']),
                $this->OriginalFields['ShortName']
            );
        } else {
            $this->Query->Fields['ProviderID'] = $this->OriginalFields['ProgramName'];
        }

        if (!empty($this->OriginalFields['ErrorMessage'])) {
            $this->Query->Fields['ErrorCode'] = sprintf(
                '<abbr title="%s">%s</abbr>',
                $this->OriginalFields['ErrorMessage'],
                $this->Query->Fields['ErrorCode'] ?? htmlspecialchars('<none>')
            );
        }

        $balances = [];

        if (!is_null($this->OriginalFields['Balance'])) {
            $balances[] = sprintf(
                '<div style="font-family:Monospace">%s</div>',
                $this->formatBalance($this->OriginalFields['Balance'])
            );
        }

        if (!is_null($this->OriginalFields['LastBalance'])) {
            $balances[] = sprintf(
                '<abbr title="Last Balance" style="font-family:Monospace; font-size: 0.8em">%s</abbr>',
                $this->formatBalance($this->OriginalFields['LastBalance'])
            );
        }

        if (!empty($balances)) {
            $this->Query->Fields['Balance'] = implode('', $balances);
        }
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;

        parent::DrawButtons($closeTable);

        $styles = <<<HTML
<style>
    #list-table tr td {
        font-size: 0.8em;
    }
</style>
HTML;

        $styles = addslashes(str_replace("\n", '', $styles));
        echo "<script>$(document.body).append('$styles');</script>";
    }

    private function formatBalance($balance): string
    {
        if (is_null($balance)) {
            return '';
        }

        if (intval($balance) == $balance) {
            return number_format($balance);
        }

        return number_format($balance, 2);
    }
}
