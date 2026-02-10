<?php

namespace AwardWallet\Manager\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class EliteLevelCardsList extends \TBaseList
{
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private ?int $eliteLevelId;

    public function __construct(
        $table,
        $fields
    ) {
        parent::__construct($table, $fields);
        $this->entityManager = getSymfonyContainer()->get('doctrine')->getManager();
        $this->router = getSymfonyContainer()->get('router');

        $this->eliteLevelId = (int) ($_GET['eliteLevelId'] ?? 0);

        if ($this->eliteLevelId) {
            $provider = $this->entityManager->getConnection()->fetchAssociative('SELECT p.ProviderID, p.DisplayName FROM Provider p JOIN EliteLevel el ON el.ProviderID = p.ProviderID WHERE el.EliteLevelID = ' . $this->eliteLevelId . ' LIMIT 1');
            $textEliteLevels = $this->entityManager->getConnection()->fetchFirstColumn('SELECT ValueText FROM TextEliteLevel WHERE EliteLevelID = ' . $this->eliteLevelId);
            $levelCondition = [];

            foreach ($textEliteLevels as $level) {
                $levelCondition[] = "ap.Val LIKE '" . $level . "'";
            }

            $this->SQL = "
                SELECT
                    a.AccountID, a.Login,
                    GROUP_CONCAT(ci.CardImageID ORDER BY ci.Kind ASC) AS CardImages,
                    GROUP_CONCAT(ci.Kind ORDER BY ci.Kind ASC) AS Kinds
                FROM Account a
                JOIN AccountProperty ap ON ap.AccountID = a.AccountID
                JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID AND pp.ProviderID = " . $provider['ProviderID'] . " AND pp.Kind = " . PROPERTY_KIND_STATUS . "
                JOIN CardImage ci ON ci.AccountID = a.AccountID AND ci.Kind IN (1, 2)
                WHERE
                    (" . implode(' OR ', $levelCondition) . ")
                    [Filters]
                GROUP BY a.AccountID, a.Login
            ";
        } else {
            $this->SQL = "
                SELECT
                    a.AccountID, a.Login,
                    GROUP_CONCAT(ci.CardImageID ORDER BY ci.Kind ASC) AS CardImages,
                    GROUP_CONCAT(ci.Kind ORDER BY ci.Kind ASC) AS Kinds
                FROM Account a
                JOIN AccountProperty ap ON ap.AccountID = a.AccountID
                JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID AND pp.Kind = " . PROPERTY_KIND_STATUS . "
                JOIN CardImage ci ON ci.AccountID = a.AccountID AND ci.Kind IN (1, 2)
                WHERE
                    1 [Filters]
                GROUP BY a.AccountID, a.Login
            ";
        }
    }

    public function DrawButtonsInternal(): array
    {
        $result = parent::DrawButtonsInternal();

        return $result;
    }

    public function DrawButtons($closeTable = true): void
    {
        parent::DrawButtons($closeTable);

        $list = $this->entityManager->getConnection()->fetchAllAssociative("
            SELECT
                p.ProviderID, p.DisplayName,
                el.EliteLevelID, el.Rank, el.Name
            FROM Provider p
            JOIN EliteLevel el ON p.ProviderID = el.ProviderID
            ORDER BY p.DisplayName ASC, el.Rank ASC
        ");

        $levels = [];

        foreach ($list as $item) {
            $providerId = (int) $item['ProviderID'];
            !array_key_exists($providerId, $levels) ? $levels[$providerId] = [
                'ProviderID' => $providerId,
                'DisplayName' => $item['DisplayName'],
                'levels' => [],
            ] : null;

            unset($item['ProviderID'], $item['DisplayName']);
            $levels[$providerId]['levels'][] = $item;
        }
        $levelsHtml = '';

        foreach ($levels as $providerId => $item) {
            $levelsHtml .= '<optgroup label="' . htmlspecialchars($item['DisplayName']) . '">';

            foreach ($item['levels'] as $level) {
                $isSelected = $this->eliteLevelId == $level['EliteLevelID'] ? ' selected="selected"' : '';
                $levelsHtml .= '<option value="' . $level['EliteLevelID'] . '"' . $isSelected . '>' . $level['Name'] . '</option>';
            }
            $levelsHtml .= '</optgroup>';
        }

        $this->footerScripts = [];

        $extends = '
        <div style="float:left;padding:0 0 10px 10px;">
            <form method="get" action="/manager/list.php">
            <input type="hidden" name="Schema" value="EliteLevelCards">
            <div style="min-width: 500px;padding: 5px 0;">
                Elite Levels by Provider: 
                <select id="eliteLevelId" name="eliteLevelId" tabindex="0" aria-hidden="false">
                    <option></option>
                    ' . $levelsHtml . '
                </select>
                
                <button type="submit">Submit</button>
            </div>
            <div style="padding: 10px 0;">
                <label><input id="loadFront" type="checkbox"> Load Front Images</label>
                <label><input id="loadBack" type="checkbox"> Load Back Images</label>
            </div>
            </form>
        </div>
    ';

        $this->footerScripts[] = '
            $("#extendFixedMenu").prepend(`' . addslashes(str_replace("\n", '', $extends)) . '`);
        ';

        $style = '<style>
            .select2-container {
                min-width: 500px;
            }
            #content-title {
                padding-top: 50px;
            }
            #list-table img {
                max-width: 300px;
            }
        </style>';
        $this->footerScripts[] = '
            $(document.body).append(`' . str_replace("\n", '', $style) . '`);
            $("#eliteLevelId").select2({
                matcher: function(term, text, opt) {
                    return text.toUpperCase().indexOf(term.toUpperCase()) >= 0 || opt.parent("optgroup").attr("label").toUpperCase().indexOf(term.toUpperCase()) >= 0;
                }
            });
            
            $("#loadFront,#loadBack").change(function(){
                const type = $(this).attr("id").substring(4);
                const isChecked = $(this).prop("checked");
                const selector = ".js-" + type;
                
                $(selector).each(function() {
                    if (isChecked) {
                        $(this).attr("src", $(this).data("src"));
                    } else {
                        $(this).attr("src", "");
                    }
                });
            });
        ';
    }

    public function FormatFields($output = 'html'): void
    {
        parent::FormatFields($output);

        $cards = array_combine(
            explode(',', $this->Query->Fields['Kinds']),
            explode(',', $this->Query->Fields['CardImages'])
        );

        $this->Query->Fields['ImageFront'] = isset($cards[1]) ? '<img class="js-Front" src="" data-src="' . $this->router->generate('aw_manager_card_image', ['cardImageId' => $cards[1]]) . '" loading="lazy" />' : 'none';
        $this->Query->Fields['ImageBack'] = isset($cards[2]) ? '<img class="js-Back" src="" data-src="' . $this->router->generate('aw_manager_card_image', ['cardImageId' => $cards[2]]) . '" loading="lazy" />' : 'none';
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
        $this->drawFooterScript();
    }

    public function DrawEmptyList()
    {
        parent::DrawEmptyList();
        $this->drawFooterScript();
    }

    private function drawFooterScript()
    {
        global $Interface;

        if (empty($Interface->isAlreadyFooterScripts) && !empty($this->footerScripts)) {
            $Interface->isAlreadyFooterScripts = true;
            echo '<script>';

            foreach ($this->footerScripts as $script) {
                echo $script;
            }
            echo '</script>';
        }
    }
}
