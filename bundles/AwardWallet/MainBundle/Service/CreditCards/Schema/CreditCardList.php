<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use AwardWallet\MainBundle\Service\Blog\NonAffiliateCards;
use AwardWallet\MainBundle\Service\CreditCards\Advertise;

class CreditCardList extends \TBaseList
{
    private $creditCardRepository;
    private CreditCardCategoryList $creditCardCategoryList;

    public function __construct(
        string $table,
        array $fields,
        CreditCardRepository $creditCardRepository,
        CreditCardCategoryList $creditCardCategoryList
    ) {
        parent::__construct($table, $fields, null);

        $this->creditCardRepository = $creditCardRepository;
        $this->creditCardCategoryList = $creditCardCategoryList;

        if (isset($_GET['updateCardsBlogDisclosure'])) {
            exit(json_encode(getSymfonyContainer()->get(NonAffiliateCards::class)->syncNonAffiliateDisclosure()));
        }
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === 'html') {
            $this->Query->Fields = $this->formatFieldsRow($this->Query->Fields);
        }
    }

    public function formatFieldsRow($row)
    {
        if (!empty($row['ExcludeCardsId'])) {
            $excludeCards = $this->creditCardRepository->findBy(['id' => explode(',', $row['ExcludeCardsId'])]);
            $row['ExcludeCardsId'] = '';

            foreach ($excludeCards as $groupCard) {
                $groupCardName = empty($groupCard->getCardFullName()) ? $groupCard->getName() : $groupCard->getCardFullName();
                $row['ExcludeCardsId'] .= '<a href="edit.php?Schema=CreditCard&ID=' . $groupCard->getId() . '">' . $groupCardName . '</a>';
            }
        }

        $name = empty($row['CardFullName']) ? $row['Name'] : $row['CardFullName'];

        if (empty($name) && empty($row['Text'])) {
            return $row;
        }

        /** @var CreditCard $card */
        $card = $this->creditCardRepository->find($row['CreditCardID']);

        $row['Name'] = $this->getAdsBox($row['DirectClickURL'], $name, $row['Text'], $card->getPicturePath());

        $row['BonusEarning'] = $this->creditCardCategoryList->getCategoriesList($row['CreditCardID']);

        return $row;
    }

    public function DrawButtonsInternal()
    {
        $triggers = parent::DrawButtonsInternal();

        echo '
            Show cards for User <input name="userReport" type="text" value="' . (!empty($_POST['userReport']) ? htmlspecialchars($_POST['userReport'], ENT_QUOTES) : '') . '" placeholder="Email / Login / UserID" style="padding: 3px;margin-top:-3px;" required> <button class="btn btn-primary" id="userReportButton" onclick="this.form.action.value = \'userReport\'; form.submit();">Submit</button>
        ';

        return $triggers;
    }

    public function ProcessAction($action, $ids)
    {
        global $Interface;

        parent::ProcessAction($action, $ids);

        if ($action === 'userReport') {
            if (empty($_POST['userReport'])) {
                return;
            }

            $style = '
            <style type="text/css">
            .ucc-popup {
                position: fixed;
                width: 80%;
                max-height: 80%;
                overflow: auto;
                top: 100px;
                left: 10%;
                background: #fff;
                z-index: 10;
                padding: .5rem 1rem;
            }
            .ucc-overlay {
                position: fixed;
                width: 100%;
                height: 100%;
                left:0;
                top:0;
                z-index: 9;
                background: rgba(0,0,0,.5);
            }
            .ucc-popup h2,
            .ucc-popup h3 {
                margin-bottom: 0;
            }
            .ucc-popup table {
                border-collapse: collapse;
                border-spacing: 0;
            }
            .ucc-popup table th {
                background: #ddd;
                padding: 10px;
            }
            .ucc-popup table td {
                padding: 5px 8px; 
                border: solid 1px #999;
            }
            .cc-preview {
                max-width: 200px;
                max-height: 70px;
            }
            </style>
            <div id="uccOverlay" class="ucc-overlay" onclick="$(\'#uccPopup,#uccOverlay\').hide();$(\'body\').css(\'overflow\', \'auto\')"></div>
            ';

            $html = $style . $this->reportByUser($_POST['userReport']);

            $Interface->FooterScripts[] = '
                $("#body").css("overflow", "hidden");
                $(document.body).append("' . addslashes(str_replace("\n", '', $html)) . '");
            ';
        }
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
        echo "<style type='text/css'>
                #list-table > tbody>tr>td:nth-child(10) {
                    min-width: 200px !important;
                }
                #list-table > tbody>tr>td:nth-child(10) a {
                    display:block;
                    margin-bottom: 5px;
                }
                #list-table > tbody>tr>td:nth-child(12) a + a {
                    display:block;
                    margin-top: 5px;
                }

                    @font-face {
                        font-family: 'open_sansregular';
                        src: url('../assets/awardwalletnewdesign/font/opensans-regular-webfont.eot?v=2');
                        src: url('../assets/awardwalletnewdesign/font/opensans-regular-webfont.eot?#iefix') format('embedded-opentype'),
                             url('../assets/awardwalletnewdesign/font/opensans-regular-webfont.woff?v=2') format('woff'),
                             url('../assets/awardwalletnewdesign/font/opensans-regular-webfont.ttf?v=2') format('truetype');
                        font-weight: normal;
                        font-style: normal;
                    
                    }
                    @font-face {
                        font-family: 'open_sansbold';
                        src: url('../assets/awardwalletnewdesign/font/opensans-bold-webfont.eot?v=2');
                        src: url('../assets/awardwalletnewdesign/font/opensans-bold-webfont.eot?#iefix') format('embedded-opentype'),
                             url('../assets/awardwalletnewdesign/font/opensans-bold-webfont.woff?v=2') format('woff'),
                             url('../assets/awardwalletnewdesign/font/opensans-bold-webfont.ttf?v=2') format('truetype');
                        font-weight: normal;
                        font-style: normal;
                    
                    }
                    .card-offer {
                      text-decoration: none!important;
                      width: 100%;
                      display: -webkit-flex;
                      display: -ms-flexbox;
                      display: -ms-flex;
                      display: flex;
                      -webkit-flex-flow: row nowrap;
                      -ms-flex-flow: row nowrap;
                      flex-flow: row nowrap;
                      -webkit-align-items: center;
                      -ms-align-items: center;
                      align-items: center;
                      -webkit-justify-content: flex-end;
                      -ms-justify-content: flex-end;
                      justify-content: flex-end;
                    }
                    .card-offer .thumb {
                      width: 140px;
                    }
                    .card-offer .thumb img {
                      width: 140px;
                      height: 88px;
                      vertical-align: top;
                      border-radius: 4px;
                      -webkit-border-radius: 4px;
                      -moz-border-radius: 4px;
                    }
                    .card-offer .details {
                      line-height: 1.2;
                      text-align: right;
                      width: calc(100% - 70px);
                      padding-right: 7px;
                    }
                    .card-offer .details .title {
                      font-family: 'open_sansbold';
                      color: #535457;
                      font-size: 13px;
                      white-space: nowrap;
                      overflow: hidden;
                      text-overflow: ellipsis;
                    }
                    .card-offer .details .description {
                      color: #929399;
                      font-size: 13px;
                    }
                    .card-offer .details .blue-link {
                      color: #4684c4;
                      font-size: 13px;
                      text-decoration: none;
                      font-family: 'open_sansbold';
                    }
                    
                    #popupCardsDisclosureResult {
                        position: absolute;
                        padding: 10px 15px;
                        left: 50px;
                        top: 100px;
                        background: #fff;
                        border: 1px solid #000;
                    }
                    #popupCardsDisclosureResult h3 {
                        margin-bottom: 0;
                    }
                    #popupCardsDisclosureResult > a[href='#close'] {
                        position: absolute;
                        right: 10px;
                        top: 0;
                        font-size: 3rem;
                        color: darkred;
                        text-decoration: none;
                    }
            </style>
            <script>
            function syncAwBlogCards() {
                const syncBtn = $('#syncAwBlogCardsBtn'); 
                syncBtn.css({'background': 'black', 'color':'#fff'}).text('Sync From AW Blog cards - process');
                $.post('/api/blog/aw_quinstreet_cards/sync', function(response){
                    if (response.success) {
                        syncBtn.css({'background': 'green', 'color':'#fff'}).text('Sync From AW Blog cards - done');
                    } else {
                        syncBtn.css({'background': 'darkred', 'color':'#fff'}).text('Sync From AW Blog cards - fail');
                    }
                }, 'JSON');
            }
            function syncBlogCardsDisclosure() {
                const syncBtn = $('#syncCardBlogDisclosure'); 
                syncBtn.text('Sync AW Blog Cards Disclosures - process');
                $.post('/manager/list.php?Schema=CreditCard&updateCardsBlogDisclosure', function(response) {
                    if ($('#popupCardsDisclosureResult').length == 0){
                        $('body').append('<div id=\"popupCardsDisclosureResult\"></div>');
                    }
                    
                    let html = '<a href=\"#close\" onclick=\"$(\'#popupCardsDisclosureResult\').hide();\">×</a><div style=\"display:flex;\">';
                    
                    const printList = function(list) {
                        let html = '<ul>';
                        for (const [key, value] of Object.entries(list)) {
                            html += '<li>'+value.name+'</li>';
                        }
                        html += '</ul>';
                        return html;
                    };
                    
                    if (response.notFound) {
                        html += '<div><h3>Not Found Card</h3><br><sup>(required <b>Qs Credit Card</b>)</sup>' + printList(response.notFound) + '</div>';
                    }
                    if (response.inactives) {
                        html += '<div><h3>Disclosure is <u>off</u></h3>' + printList(response.inactives) + '</div>';
                    }
                    if (response.actives) {
                        html += '<div><h3>Disclosure is <u>on</u></h3>' + printList(response.actives) + '</div>';
                    }
                    
                    html += '</div>';
                    
                    $('#popupCardsDisclosureResult').empty().html(html).show();
                    
                    syncBtn.text('Sync AW Blog Cards Disclosures');

                }, 'JSON');
            }
            $(document).ready(function() {
                $('#DeleteId').before(`
                    <button id=\"syncCardBlogDisclosure\" type=\"button\" style=\"margin-right: 5px;\" onclick=\"syncBlogCardsDisclosure()\">Sync AW Blog Cards Disclosures</button>
                    <button id=\"syncAwBlogCardsBtn\" type=\"button\" style=\"margin-right: 5px;\" onclick=\"syncAwBlogCards()\">Sync From AW Blog cards</button>
                `);
            });
            </script>
            ";
    }

    public function GetEditLinks()
    {
        $result = parent::GetEditLinks();

        $row = $this->OriginalFields;

        $result .= "<br/><a target='_blank' href='list.php?Schema=CreditCardShoppingCategoryGroup&CreditCardID={$row['CreditCardID']}'>Category Groups</a>";
        $result .= "<br/><a target='_blank' href='list.php?Schema=CreditCardMerchantGroup&CreditCardID={$row['CreditCardID']}'>Merchant Groups</a>";
        $result .= "<br/><a target='_blank' href='list.php?Schema=CreditCardBonusLimit&CreditCardID={$row['CreditCardID']}'>Bonus Limits ({$row['BonusLimits']})</a>";

        return $result;
    }

    private function getAdsBox($link, $name, $text, $img)
    {
        return '
            <a class="card-offer" target="_blank" href="' . $link . '">
                <div class="details">
                    <div class="title">' . $name . '</div>
                    <div class="description">
                        <span>' . $text . '</span>
                        <span class="blue-link">Apply Now</span>
                    </div>
                </div>
                <div class="thumb">
                    ' . (empty($img) ? '' : '<img src="' . $img . '" alt="cc_promo">') . '
                </div>
            </a>
        ';
    }

    private function reportByUser(string $query)
    {
        global $arProviderKind;
        $advertiseService = getSymfonyContainer()->get(Advertise::class);

        $query = trim($query);

        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            $userWhere = ['email' => $query];
        } else {
            $userId = filter_var($query, FILTER_SANITIZE_NUMBER_INT);

            if ($userId == $query) {
                $userWhere = ['userid' => (int) $userId];
            } else {
                $userWhere = ['login' => $query];
            }
        }

        $html = [];
        /** @var \AwardWallet\MainBundle\Entity\Usr $user */
        $user = getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy($userWhere);

        if (!empty($user)) {
            $ads = $advertiseService->getListByUser($user);
            $list = '';

            foreach ($ads as $kind => $ad) {
                $list .= '<tr>';
                $list .= '<td><h2>' . $arProviderKind[$kind] . '</h2></td>';
                $list .= '<td>' . $this->getAdsBox($ad->link, $ad->title, $ad->description, $ad->image) . '</td>';
                $list .= '</tr>';

                $list .= str_repeat('<tr><td colspan="2">...</td></tr>', 5);
            }

            $html[] = '<h1>Account List cards for user - ' . $user->getFullName() . '</h1><table class="usercc">' . $list . '</table><br><br>';
        } else {
            $html[] = '<h1>User not found</h1><br><br>';
        }

        $html = '
        <div id="uccPopup" class="ucc-popup">
            <div class="ucc-content">
            ' . implode('<br>', $html) . '
            <style type="text/css">
            .usercc {width: 100%}
            .usercc, .usercc td {border-color: #ddd !important;}
            </style>
            </div>
        </div>
        ';

        return $html;
    }
}
