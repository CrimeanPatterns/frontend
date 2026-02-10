<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\Predictor\ComparatorInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\LoungeNormalized;

class BrandRecognitionComparator implements ComparatorInterface
{
    private const BRAND_DICTIONARY = [
        // airlines
        'Air Canada' => ['air canada', 'maple leaf'],
        'Air China' => ['air china'],
        'Air France' => ['air france', 'la première', 'la premiere'],
        'Air India' => ['air india', 'maharajah', 'maharaja'],
        'Air New Zealand' => ['air new zealand', 'koru'],
        'Aeromexico' => ['aeromexico', 'salon premier', 'terraza premier'],
        'Alaska Airlines' => ['alaska airlines'],
        'American Airlines' => ['american airlines', 'admirals club', 'flagship'],
        'ANA' => ['ana', 'all nippon airways'],
        'Asiana' => ['asiana'],
        'Austrian Airlines' => ['austrian airlines'],
        'Avianca' => ['avianca'],
        'Bangkok Airways' => ['bangkok airways', 'blue ribbon', 'boutique'],
        'British Airways' => ['british airways', 'concorde room', 'ba'],
        'Cathay Pacific' => ['cathay pacific', 'the wing', 'the pier', 'the deck'],
        'China Airlines' => ['china airlines', 'dynasty'],
        'China Eastern' => ['china eastern', 'shanghai airlines'],
        'China Southern' => ['china southern', 'sky pearl'],
        'Copa' => ['copa', 'copa club'],
        'Delta' => ['delta', 'delta sky club', 'delta one', 'sky club'],
        'Emirates' => ['emirates'],
        'Ethiopian Airlines' => ['ethiopian', 'cloud nine', 'shebamiles'],
        'Etihad' => ['etihad'],
        'EVA Air' => ['eva air', 'the star', 'the garden', 'the infinity'],
        'Finnair' => ['finnair', 'platinum wing'],
        'Garuda Indonesia' => ['garuda indonesia', 'garudamiles'],
        'GOL' => ['gol', 'gol premium', 'gol smiles'],
        'Gulf Air' => ['gulf air', 'falcon gold'],
        'Hainan Airlines' => ['hainan airlines', 'hna club', 'fortune wings'],
        'Hawaiian Airlines' => ['hawaiian airlines', 'plumeria'],
        'Iberia' => ['iberia', 'dali', 'velázquez', 'velazquez'],
        'Japan Airlines' => ['japan airlines', 'jal', 'sakura'],
        'JetBlue' => ['jetblue'],
        'KLM' => ['klm', 'crown'],
        'Korean Air' => ['korean air', 'kal'],
        'LATAM' => ['latam'],
        'Lufthansa' => ['lufthansa', 'senator', 'first terminal', 'lh'],
        'Malaysia Airlines' => ['malaysia airlines', 'golden'],
        'Oman Air' => ['oman air', 'al khareef'],
        'Philippine Airlines' => ['philippine airlines', 'mabuhay'],
        'Qantas' => ['qantas', 'chairman', 'qf'],
        'Qatar Airways' => ['qatar airways', 'al mourjan', 'al safwa', 'orchard', 'qr'],
        'Royal Air Maroc' => ['royal air maroc', 'zenith', 'le zenith', 'casablanca', 'oasis'],
        'Royal Brunei' => ['royal brunei'],
        'SAS' => ['sas', 'scandinavian'],
        'Saudia' => ['saudia', 'al-fursan', 'alfursan'],
        'Singapore Airlines' => ['singapore airlines', 'silverkris', 'krisflyer', 'private room'],
        'SWISS' => ['swiss', 'alpine'],
        'TAP' => ['tap', 'atlantico'],
        'Thai Airways' => ['thai airways', 'royal silk', 'royal orchid', 'royal first'],
        'Turkish Airlines' => ['turkish airlines', 'thy', 'miles & smiles'],
        'United Airlines' => ['united airlines', 'polaris', 'united club'],
        'Virgin Atlantic' => ['virgin atlantic', 'clubhouse', 'revivals'],
        'Virgin Australia' => ['virgin australia', 'beyond'],
        'WestJet' => ['westjet', 'elevation'],
        'Xiamen Airlines' => ['xiamen airlines', 'xiamenair'],
        'Air Astana' => ['air astana', 'nomad club'],
        'Air Baltic' => ['air baltic', 'airbaltic'],
        'Air Belgium' => ['air belgium'],
        'Air Europa' => ['air europa'],
        'Air Malta' => ['air malta', 'la valette'],
        'Air Mauritius' => ['air mauritius', 'amedee maingard'],
        'Air Tahiti Nui' => ['air tahiti nui'],
        'AirAsia' => ['airasia', 'premium red', 'tunai'],
        'Alitalia' => ['alitalia', 'casa alitalia', 'piazza del popolo'],
        'Aer Lingus' => ['aer lingus', 'revival'],
        'Azerbaijan Airlines' => ['azerbaijan airlines', 'azal'],
        'Bamboo Airways' => ['bamboo airways'],
        'Bulgaria Air' => ['bulgaria air'],
        'Cebu Pacific' => ['cebu pacific'],
        'Cyprus Airways' => ['cyprus airways'],
        'EgyptAir' => ['egyptair', 'kochab', 'gienah', 'alioth', 'almeisan', 'el teir'],
        'EL AL' => ['el al', 'king david'],
        'Fiji Airways' => ['fiji airways', 'tabua club', 'flying fijian'],
        'ITA Airways' => ['ita airways', 'piazza di spagna', 'hangar', 'piazza della scala'],
        'JAL' => ['jal', 'japan airlines', 'sakura', 'diamond premier'],
        'Kenya Airways' => ['kenya airways', 'pride', 'simba', 'asante'],
        'LOT Polish Airlines' => ['lot polish', 'polonez', 'mazurek'],
        'Middle East Airlines' => ['middle east airlines', 'cedar'],
        'Norwegian' => ['norwegian', 'norwegian air'],
        'Pegasus Airlines' => ['pegasus airlines', 'pegasus'],
        'Royal Jordanian' => ['royal jordanian', 'crown'],
        'SriLankan Airlines' => ['srilankan airlines', 'serendib', 'serendiva', 'silk route'],
        'TAAG Angola' => ['taag angola', 'taag'],
        'TAME' => ['tame'],
        'Tunisair' => ['tunisair', 'espace privilege'],
        'Ukraine International' => ['ukraine international', 'panorama'],
        'Uzbekistan Airways' => ['uzbekistan airways'],
        'Vistara' => ['vistara'],
        'Vueling' => ['vueling'],
        'WizzAir' => ['wizzair', 'wizz air'],
        'Aegean Airlines' => ['aegean airlines', 'aegean'],
        'Pakistan International Airlines' => ['pakistan international airlines', 'pia'],

        // alliances
        'Star Alliance' => ['star alliance', 'star', 'star-alliance'],
        'SkyTeam' => ['skyteam'],
        'oneworld' => ['oneworld'],

        // hotels
        'InterContinental' => ['intercontinental', 'balaka'],
        'Dubai International Hotel' => ['dubai international hotel'],
        'Sama Sama' => ['sama sama'],
        'Tryp' => ['tryp'],
        'YotelAir' => ['yotelair', 'yotel'],
        'Aerotel' => ['aerotel'],
        'Westin' => ['westin'],
        'St. Regis' => ['st. regis', 'st regis'],
        'Hilton' => ['hilton'],
        'Marriott' => ['marriott', 'bonvoy'],
        'Sheraton' => ['sheraton'],
        'Holiday Inn' => ['holiday inn'],
        'Ibis' => ['ibis hotel', 'ibis'],
        'Hyatt' => ['hyatt', 'park hyatt'],
        'Radisson' => ['radisson', 'radisson blu'],
        'Moxy' => ['moxy'],
        '9 Hours' => ['9 hours', 'nine hours'],
        'Novotel' => ['novotel'],
        'Sofitel' => ['sofitel'],

        // financial institutions
        'American Express' => ['american express', 'amex', 'centurion'],
        'Citibank' => ['citibank', 'citi'],
        'HSBC' => ['hsbc'],
        'Capital One' => ['capital one', 'landing'],
        'Chase' => ['chase', 'sapphire'],
        'Desjardins' => ['desjardins', 'odyssey', 'odyssee'],
        'National Bank' => ['national bank'],
        'CitiBanamex' => ['citibanamex', 'banamex'],
        'Banco Safra' => ['banco safra', 'safra', 'espaco banco'],
        'ICBC' => ['icbc'],
        'FNB' => ['fnb'],
        'Banco Atlantida' => ['banco atlantida', 'atlantida'],
        'FirstBank' => ['firstbank'],
        'Bradesco' => ['bradesco', 'cartões'],
        'BRB' => ['brb', 'latitude'],
        'BBVA' => ['bbva'],
        'RuPay' => ['rupay'],
        'JCB' => ['jcb'],
        'Credomatic' => ['credomatic', 'bac credomatic'],
        'Bank Alfalah' => ['bank alfalah', 'alfalah'],
        'Daegu Bank' => ['daegu bank'],
        'Raiffeisenbank' => ['raiffeisenbank', 'raiffeisen'],
        'Standard Chartered' => ['standard chartered'],
        'Converse Bank' => ['converse bank'],
        'Scotia' => ['scotia', 'scotiabank'],
        'Erste' => ['erste'],
        'SDN Communications' => ['sdn communications'],
        'LHV' => ['lhv'],
        'Mastercard' => ['mastercard', 'pearl metro'],
        'City Bank' => ['city bank'],
        'Visa' => ['visa infinite', 'visa'],
        'UCB' => ['ucb imperial', 'ucb'],
        'Absa' => ['absa', 'barclays africa'],
        'Access Bank' => ['access bank'],
        'Akbank' => ['akbank'],
        'ANZ' => ['anz bank', 'australia and new zealand'],
        'Axis Bank' => ['axis bank'],
        'Bangkok Bank' => ['bangkok bank'],
        'Bank Mandiri' => ['bank mandiri', 'mandiri'],
        'Barclays' => ['barclays'],
        'BDO' => ['bdo', 'banco de oro'],
        'BNP Paribas' => ['bnp paribas', 'bnp'],
        'Commercial Bank' => ['commercial bank'],
        'CaixaBank' => ['caixabank', 'la caixa'],
        'Deutsche Bank' => ['deutsche bank'],
        'Eastern Bank' => ['eastern bank', 'skylounge'],
        'Emirates NBD' => ['emirates nbd', 'nbd'],
        'Garanti BBVA' => ['garanti bbva', 'garanti'],
        'Gazprombank' => ['gazprombank'],
        'Halk Bank' => ['halk bank', 'halkbank'],
        'Habib Bank' => ['habib bank', 'hbl'],
        'Intesa Sanpaolo' => ['intesa sanpaolo', 'intesa'],
        'Industrial Bank' => ['industrial bank'],
        'Loyal Bank' => ['loyal bank', 'loyalty'],
        'Maybank' => ['maybank', 'maybank lounge'],
        'MTB' => ['mtb', 'mutual trust bank'],
        'Nedbank' => ['nedbank'],
        'Piraeus Bank' => ['piraeus bank'],
        'RBL Bank' => ['rbl bank'],
        'Santander' => ['santander'],
        'Sberbank' => ['sberbank'],
        'Societe Generale' => ['societe generale'],
        'Tinkoff' => ['tinkoff'],
        'UBS' => ['ubs'],
        'UniCredit' => ['unicredit'],
        'VTB' => ['vtb'],
        'Yes Bank' => ['yes bank'],
        'Zenith Bank' => ['zenith bank'],

        // regional brands
        'Aena VIP' => ['aena vip', 'neptuno', 'cibeles', 'puerta del sol', 'plaza mayor'],
        'Aeroportos VIP Club' => ['aeroportos vip club', 'aeroporti vip'],
        'BGS Premier' => ['bgs premier'],
        'DiamondHead' => ['diamondhead'],
        'Dan' => ['dan lounge', 'dan plus'],
        'dnata' => ['dnata', 'skyview'],
        'Dusit' => ['dusit', 'dusit thani'],
        'GVK' => ['gvk lounge', 'gvk'],
        'International Lounge Network' => ['international lounge network', 'iln'],
        'Ikes' => ['ikes lounge'],
        'ICTS' => ['icts'],
        'Loungebuddy' => ['loungebuddy'],
        'Loyalty' => ['loyalty lounge'],
        'Matina' => ['matina', 'matina gold'],
        'Menzies' => ['menzies'],
        'NAC' => ['nac lounge'],
        'OSL' => ['osl lounge'],
        'Pacific Club' => ['pacific club', 'salones vip pacific'],
        'Petra' => ['petra lounge'],
        'PrimeTime' => ['primetime'],
        'Priority Pass' => ['priority pass'],
        'Servisair' => ['servisair'],
        'Sheltair' => ['sheltair'],
        'TAV' => ['tav', 'prime class', 'primeclass'],
        'TGM' => ['tgm', 'tgm asian'],
        'Travelers' => ['travelers lounge', 'travellers'],
        'UTG Aviation' => ['utg aviation', 'utg'],
        'VIP Service' => ['vip service club', 'vip service'],

        // lounge brands
        'Plaza Premium' => ['plaza premium', 'blossom', 'blush'],
        'Aspire' => ['aspire', 'club aspire', 'the house by aspire'],
        'No1 Lounges' => ['no1', 'clubrooms', 'my lounge'],
        'The Club' => ['the club'],
        'HelloSky' => ['hellosky'],
        'Minute Suites' => ['minute suites'],
        'Marhaba' => ['marhaba'],
        'Ahlan' => ['ahlan'],
        'Escape Lounges' => ['escape', 'centurion studio partner'],
        'Primeclass' => ['primeclass'],
        'Pearl Lounge' => ['pearl lounge', 'sanbra'],
        'Miracle Lounge' => ['miracle'],
        'Coral' => ['coral executive', 'coral premium', 'coral beach', 'coral first', 'coral finest', 'cocoon', 'cosmo'],
        'USO' => ['uso'],
        'IASS' => ['iass', 'noa', 'kocoo'],
        'sleep n\' fly' => ['sleep n\' fly', 'sleep \'n fly', 'sleepnfly'],
        'Ambaar' => ['ambaar'],
        'PAGSS' => ['pagss'],
        'SLOW' => ['slow', 'slow xs'],
        'Fraport' => ['fraport'],
        'Swissport' => ['swissport'],
        'SATS' => ['sats'],
        'Wingtips' => ['wingtips'],
        'Be Relax' => ['be relax'],
        'Bidvest' => ['bidvest'],
        'The House' => ['the house'],
        'Capsule Transit' => ['capsule transit'],
        'XpresSpa' => ['xpresspa'],
        'Encalm' => ['encalm', 'prive'],
        'W Lounge' => ['w lounge', 'w premium', 'w airport rooms', 'frevo', 'sao joao', 'pier', '5th avenue', 'the west'],
        'Skyserv' => ['skyserv', 'aristotle onassis', 'melina merkouri'],
        'Goldair' => ['goldair'],
        'Lexus' => ['lexus'],
        'Club S.E.A.' => ['club s.e.a.', 'sala leonardo', 'sala piranesi', 'sala pergolesi', 'sala montale', 'sala monteverdi', 'alda merini'],
        'Advantage VIP' => ['advantage vip'],
        'AMAE' => ['amae'],
        'Skylife' => ['skylife', 'pilot cafe'],
        'Club Mobay' => ['club mobay'],
        'SH Premium' => ['sh premium'],
        'The Centurion' => ['the centurion'],
        'Sky Suite' => ['sky suite'],
        'Jabbrrbox' => ['jabbrrbox'],
        'Siesta Box' => ['siesta box'],
        'Skyview' => ['skyview'],
        'Airr' => ['airr'],
        'Privium' => ['privium'],
        'Air Lounge' => ['air lounge'],
        'BRT Lounge' => ['brt lounge'],
        'Skyward' => ['skyward gaming'],
        'Air Space' => ['air space', 'airspace'],
        'Ahlein' => ['ahlein', 'comfy', 'emerald'],
        'Library Lounge' => ['library lounge'],

        // food and beverage brands
        'Wolfgang Puck' => ['wolfgang puck'],
        'PF Chang\'s' => ['pf chang\'s', 'pf changs'],
        'Landry\'s' => ['landry\'s', 'landrys'],
        'Burger King' => ['burger king'],
        'Carl\'s Jr' => ['carl\'s jr', 'carls jr'],
        'Pizza Express' => ['pizza express'],
        'Starbucks' => ['starbucks'],
        'Tully\'s Coffee' => ['tully\'s coffee', 'tullys coffee'],
        'Tim Hortons' => ['tim hortons', 'tims coffee'],
        'KFC' => ['kfc'],
        'Dicos' => ['dicos'],
        'Subway' => ['subway'],
        'Bottega' => ['bottega', 'prosecco'],
        'Gordon Ramsay' => ['gordon ramsay', 'plane food'],
        'Jamie\'s Deli' => ['jamie\'s deli', 'jamies deli'],
        'Coffee Bean' => ['coffee bean', 'tea leaf'],
        'Puro Gusto' => ['puro gusto'],
        'Bobby Van\'s' => ['bobby van\'s', 'bobby vans'],
        'Ajisen Ramen' => ['ajisen ramen', 'ajisen'],
        'Pacific Coffee' => ['pacific coffee'],
        'Heineken' => ['heineken', 'world bar'],
        'Peroni' => ['peroni'],
        'Marché' => ['marché', 'marche', 'moevenpick', 'movenpick'],
        'Johnny Rockets' => ['johnny rockets'],
        'Segafredo' => ['segafredo'],
        'Chef Geoff\'s' => ['chef geoff\'s', 'chef geoffs'],
        'Crystal Jade' => ['crystal jade'],
        'DQ' => ['dq', 'dairy queen'],
        'Tai Hing' => ['tai hing'],
        'Pelicana' => ['pelicana'],
        'Twiga' => ['twiga'],
        'BrewDog' => ['brewdog'],
        'Costa Coffee' => ['costa coffee', 'costa'],
        'Mefou' => ['mefou'],
        'Duck De Burger' => ['duck de burger'],
        'Kafelaku' => ['kafelaku'],
        'illy' => ['illy', 'espressamente illy'],
        'Mango Tree' => ['mango tree'],
        'PGA' => ['pga', 'pga tour'],
        'Au Bon Pain' => ['au bon pain'],
        'Brioche Dorée' => ['brioche doree', 'brioche dorée'],
        'Camden Food' => ['camden food', 'camden bar'],
        'Chili\'s' => ['chili\'s', 'chilis'],
        'Dunkin\'' => ['dunkin\'', 'dunkin'],
        'Eat & Go' => ['eat & go', 'eat and go'],
        'Exki' => ['exki'],
        'Figs' => ['figs', 'todd english'],
        'Giraffe' => ['giraffe restaurant', 'giraffe'],
        'Hard Rock Cafe' => ['hard rock cafe', 'hard rock'],
        'Havana Club' => ['havana club'],
        'Hudson' => ['hudson', 'hudsons coffee'],
        'Joe & The Juice' => ['joe & the juice', 'joe and the juice'],
        'La Place' => ['la place'],
        'Leon' => ['leon'],
        'Nando\'s' => ['nando\'s', 'nandos'],
        'Panda Express' => ['panda express'],
        'Paul' => ['paul bakery', 'paul'],
        'Peet\'s Coffee' => ['peet\'s coffee', 'peets coffee'],
        'Pret A Manger' => ['pret a manger', 'pret'],
        'Shake Shack' => ['shake shack'],
        'Wagamama' => ['wagamama'],
        'WH Smith' => ['wh smith'],
        'YO! Sushi' => ['yo! sushi', 'yo sushi'],

        // regional and local brands
        'Adinkra' => ['adinkra'],
        'Akwaaba' => ['akwaaba'],
        'Flamingo' => ['flamingo lounge'],
        'Galdos' => ['galdos'],
        'Indira Gandhi' => ['indira gandhi'],
        'Jomo Kenyatta' => ['jomo kenyatta'],
        'Karibou' => ['karibou'],
        'Mashonzha' => ['mashonzha'],
        'Mombasa' => ['mombasa'],
        'Nirvana' => ['nirvana lounge'],
        'Oberoi' => ['oberoi'],
        'Shongololo' => ['shongololo'],
        'Tanzanite' => ['tanzanite'],
        'Umphafa' => ['umphafa'],
        'Zanzibar' => ['zanzibar'],

        // other brands
        'DragonPass' => ['dragonpass'],
        'Diners' => ['diners club', 'diners'],
        'Sandals Resorts' => ['sandals resorts', 'sandals'],
        'Club Med' => ['club med', 'par club med', 'by club med'],
        'The Grand Lounge Elite' => ['the grand lounge elite', 'terraza premier'],
        'ROAM Fitness' => ['roam fitness'],
        'Regus Express' => ['regus express'],
        'GoodLife Fitness' => ['goodlife fitness'],
        'Timeless Spa' => ['timeless spa'],
        'Juvenis Spa' => ['juvenis spa'],
        'Louis Vuitton' => ['louis vuitton'],
        'Extime' => ['extime'],
        'Royal Host' => ['royal host'],
        'JW' => ['jw sky'],
        'Nine Hours' => ['nine hours', '9h nine hours'],
        'Aeroporti di Roma' => ['aeroporti di roma', 'adr vip'],
        'Aviapartner' => ['aviapartner'],
        'Canopy' => ['canopy lounge'],
        'Centurion Studio' => ['centurion studio'],
        'CarltonOne' => ['carltonone'],
        'DreamFolks' => ['dreamfolks'],
        'Diners Club' => ['diners club'],
        'Global Blue' => ['global blue', 'tax refund'],
        'iGA' => ['iga', 'istanbul grand airport'],
        'Infinity' => ['infinity lounge'],
        'JetQuay' => ['jetquay'],
        'Levity' => ['levity lounge'],
        'Lounge Key' => ['lounge key', 'loungekey'],
        'OTG' => ['otg'],
        'Pontus' => ['pontus', 'frithiof'],
        'Qinhuai' => ['qinhuai snacks'],
        'Root98' => ['root98'],
        'SSP' => ['ssp'],
        'travelex' => ['travelex'],
        'VFS Global' => ['vfs global'],
        'Younique' => ['younique'],
    ];

    private const COMMON_WORDS = [
        'lounge', 'airport', 'club', 'international', 'business', 'first', 'class',
        'vip', 'departure', 'arrival', 'terminal', 'gold', 'silver', 'premium',
        'exclusive', 'the', 'and', 'new', 'elite', 'central', 'global', 'world',
        'executive', 'passenger', 'travelers', 'salon', 'room', 'service', 'area',
        'air', 'airlines', 'by',
    ];

    private const BY_PATTERNS = [
        ' by ',
        ' operated by ',
        ' managed by ',
    ];

    private array $wordIndex = [];
    private array $phraseIndex = [];

    public function __construct()
    {
        $this->buildIndices();
    }

    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float
    {
        $name1 = $lounge1->getNameNormalized();
        $name2 = $lounge2->getNameNormalized();

        if (empty($name1) || empty($name2)) {
            return 0;
        }

        $loungeAnalysis1 = $this->analyzeName($name1);
        $loungeAnalysis2 = $this->analyzeName($name2);

        return $this->determineSimilarity($loungeAnalysis1, $loungeAnalysis2);
    }

    private function analyzeName(string $name): array
    {
        $normalizedName = $this->normalizeText($name);
        $result = [
            'original_name' => $name,
            'normalized_name' => $normalizedName,
            'direct_brands' => $this->detectBrand($normalizedName),
            'by_brands' => [],
            'name_before_by' => null,
            'name_after_by' => null,
            'brand_keywords' => $this->extractBrandKeywords($normalizedName),
        ];

        foreach (self::BY_PATTERNS as $pattern) {
            if (strpos($normalizedName, $pattern) !== false) {
                $parts = explode($pattern, $normalizedName, 2);

                if (count($parts) >= 2) {
                    $result['name_before_by'] = trim($parts[0]);
                    $result['name_after_by'] = trim($parts[1]);
                    $result['by_brands'] = array_merge(
                        $this->detectBrand($result['name_after_by']),
                        $this->detectBrand($result['name_before_by'])
                    );

                    break;
                }
            }
        }

        return $result;
    }

    private function determineSimilarity(array $analysis1, array $analysis2): float
    {
        $directBrands1 = $analysis1['direct_brands'];
        $directBrands2 = $analysis2['direct_brands'];
        $byBrands1 = $analysis1['by_brands'];
        $byBrands2 = $analysis2['by_brands'];

        $anyBrands1 = $directBrands1 ?: $byBrands1;
        $anyBrands2 = $directBrands2 ?: $byBrands2;

        if ($anyBrands1 && $anyBrands2 && $this->brandsIntersect($anyBrands1, $anyBrands2)) {
            return 1.0;
        }

        if (
            ($directBrands1 && $byBrands2 && $this->brandsIntersect($directBrands1, $byBrands2))
            || ($directBrands2 && $byBrands1 && $this->brandsIntersect($directBrands2, $byBrands1))
        ) {
            return 0.9;
        }

        $nameBeforeBy1 = $analysis1['name_before_by'];
        $nameBeforeBy2 = $analysis2['name_before_by'];

        if (
            $nameBeforeBy1 && $nameBeforeBy2
            && $nameBeforeBy1 === $nameBeforeBy2
            && $nameBeforeBy1 !== $analysis1['normalized_name']
            && $nameBeforeBy2 !== $analysis2['normalized_name']
        ) {
            return 0.8;
        }

        if (($anyBrands1 && !$anyBrands2) || (!$anyBrands1 && $anyBrands2)) {
            return 0.2;
        }

        $keywords1 = $analysis1['brand_keywords'];
        $keywords2 = $analysis2['brand_keywords'];

        $commonKeywords = array_intersect($keywords1, $keywords2);

        if (count($commonKeywords) > 0) {
            return min(
                0.7,
                0.5 + (0.1 * count($commonKeywords))
            );
        }

        return 0;
    }

    private function detectBrand(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $normalizedText = $this->normalizeText($text);
        $matchedBrands = [];

        foreach ($this->phraseIndex as $phrase => $brands) {
            if (strpos($normalizedText, $phrase) !== false) {
                foreach ($brands as $brand) {
                    $matchedBrands[$brand] = 1.0;
                }
            }
        }

        if (!empty($matchedBrands)) {
            return array_keys($matchedBrands);
        }

        $words = $this->tokenize($normalizedText);
        $brandMatches = [];

        foreach ($words as $word) {
            if (in_array($word, self::COMMON_WORDS) || strlen($word) < 2) {
                continue;
            }

            if (isset($this->wordIndex[$word])) {
                foreach ($this->wordIndex[$word] as $brand) {
                    if (!isset($brandMatches[$brand])) {
                        $brandMatches[$brand] = [
                            'words' => [],
                            'count' => 0,
                            'variation_matches' => [],
                        ];
                    }

                    $brandMatches[$brand]['words'][] = $word;
                    $brandMatches[$brand]['count']++;
                }
            }
        }

        $confirmedBrands = [];

        foreach ($brandMatches as $brand => $data) {
            $brandData = $this->getAllSignificantBrandWords($brand);

            if (empty($brandData['all_words'])) {
                $confirmedBrands[$brand] = 1.0;

                continue;
            }

            $mainMatchRatio = $this->calculateMatchRatio(
                $data['words'],
                $brandData['main_words']
            );

            $bestVariationMatch = 0;

            foreach ($brandData['variations'] as $variation => $variationWords) {
                if (empty($variationWords)) {
                    continue;
                }

                $variationMatchRatio = $this->calculateMatchRatio(
                    $data['words'],
                    $variationWords
                );

                if ($variationMatchRatio > $bestVariationMatch) {
                    $bestVariationMatch = $variationMatchRatio;
                }
            }

            $bestMatch = max($mainMatchRatio, $bestVariationMatch);

            if ($bestMatch >= 0.6) {
                $confirmedBrands[$brand] = $bestMatch;
            }
        }

        if (!empty($confirmedBrands)) {
            arsort($confirmedBrands);

            $maxConfidence = reset($confirmedBrands);
            $threshold = $maxConfidence * 0.9;
            $result = [];

            foreach ($confirmedBrands as $brand => $confidence) {
                if ($confidence >= $threshold) {
                    $result[] = $brand;
                }
            }

            return $result;
        }

        return [];
    }

    private function calculateMatchRatio(array $foundWords, array $brandWords): float
    {
        if (empty($brandWords)) {
            return 0;
        }

        $matchingWords = array_intersect($foundWords, $brandWords);

        return count(array_unique($matchingWords)) / count($brandWords);
    }

    private function getAllSignificantBrandWords(string $brand): array
    {
        $result = [
            'main_words' => $this->getSignificantBrandWords($brand),
            'variations' => [],
            'all_words' => [],
        ];

        $result['all_words'] = $result['main_words'];

        if (isset(self::BRAND_DICTIONARY[$brand])) {
            foreach (self::BRAND_DICTIONARY[$brand] as $variation) {
                $variationWords = $this->getSignificantBrandWords($variation);
                $result['variations'][$variation] = $variationWords;
                $result['all_words'] = array_unique(array_merge($result['all_words'], $variationWords));
            }
        }

        return $result;
    }

    private function getSignificantBrandWords(string $brand): array
    {
        $words = $this->tokenize($brand);
        $significant = [];

        foreach ($words as $word) {
            if (!in_array($word, self::COMMON_WORDS) && strlen($word) >= 2) {
                $significant[] = $word;
            }
        }

        return $significant;
    }

    private function extractBrandKeywords(string $text): array
    {
        $words = $this->tokenize($text);
        $keywords = [];

        foreach ($words as $word) {
            if (in_array($word, self::COMMON_WORDS) || strlen($word) < 2) {
                continue;
            }

            if (isset($this->wordIndex[$word])) {
                $keywords[] = $word;
            }
        }

        $normalizedText = $this->normalizeText($text);

        foreach ($this->phraseIndex as $phrase => $brands) {
            if (strpos($normalizedText, $phrase) !== false) {
                $keywords[] = $phrase;
            }
        }

        return array_unique($keywords);
    }

    private function brandsIntersect(?array $brands1, ?array $brands2): bool
    {
        if (!$brands1 || !$brands2) {
            return false;
        }

        $intersection = array_intersect($brands1, $brands2);

        return !empty($intersection);
    }

    private function buildIndices(): void
    {
        $this->wordIndex = [];
        $this->phraseIndex = [];

        foreach (self::BRAND_DICTIONARY as $brand => $variations) {
            $this->indexBrand($brand, $brand);

            foreach ($variations as $variation) {
                $this->indexBrand($variation, $brand);
            }
        }
    }

    private function indexBrand(string $text, string $brand): void
    {
        $words = $this->tokenize($text);

        if (count($words) > 1) {
            $key = implode(' ', $words);

            if (!isset($this->phraseIndex[$key])) {
                $this->phraseIndex[$key] = [];
            }

            if (!in_array($brand, $this->phraseIndex[$key])) {
                $this->phraseIndex[$key][] = $brand;
            }
        }

        foreach ($words as $word) {
            if (strlen($word) < 2 || in_array($word, self::COMMON_WORDS)) {
                continue;
            }

            if (!isset($this->wordIndex[$word])) {
                $this->wordIndex[$word] = [];
            }

            if (!in_array($brand, $this->wordIndex[$word])) {
                $this->wordIndex[$word][] = $brand;
            }
        }
    }

    private function tokenize(string $text): array
    {
        $normalized = $this->normalizeText($text);

        return explode(' ', $normalized);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
