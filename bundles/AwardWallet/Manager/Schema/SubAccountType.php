<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Account\SubAccountMatcher;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\RegexMetadataFactory;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SubAccountType extends \TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        $TAB = "&emsp;&emsp;&emsp;&emsp;";

        $this->Fields['ProviderID']['Options'] = ['' => ''] + SQLToArray(
            "select ProviderID, DisplayName from Provider order by DisplayName",
            "ProviderID",
            "DisplayName"
        );
        $this->Fields['Patterns']['InputType'] = 'textarea';
        $this->Fields['Patterns']['Note'] = "
            <style>
                /* Style the button that is used to open and close the collapsible content */
                .collapsible {
                  background-color: #f8f8f8;
                  color: #444;
                  cursor: pointer;
                  padding: 5px;
                  width: 15%;
                  border: none;
                  text-align: left;
                  outline: none;
                  font-size: 15px;
                }

                /* Add a background color to the button if it is clicked on (add the .active class with JS), and when you move the mouse over it (hover) */
                .active, .collapsible:hover {
                  background-color: #ccc;
                }

                /* Style the collapsible content. Note: hidden by default */
                .content {
                  padding: 0 18px;
                  display: none;
                  overflow: hidden;
                  background-color: #f1f1f1;
                } 
                
                .collapsible:after {
                  content: '\\02795'; /* Unicode character for 'plus' sign (+) */
                  font-size: 13px;
                  color: white;
                  float: right;
                  margin-left: 5px;
                }

                .active:after {
                  content: '\\2796'; /* Unicode character for 'minus' sign (-) */
                }
                
                .collapsible.active + div.content {
                    display: block;
                }
            </style>
            <button type='button' class='collapsible' onclick='this.classList.toggle(\"active\")'>Patterns help</button>
            <div class='content'>
                <ul>
                    <li>
                        Can be multiple patterns, one per line, each pattern should be on a new line:<br/>
                            {$TAB}SAKS<br/>
                            {$TAB}Membership rewards<br/>
                    </li>
                    <li>
                        Search is case-insensitive, these patterns are the same: <br/>
                            {$TAB}AMAZON<br/>
                            {$TAB}amazon<br/>
                            {$TAB}aMaZoN<br/>
                    </li>
                    <li>
                        Regular expressions are supported, patterns are case-insensitive, modifiers are ignored, these patterns are the same: <br/>
                            {$TAB}#a?ma?zo?n#<br/>
                            {$TAB}#A?MA??ZO?N#<br/>
                            {$TAB}#A?MA?ZO?N#<br/> 
                        <br><b>Regular expressions should start and end with #</b>
                    </li>
                </ul>
            </div>
        ";

        $this->Fields['BlogIds']['Note'] = 'Use a comma to separate';
        $this->Fields['MatchingOrder']['Note'] = 'Ascending. The lower the number, the earlier';
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnCheck = function () use ($form) {
            $patterns = $form->Fields['Patterns']['Value'];

            if (StringUtils::isNotEmpty($patterns)) {
                $patternsList = RegexMetadataFactory::create($patterns);
                $regexpErrors =
                    it($patternsList)
                        ->filter(fn (array $pattern) => isset($pattern['pregError']))
                        ->toArray();

                if ($regexpErrors) {
                    return "There are issues with patterns:<br/> \n" .
                        it($regexpErrors)
                            ->map(fn (array $error) => "{$error['template']}: {$error['pregError']}")
                            ->joinToString("<br/>");
                }
            }
        };

        $form->OnSave = $this->cleanCache();
    }

    public function TuneList(&$list): void
    {
        parent::TuneList($list);

        $list->ShowExport =
        $list->ShowImport =
        $list->MultiEdit = false;
    }

    public function showForm()
    {
        parent::showForm();

        echo '
<style>
.select2-container {
    min-width: 700px;
}
</style>
<script>
    $("#fldProviderID").select2();
</script>';
    }

    public function AfterDelete(): void
    {
        $this->cleanCache();
    }

    private function cleanCache(): void
    {
        getSymfonyContainer()->get(\Memcached::class)->delete(SubAccountMatcher::CACHE_KEY);
    }
}
