<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\ConcurrentArrayFactory;
use AwardWallet\MainBundle\Security\StringSanitizer;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\Async\RelinkMerchantsTask;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\RegexMetadataFactory;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\minutes;

class MerchantPattern extends \TBaseSchema
{
    private Process $process;

    private LoggerInterface $logger;
    private Connection $connection;
    private ConcurrentArrayFactory $concurrentArrayFactory;
    private AwTokenStorageInterface $tokenStorage;
    private ClockInterface $clock;
    private MerchantMatcher $merchantMatcher;

    public function __construct(
        Process $process,
        LoggerInterface $logger,
        Connection $connection,
        ConcurrentArrayFactory $concurrentArrayFactory,
        AwTokenStorageInterface $awTokenStorage,
        ClockInterface $clock,
        MerchantMatcher $merchantMatcher
    ) {
        parent::__construct();

        $this->ListClass = MerchantPatternList::class;

        $this->process = $process;
        $this->logger = $logger;
        $this->connection = $connection;
        $this->concurrentArrayFactory = $concurrentArrayFactory;
        $this->tokenStorage = $awTokenStorage;
        $this->clock = $clock;
        $this->merchantMatcher = $merchantMatcher;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        unset($result['ClickUrl']);
        unset($result['DescriptionExamples']);
        ArrayInsert($result, "Patterns", true, [
            "Groups" => [
                "Type" => "string",
                "Options" => $this->connection->fetchAllKeyValue("select MerchantGroupID, Name from MerchantGroup order by Name"),
            ],
        ]);

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->DefaultSort = "Transactions";
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        unset($fields['Transactions']);
        unset($fields['Stat']);
        unset($fields['TransactionsConfidenceInterval']);
        unset($fields['ConfidenceIntervalStartDate']);
        $TAB = "&emsp;&emsp;&emsp;&emsp;";
        $fields['Patterns']['Note'] = "
            <script>
                setTimeout(() => {
                    var coll = document.getElementsByClassName('collapsible');
                    var i;

                    for (i = 0; i < coll.length; i++) {
                      coll[i].addEventListener('click', function() {
                        this.classList.toggle('active');
                        var content = this.nextElementSibling;
                        if (content.style.display === 'block') {
                          content.style.display = 'none';
                        } else {
                          content.style.display = 'block';
                        }
                      });
                    } 
                }, 0);
            </script>
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
            </style>
            <button type='button' class='collapsible'>Patterns help</button>
            <div class='content'>
                <ul>
                    <li>
                        Can be multiple patterns, one per line, each pattern should be on a new line:<br/>
                            {$TAB}amazon<br/>
                            {$TAB}amzn<br/>
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
                            {$TAB}#A?MA??ZO?N#i<br/>
                            {$TAB}#A?MA?ZO?N#si<br/> 
                        Regular expressions should start and end with #
                    </li>
                    
                    <li>
                        Explicit positive and negative modifiers are supported, can be mixed, patterns should contain at least one positive pattern: <br/>
                            {$TAB}<span style='color: darkgreen'>GOOD</span>:<br/>
                                {$TAB}{$TAB}apple<br/>
                                {$TAB}{$TAB}+#apple inc#<br/>
                                {$TAB}{$TAB}-fruits<br/>
                            {$TAB}<span style='color: darkred'>BAD</span> (should contain at least one positive pattern):<br/>
                                {$TAB}{$TAB}-fruits<br/>
                                {$TAB}{$TAB}-#juice#<br/>
                    </li>
                </ul>
            </div>
        ";
        $fields['Patterns']['InputType'] = 'textarea';
        $fields['DescriptionExamples']['Caption'] = 'Transaction examples';
        $fields['DescriptionExamples']['InputType'] = 'textarea';
        $fields['DescriptionExamples']['Note'] = "
            <button type='button' class='collapsible'>Examples help</button>
            <div class='content'>
                <ul>
                    <li>
                        Can be multiple examples, one per line, each example should be on a new line:<br/>
                            {$TAB}AMAZON WEB SERVICES PAYMENT<br/>
                            {$TAB}AMZNWEBSRV<br/>
                    </li>
                    
                    <li>
                        Explicit positive and negative modifiers are supported, can be mixed with each other:<br/>
                            {$TAB}AMAZON AWS<br/>
                            {$TAB}+AMAZON WEB SERVICES<br/>
                            {$TAB}-AMAZON TV<br/>
                        patterns should:<br/>
                            {$TAB}<span style='font-weight: bold'>MATCH</span> transaction <span style='font-style: italic'>AMAZON AWS</span><br/>
                            {$TAB}<span style='font-weight: bold'>MATCH</span> transaction <span style='font-style: italic'>AMAZON WEB SERVICES PAYMENT</span><br/>
                            {$TAB}<span style='font-weight: bold'>NOT MATCH</span> transaction <span style='font-style: italic'>AMAZON TV</span><br/>
                    </li>
                </ul>
            </div>
        ";

        $fields['ClickUrl']['Note'] = 'Link to blog post';
        $fields['DetectPriority']['Note'] = '0..255. Lower number means higher priority.';

        $groups = SQLToArray(
            "select MerchantGroupID, Name from MerchantGroup order by Name",
            "MerchantGroupID",
            "Name"
        );
        $options = ['' => ''];

        foreach ($groups as $key => $value) {
            $options[(string) $key] = $value;
        }

        // Merchant groups
        $groupManager = new \TTableLinksFieldManager();
        $groupManager->TableName = "MerchantPatternGroup";
        $groupManager->KeyField = "MerchantPatternID";
        $groupManager->UniqueFields = ["MerchantGroupID"];
        $groupManager->Fields = [
            "MerchantGroupID" => [
                "Type" => "integer",
                "Caption" => "Group",
                "FilterField" => "MerchantGroupID",
                "Options" => $options,
                "Required" => true,
            ],
        ];
        $groupManager->CanEdit = true;

        if (!empty($_GET["Groups"])) {
            $groupManager->SelectedOptions[] = ["MerchantGroupID" => 30];
        }

        $fields["Groups"] = [
            "Manager" => $groupManager,
        ];

        return $fields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        $form->OnCheck = function () use ($form) {
            $TAB = "&emsp;&emsp;&emsp;&emsp;";
            $examples = \trim($form->Fields['DescriptionExamples']['Value']);
            $patterns = $form->Fields['Patterns']['Value'];
            $regexpErrors = [];

            if (StringUtils::isNotEmpty($patterns)) {
                $patternsList = RegexMetadataFactory::create($patterns);
                $regexpErrors =
                    it($patternsList)
                    ->filter(fn (array $pattern) => isset($pattern['pregError']))
                    ->toArray();

                if ($regexpErrors) {
                    return
                        "There are issues with patterns:<br/> \n"
                        .
                            it($regexpErrors)
                            ->map(fn (array $error) => "{$error['template']}: {$error['pregError']}")
                            ->joinToString("<br/>")
                    ;
                }

                [$positivePatterns, $negativePatterns] = MerchantMatcher::fillBuilders(
                    it($patternsList)
                    ->mapIndexed(fn (array $pattern, int $idx) => [
                        'MerchantPatternID' => $idx,
                        'Patterns' => [$pattern],
                    ])
                    ->toArray()
                );
            }

            if (StringUtils::isNotEmpty($examples)) {
                $matchErrors = [];

                foreach (\explode("\n", $examples) as $exampleOriginal) {
                    [$example, $isPositive] = RegexMetadataFactory::processSign($exampleOriginal);

                    if ($isPositive) {
                        $hasPositiveMatches =
                            it($positivePatterns)
                            ->any(fn (string $positive) => \preg_match($positive, $example));
                        $negativeMatchesIdxList = self::matchAllPatterns($negativePatterns, $example);

                        if (!$hasPositiveMatches) {
                            $matchErrors[] = "Positive transactions example <span style='font-style: italic'>{$exampleOriginal}</span> was not matched by any positive pattern";
                        }

                        if ($negativeMatchesIdxList) {
                            $matchErrors[] =
                                "Positive transaction example <span style='font-style: italic'>{$exampleOriginal}</span> was matched by negative pattern(s):<br/>"
                                . " <span style='font-style: italic'>"
                                .
                                    it($negativeMatchesIdxList)
                                    ->map(fn (int $idx) => $TAB . $TAB . $patternsList[$idx]['original'])
                                    ->joinToString('<br/>')
                                . "</span>";
                        }
                    } else {
                        $hasNegativeMatches =
                            it($negativePatterns)
                            ->any(fn (string $positive) => \preg_match($positive, $example));
                        $positiveMatchesIdxList = self::matchAllPatterns($positivePatterns, $example);

                        if (!$hasNegativeMatches) {
                            $matchErrors[] = "Negative transaction example <span style='font-style: italic'>{$exampleOriginal}</span> was not matched by any negative pattern";
                        }

                        if ($positiveMatchesIdxList) {
                            $matchErrors[] =
                                "Negative transaction example <span style='font-style: italic'>{$exampleOriginal}</span> was matched by positive pattern(s):<br/>"
                                . " <span style='font-style: italic'>"
                                .
                                    it($positiveMatchesIdxList)
                                    ->map(fn (int $idx) => $TAB . $TAB . $patternsList[$idx]['original'])
                                    ->joinToString('<br/>')
                                . "</span>";
                        }
                    }
                }

                if ($matchErrors) {
                    return
                        "There are issues with transactions examples:<br/> \n"
                        .
                        it($matchErrors)
                        ->map(fn (string $error) => $TAB . $error)
                        ->joinToString("<br/>")
                    ;
                }
            }

            return null;
        };
        $form->OnSave = function () use ($form) {
            $this->OnPatternsChange($form->Fields['Name']['Value'], [$form->ID]);
        };
    }

    public function AfterDelete(array $arRows): void
    {
        $removedPatterns =
            it($arRows)
            ->filter(fn (array $row) => $row['Table'] === 'MerchantPattern')
            ->map(fn (array $row) => (int) $row['ID'])
            ->toArray();
        $removedPatternsCount = \count($removedPatterns);
        $this->OnPatternsChange(
            'Deleted ' . $removedPatternsCount . ' pattern(s): '
            . it($removedPatterns)->take(5)->joinToString(', ')
            . ($removedPatternsCount > 5 ? 'and more...' : ''),
            $removedPatterns
        );
    }

    public function OnPatternsChange(string $merchantName, array $patternIds): void
    {
        $this->merchantMatcher->clearCache();
        /** @var Usr $user */
        $user = $this->tokenStorage->getUser();
        $channelId = UserMessaging::getChannelName('mrchprm' . bin2hex(random_bytes(10)), $user->getId());
        $merchantMap = $this->concurrentArrayFactory->create('merchant_pattern_save_progress', minutes(30));
        $merchantMap->update(function (array $map) use ($channelId, $user, $merchantName, $patternIds): array {
            $timeInternal = $this->clock->current();
            $timeInternalFmt = $timeInternal->format('Y-m-d H:i:s') . ' UTC';
            $map =
                it($map)
                    ->filter(fn (array $channelData) => ($channelData['state'] !== 'finished')
                        || $timeInternal->sub($channelData["last_updated_internal"])->greaterThan(minutes(15))
                    )
                    ->toArrayWithKeys();
            $map[$channelId] = [
                "channel" => $channelId,
                "state" => "queued",
                "state_info" => "queued",
                "progress" => "0.00",
                "start_date_internal" => $timeInternal,
                "start_date" => $timeInternalFmt,
                "last_updated_internal" => $timeInternal,
                "last_updated" => $timeInternalFmt,
                "elapsed_mins" => "just started",
                'user' => StringSanitizer::encodeHtmlEntities($user->getLogin()),
                "merchant_name" => StringSanitizer::encodeHtmlEntities($merchantName),
                "affected_merchant_pattern_ids" => $patternIds,
                'processed_count' => 0,
                "updated_count" => 0,
                "speed" => "0 K/sec",
                "diff" => [],
            ];

            return $map;
        });
        $this->process->execute(new RelinkMerchantsTask($channelId));
    }

    /**
     * @param list<string> $patterns
     * @return list<int>
     */
    private static function matchAllPatterns(array $patterns, string $example): array
    {
        return
            it($patterns)
            ->flatMap(function (string $pattern) use ($example) {
                if (
                    \preg_match_all($pattern, $example, $matches)
                    && isset($matches['MARK'])
                ) {
                    foreach ($matches['MARK'] as $mark) {
                        yield (int) \explode('_', $mark)[0];
                    }
                }
            })
            ->toArray();
    }
}
