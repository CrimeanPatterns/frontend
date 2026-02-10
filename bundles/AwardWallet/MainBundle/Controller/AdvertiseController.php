<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Socialad;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\Manager\Ad\AdManager;
use AwardWallet\MainBundle\Manager\Ad\Options;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdvertiseController extends AbstractController
{
    /**
     * @Route("/top100", name="aw_get_advertise", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function getAdvertiseAction(Request $request, AdManager $adManager)
    {
        $opt = new Options(ADKIND_BALANCE_CHECK);
        $opt->accounts = $this->getAccountsByRequest($request);
        $opt->clientIp = $request->getClientIp();
        $ad = $adManager->getAdvt($opt, true);

        if ($ad) {
            return $this->json([
                'success' => true,
                "Content" => $this->getAdvtContent($ad),
                "SocialAdID" => $ad->getSocialadid(),
            ]);
        }

        return $this->json([
            'success' => true,
            "Content" => '',
            "SocialAdID" => 0,
        ]);
    }

    /**
     * @Route("/top100/click", name="aw_advertise_click", methods={"GET", "POST"}, options={"expose"=true})
     */
    public function clickAction(Request $request, AdManager $adManager)
    {
        $adId = $request->get("ad");
        $redirectUrl = $request->get("url");
        $em = $this->getDoctrine()->getManager();

        if (isset($adId) && is_numeric($adId)) {
            $ad = $em->getRepository(\AwardWallet\MainBundle\Entity\Socialad::class)->find(intval($adId));

            if ($ad) {
                $adManager->recordStat($ad->getSocialadid(), "Clicks");
            }
        }

        if (isset($redirectUrl) && is_string($redirectUrl)) {
            return new RedirectResponse($redirectUrl);
        }

        return $this->json([
            'success' => true,
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('ROLE_MANAGE_INDEX')")
     * @Route("/advertise/{id}", name="aw_get_advertise_preview", requirements={"id" = "\d+"})
     * @ParamConverter("ad", class="AwardWalletMainBundle:Socialad", options={"id" = "id"})
     * @Template("@AwardWalletMain/Advertise/getAdvertisePreview.html.twig")
     */
    public function getAdvertisePreviewAction(Socialad $ad): array
    {
        return [
            'advt' => $this->getAdvtContent($ad),
        ];
    }

    /**
     * @Route("/ads.txt", name="aw_advertise_freestar_ads")
     */
    public function freestarAdsAction()
    {
        // return $this->redirect('https://a.pub.network/awardwallet-com/ads.txt', Response::HTTP_MOVED_PERMANENTLY);
        return new Response($this->adsTxt(), Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    /**
     * @return Account[]
     */
    private function getAccountsByRequest(Request $request)
    {
        $accountsIds = [];
        $data = JsonRequestHandler::parse($request);

        if (isset($data['accounts'])) {
            $accountsIds = array_map(
                function ($accountId) {
                    return (int) ltrim($accountId, 'a');
                },
                array_filter(
                    $data['accounts'],
                    function ($accountId) {
                        return is_scalar($accountId)
                            && (strpos($accountId, 'a') === 0 || is_numeric($accountId));
                    }
                )
            );
        }

        if ($request->query->has("account")) {
            $accountId = $request->query->get("account");

            if (is_scalar($accountId) && is_numeric($accountId)) {
                $accountsIds[] = intval($accountId);
            }
        }

        $accountsIds = array_unique($accountsIds);

        if (count($accountsIds)) {
            return $this->getDoctrine()
                ->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->findBy(['accountid' => $accountsIds]);
        }

        return [];
    }

    private function getAdvtContent(Socialad $ad)
    {
        $content = $ad->getContent();

        //        $content = preg_replace('/(src=[\'"])(https?:\/\/(www\.)?awardwallet\.com)/ims', '$1', $ad->getContent());
        return $this->renderView("@AwardWalletMain/Advertise/advertiseWrapper.html.twig", [
            "advt" => $content,
        ]);
    }

    private function adsTxt(): string
    {
        return trim('
# AwardWallet ads.txt file - Last updated 05-21-2025
# Merged configuration for Freestar and MonetizeMore

OWNERDOMAIN=awardwallet.com
MANAGERDOMAIN=freestar.com, monetizemore.com
freestar.com, 1614, DIRECT

# MonetizeMore Entries
google.com, pub-1697005423717102, DIRECT, f08c47fec0942fa0
google.com, pub-6190096338979886, RESELLER, f08c47fec0942fa0 #MMLATAM
google.com, pub-5278973888786334, RESELLER, f08c47fec0942fa0 #MMUSD
google.com, pub-5517484840348343, RESELLER, f08c47fec0942fa0 #MMAdSensePSU
google.com, pub-3764626397686609, RESELLER, f08c47fec0942fa0 #MMAdsenseLatAm
google.com, pub-3012726961203063, RESELLER, f08c47fec0942fa0 #MMAdSenseSales

# Freestar Entries
google.com, pub-3605257360853185, RESELLER, f08c47fec0942fa0
33across.com, 0013300001cFpYHAA0, DIRECT, bbea06d9c4d2853c
triplelift.com, 12503, RESELLER, 6c33edb13117fd86
appnexus.com, 10239, RESELLER, f5ab79cb980f11d1
video.unrulymedia.com, 2439829435, DIRECT
conversantmedia.com, 100141,RESELLER, 03113cd04947736d
pubmatic.com, 156423, RESELLER, 5d62403b186f2ace
rubiconproject.com, 16414, RESELLER, 0bfd66d529a55807
rubiconproject.com, 21642, RESELLER, 0bfd66d529a55807
amxrtb.com, 105199372, DIRECT
appnexus.com,12290,RESELLER,f5ab79cb980f11d1
pubmatic.com, 158355, RESELLER
rubiconproject.com, 23844, RESELLER
openx.com, 559680764, RESELLER
sonobi.com,7f5fa520f8,RESELLER,d1a215d9eb5aee9e
alkimiexchange.com, 8375, DIRECT
aps.amazon.com,0ab198dd-b265-462a-ae36-74e163ad6159,DIRECT
onetag.com,770a440e65869c2,RESELLER
smartadserver.com, 4125, RESELLER, 060d053dcf45cbf3
supply.colossusssp.com, 836, RESELLER, 6c5b49d96ec1b458
xandr.com, 10490, RESELLER
yieldmo.com, 3078438591206989879, RESELLER
admanmedia.com, 844, DIRECT
pubmatic.com, 158481, RESELLER, 5d62403b186f2ace
rubiconproject.com, 14558, RESELLER, 0bfd66d529a55807
blockthrough.com, 5714937848528896, DIRECT
themediagrid.com, 7E2DLW, RESELLER, 35d5010d7789b49d
video.unrulymedia.com, 553875126, RESELLER
trustedstack.com, TS3YM7Z35, RESELLER
trustedstblockthrough.com, 5714937848528896, DIRECT
media.net, 8PR4460XU, RESELLER
pubmatic.com, 164187, RESELLER, 5d62403b186f2ace
rubiconproject.com, 26144, RESELLER, 0bfd66d529a55807
emxdgt.com, 152, DIRECT, 1e1d41537f7cad7f
conversantmedia.com, 100316, RESELLER, 03113cd04947736d
connatix.com, 101804, DIRECT, 2af98acdee0e81ed
google.com, pub-1929615694373103, RESELLER, f08c47fec0942fa0
rubiconproject.com, 19564, RESELLER, 0bfd66d529a55807
pubmatic.com, 156592, RESELLER, 5d62403b186f2ace
pubmatic.com, 163106, RESELLER, 5d62403b186f2ace
google.com, pub-5717092533913515, RESELLER, f08c47fec0942fa0
gannett.com, 22597229605, RESELLER
openx.com, 539496816, RESELLER, 6a698e2ec38604c6
indexexchange.com, 190549, RESELLER, 50b1c356f2c5c8fc
triplelift.com, 12200, RESELLER, 6c33edb13117fd86
telaria.com, cd009, RESELLER, 1a4e959a1b50034a
freewheel.tv, 1064705, RESELLER
media.net, 8CUP5F2LD, RESELLER
video.unrulymedia.com, 687806135, RESELLER
criteo.com, B-061740, DIRECT, 9fac4a4a87c2a44f
themediagrid.com, I1ZD25, DIRECT, 35d5010d7789b49d
adingo.jp, 23401, DIRECT
pubmatic.com, 156313, RESELLER, 5d62403b186f2ace
gumgum.com, 12907, RESELLER, ffdef49475d318a9
rubiconproject.com, 23434, RESELLER, 0bfd66d529a55807
pubmatic.com, 157897, RESELLER, 5d62403b186f2ace
appnexus.com, 2758, RESELLER, f5ab79cb980f11d1
contextweb.com, 558355, RESELLER, 89ff185a4c4e857c
indexexchange.com, 184310, DIRECT, 50b1c356f2c5c8fc
indexexchange.com, 186431, DIRECT, 50b1c356f2c5c8fc
indexexchange.com, 193334, DIRECT, 50b1c356f2c5c8fc
indexexchange.com, 184310, RESELLER, 50b1c356f2c5c8fc
indexexchange.com, 186431, RESELLER, 50b1c356f2c5c8fc
indexexchange.com, 193334, RESELLER, 50b1c356f2c5c8fc
indexexchange.com, 209583, DIRECT, 50b1c356f2c5c8fc
indexexchange.com, 209583, RESELLER, 50b1c356f2c5c8fc
inmobi.com, 44ce8dca19d54696bbb793d4607fecb0, DIRECT, 83e75a7ae333ca9d
rubiconproject.com, 11726, RESELLER, 0bfd66d529a55807
rubiconproject.com, 12266, RESELLER, 0bfd66d529a55807
conversantmedia.com, 40881, RESELLER, 03113cd04947736d
loopme.com, 9724, RESELLER, 6c8d5f95897a5a3b
lijit.com, 502742, RESELLER, fafdf38b16bf6b2b
insticator.com,e0247337-444d-44f9-8a10-45f38333b383,DIRECT,b3511ffcafb23a32
sharethrough.com,Q9IzHdvp,RESELLER,d53b998a7bd4ecd2
pubmatic.com,95054,RESELLER,5d62403b186f2ace
rubiconproject.com,17062,RESELLER,0bfd66d529a55807
smaato.com,1100058355,RESELLER,07bcf65f187117b4
openx.com,558230700,RESELLER,6a698e2ec38604c6
pmc.com,1242710,DIRECT,8dd52f825890bb44
rubiconproject.com,10278,RESELLER,0bfd66d529a55807
rubiconproject.com, 16924, DIRECT, 0bfd66d529a55807
rubiconproject.com, 17486, DIRECT, 0bfd66d529a55807
media.net, 8CUJ8GUQF, DIRECT
media.net, 8CUFH1GPH, DIRECT
pubmatic.com, 159463, RESELLER, 5d62403b186f2ace
rubiconproject.com, 19396, Reseller, 0bfd66d529a55807
openx.com, 537100188, RESELLER, 6a698e2ec38604c6
onetag.com, 5d49f482552c9b6, Reseller
themediagrid.com, 6VZ9I7, DIRECT, 35d5010d7789b49d
themediagrid.com, SRU5VK, DIRECT, 35d5010d7789b49d
appnexus.com, 7125, DIRECT
minutemedia.com, 014kg58nmg7hk08tk, DIRECT
rubiconproject.com, 17598, RESELLER, 0bfd66d529a55807
pubmatic.com, 161683, RESELLER, 5d62403b186f2ace
triplelift.com, 6030, RESELLER, 6c33edb13117fd86
appnexus.com, 8381, RESELLER, f5ab79cb980f11d1
sharethrough.com, xz7QjFBY, RESELLER, d53b998a7bd4ecd2
yieldmo.com, 2954622693783052507, RESELLER
nativo.com, 5764, DIRECT, 59521ca7cc5e9fee
nativo.com, 5990-OB, RESELLER, 59521ca7cc5e9fee
appnexus.com, 8035, RESELLER, f5ab79cb980f11d1
pubmatic.com, 156500, RESELLER, 5d62403b186f2ace
rubiconproject.com, 16156, RESELLER, 0bfd66d529a55807
zetaglobal.net, 989, RESELLER
smaato.com, 1100057947, RESELLER, 07bcf65f187117b4
ogury.com, 5111a14e-6753-43d0-9d4d-64177a0dfa68, RESELLER
appnexus.com, 11470, RESELLER
pubmatic.com, 163238, RESELLER, 5d62403b186f2ace
smartadserver.com, 4537, RESELLER, 060d053dcf45cbf3
rubiconproject.com, 25198, RESELLER, 0bfd66d529a55807
video.unrulymedia.com, 533898005, RESELLER
onetag.com, 65e2f0d9f4ee117-OB, DIRECT
openx.com, 539181723, DIRECT, 6a698e2ec38604c6
openx.com, 539618221, DIRECT, 6a698e2ec38604c6
openx.com, 539900106, DIRECT, 6a698e2ec38604c6
openx.com, 545709546, DIRECT, 6a698e2ec38604c6
openx.com, 561209331, DIRECT, 6a698e2ec38604c6
openx.com, 561342917, DIRECT, 6a698e2ec38604c6
openx.com, 562454596, RESELLER, 6a698e2ec38604c6
opera.com,pub11033021832064,DIRECT,55a0c5fd61378de3
sabio.us,100092,reseller,96ed93aaa9795702
rubiconproject.com,17608,RESELLER,0bfd66d529a55807
themediagrid.com, A6CWLO, RESELLER, 35d5010d7789b49d
pubmatic.com,165340,RESELLER,5d62403b186f2ace
lijit.com,465542,RESELLER,fafdf38b16bf6b2b
primis.tech, 27975, DIRECT
pubmatic.com, 156595, RESELLER, 5d62403b186f2ace
google.com, pub-1320774679920841, RESELLER, f08c47fec0942fa0
rubiconproject.com, 20130, RESELLER, 0bfd66d529a55807
openx.com, 540258065, RESELLER, 6a698e2ec38604c6
freewheel.tv, 19129, RESELLER
freewheel.tv, 19133, RESELLER
indexexchange.com, 191923, RESELLER, 50b1c356f2c5c8fc
sharethrough.com, flUyJowI, RESELLER, d53b998a7bd4ecd2
triplelift.com, 8210, RESELLER, 6c33edb13117fd86
Media.net, 8CU695QH7, RESELLER
video.unrulymedia.com, 2338962694, RESELLER
yahoo.com, 59260, RESELLER
appnexus.com, 16007, RESELLER, f5ab79cb980f11d1
smartadserver.com, 3436, RESELLER, 060d053dcf45cbf3
pmc.com, 1240739, DIRECT, 8dd52f825890bb44
rubiconproject.com, 10278, RESELLER, 0bfd66d529a55807
video.unrulymedia.com, 776418614052335749, RESELLER
sharethrough.com, jbYv3ec8, RESELLER, d53b998a7bd4ecd2
ottadvisors.com, 122034096467, RESELLER
pubmatic.com, 156696, DIRECT, 5d62403b186f2ace
pubmatic.com, 157235, DIRECT, 5d62403b186f2ace
pubmatic.com, 160726, DIRECT, 5d62403b186f2ace
lkqd.net, 446, DIRECT, 59c49fa9598a0117
Contextweb.com, 560313,DIRECT, 89ff185a4c4e857c
rubiconproject.com, 26184, RESELLER, 0bfd66d529a55807
mediafuse.com, 720, RESELLER
appnexus.com, 9538, RESELLER, f5ab79cb980f11d1
rubiconproject.com, 24434, RESELLER, 0bfd66d529a55807
risecodes.com, 61df247711c68300011cd447, DIRECT
sharethrough.com, 5926d422, RESELLER, d53b998a7bd4ecd2
pubmatic.com, 160295, RESELLER, 5d62403b186f2ace
rubiconproject.com, 23876, RESELLER, 0bfd66d529a55807
xandr.com, 14082, RESELLER
media.net, 8CUQ6928Q, RESELLER
seedtag.com, 633b0836dbf629000786de42, DIRECT
xandr.com, 4009, DIRECT, f5ab79cb980f11d1
smartadserver.com, 3050, DIRECT
rubiconproject.com, 17280, RESELLER, 0bfd66d529a55807
pubmatic.com, 157743, RESELLER, 5d62403b186f2ace
openx.com, 558758631, RESELLER, 6a698e2ec38604c6
sharethrough.com, AXS5NfBr, RESELLER, d53b998a7bd4ecd2
sharethrough.com, c3cac6b7, DIRECT, d53b998a7bd4ecd2
sharethrough.com, ZT6RLtyQ, RESELLER, d53b998a7bd4ecd2
sharethrough.com, ab571f55, RESELLER, d53b998a7bd4ecd2
smartadserver.com, 5090, DIRECT, 060d053dcf45cbf3
pubmatic.com, 156557, RESELLER
rubiconproject.com, 18694, RESELLER, 0bfd66d529a55807
smartadserver.com, 4342, RESELLER
smaato.com, 1100054897, DIRECT, 07bcf65f187117b4
rubiconproject.com, 24600, RESELLER, 0bfd66d529a55807
sharethrough.com, iBAzay96, RESELLER, d53b998a7bd4ecd2
pubmatic.com, 156177, RESELLER, 5d62403b186f2ace
contextweb.com, 558622, RESELLER, 89ff185a4c4e857c
pubmatic.com, 156425, RESELLER, 5d62403b186f2ace
risecodes.com, 67237dda498f7100018d34ff, RESELLER
sonobi.com, 9093867874, DIRECT, d1a215d9eb5aee9e
sonobi.com, f9237889c1, DIRECT, d1a215d9eb5aee9e
pubmatic.com, 166397, RESELLER, 5d62403b186f2ace
lijit.com, 239429, DIRECT, fafdf38b16bf6b2b
lijit.com, 239429-eb, DIRECT, fafdf38b16bf6b2b
openx.com, 538959099, RESELLER, 6a698e2ec38604c6
pubmatic.com, 137711, RESELLER, 5d62403b186f2ace
pubmatic.com, 156212, RESELLER, 5d62403b186f2ace
rubiconproject.com, 17960, RESELLER, 0bfd66d529a55807
video.unrulymedia.com, 2444764291, RESELLER
smaato.com, 1100056344, RESELLER, 07bcf65f187117b4
smartadserver.com, 4926, RESELLER, 060d053dcf45cbf3
springserve.com, 1132, DIRECT, a24eb641fc82e93d
appnexus.com, 10617, RESELLER
appnexus.com, 1356, RESELLER, f5ab79cb980f11d1
appnexus.com, 1908, RESELLER, f5ab79cb980f11d1
appnexus.com, 9393, RESELLER
google.com, pub-5717092533913515, DIRECT, f08c47fec0942fa0
google.com, pub-9685734445476814, DIRECT, f08c47fec0942fa0
indexexchange.com, 185192, RESELLER
openx.com, 537153564, DIRECT, 6a698e2ec38604c6
openx.com, 540226160, RESELLER, 6a698e2ec38604c6
openx.com, 540634634, RESELLER, 6a698e2ec38604c6
pubmatic.com, 157310, RESELLER, 5d62403b186f2ace
rubiconproject.com, 17346, RESELLER, 0bfd66d529a55807
teads.tv, 8917, DIRECT, 15a9c44f6d26cbe1
teads.tv, 25293, DIRECT, 15a9c44f6d26cbe1
triplelift.com, 5579, DIRECT, 6c33edb13117fd86
triplelift.com, 11420, DIRECT, 6c33edb13117fd86
triplelift.com, 11419, DIRECT, 6c33edb13117fd86
triplelift.com, 5579-EB, DIRECT, 6c33edb13117fd86
triplelift.com, 11419-EB, DIRECT, 6c33edb13117fd86
triplelift.com, 11420-EB, DIRECT, 6c33edb13117fd86
trustedstack.com, TS214MR0W, DIRECT
amxrtb.com, 105199734, RESELLER
openx.com, 559911747, RESELLER, 6a698e2ec38604c6
yieldmo.com, 3377199372461613093, RESELLER
onetag.com, 87f58fe90234d0e, RESELLER
trustx.org, 101452, DIRECT, 1d2c8a747a749d25
trustx.org, 105197, DIRECT, 1d2c8a747a749d25
trustx.org, 105198, DIRECT, 1d2c8a747a749d25
undertone.com,3617, DIRECT
rubiconproject.com, 22412, RESELLER, 0bfd66d529a55807
openx.com, 537153564, RESELLER, 6a698e2ec38604c6
pubmatic.com, 160318, RESELLER, 5d62403b186f2ace
video.unrulymedia.com, 3565817048, DIRECT
smartadserver.com, 4849, RESELLER, 060d053dcf45cbf3
pubmatic.com, 156512, RESELLER
indexexchange.com, 183753, RESELLER
criteo.com, B-068503, DIRECT, 9fac4a4a87c2a44f
themediagrid.com, N71MIF, DIRECT, 35d5010d7789b49d
rubiconproject.com, 20986, RESELLER, 0bfd66d529a55807
Yahoo.com, 57064, DIRECT, e1a5b5b6e3255540
yahoo.com, 57064, RESELLER, e1a5b5b6e3255540
yahoo.com, 59212, DIRECT, e1a5b5b6e3255540
yieldmo.com, 2396533383539662849, DIRECT
yieldmo.com, 2753675785523896670, DIRECT
yieldmo.com, 2266071064017274661, DIRECT
contextweb.com, 561118, RESELLER, 89ff185a4c4e857c
appnexus.com, 7911, RESELLER
rubiconproject.com, 17070, RESELLER, 0bfd66d529a55807
rhythmone.com, 3463482822,RESELLER,a670c89d4a324e47
video.unrulymedia.com, 3463482822, RESELLER
pubmatic.com, 160648, RESELLER, 5d62403b186f2ace

# PUBLISHER SPECIFIC ADS.TXT INFO BELOW THIS LINE
triplelift.com, 13931, DIRECT, 6c33edb13117fd86
');
    }
}
