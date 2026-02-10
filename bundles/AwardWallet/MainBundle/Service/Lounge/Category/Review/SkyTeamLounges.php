<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category\Review;

use AwardWallet\MainBundle\Service\Lounge\Category\CategoryInterface;
use AwardWallet\MainBundle\Service\Lounge\Category\ReviewInterface;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

class SkyTeamLounges implements CategoryInterface, ReviewInterface
{
    public function match(LoungeInterface $lounge): bool
    {
        /**
         * Paris Charles De Gaulle: Air France Lounge – Terminal 2F
         * Paris Charles De Gaulle: Air France Lounge – Terminal 2G
         * Paris Orly: Air France Lounge
         * Bordeaux: Air France Lounge
         * Geneva-Cointrin: Air France Lounge
         * Boston Logan Int'l: Air France Lounge
         * Washington Dulles Int'l: Air France Lounge
         * Houston George Bush Int'l: Air France Lounge
         * Houston George Bush Int'l: KLM Crown Lounge
         * New York JFK Int'l: Air France Lounge
         * San Francisco: Air France Lounge
         * Los Angeles Int'l: Air France Lounge
         * Soekarno-Hatta Int'l: Garuda Indonesia Lounge
         * I Gusti Hgurah Rai Int'l: Garuda Indonesia Lounge
         * Nairobi Jomo Kenyatta Int'l: Msafiri Lounge
         * Fozhou Airport: Xiamen Airlines First Class Lounge
         * Hangzhou Airport: Xiamen Airlines First Class Lounge
         * Jinjiang Airport: Xiamen Airlines First Class Lounge
         * Shanghai Hongqiao Int'l: Xiamen Airlines First Class Lounge
         * Shanghai Hongqiao Int'l: V28 China Eastern First & Business Class Lounge
         * Shanghai Hongqiao Int'l: V23 China Eastern First & Business Class Lounge
         * Shanghai Hongqiao Int'l: V2 Shanghai Airlines Lounge
         * Tianjin: Xiamen Airlines First Class Lounge
         * Xiamen Airport: Xiamen Airlines First Class Lounge
         * Stockholm-Arlanda: SAS Lounge
         * Copenhagen-Kastrup: SAS Lounge
         * New York Newark Liberty Int'l: SAS Lounge
         * Gothenburg-Landvetter: SAS Lounge
         * Oslo-Gardermoen: SAS Domestic Lounge
         * Chicago O'Hare Int'l: SAS Lounge
         * Jeddah King Abdulaziz Int'l: Alfursan Domestic Lounge
         * Riyadh King Khaled Int'l: Alfursan Domestic Lounge
         * Noi Bai Int'l: Lotus Lounge.
         */

        return
            ( // Paris Charles De Gaulle: Air France Lounge – Terminal 2F
                strcasecmp($lounge->getAirportCode(), 'CDG') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
                && preg_match('/\b2F\b/i', $lounge->getName())
            )
            || ( // Paris Charles De Gaulle: Air France Lounge – Terminal 2G
                strcasecmp($lounge->getAirportCode(), 'CDG') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
                && preg_match('/\b2G\b/i', $lounge->getName())
            )
            || ( // Paris Orly: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'ORY') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Bordeaux: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'BOD') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Geneva-Cointrin: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'GVA') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Boston Logan Int'l: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'BOS') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Washington Dulles Int'l: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'IAD') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Houston George Bush Int'l: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'IAH') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Houston George Bush Int'l: KLM Crown Lounge
                strcasecmp($lounge->getAirportCode(), 'IAH') == 0
                && preg_match('/\bKLM\s+Crown\s+Lounge\b/i', $lounge->getName())
            )
            || ( // New York JFK Int'l: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'JFK') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // San Francisco: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'SFO') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Los Angeles Int'l: Air France Lounge
                strcasecmp($lounge->getAirportCode(), 'LAX') == 0
                && preg_match('/\bAir\s+France\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Soekarno-Hatta Int'l: Garuda Indonesia Lounge
                strcasecmp($lounge->getAirportCode(), 'CGK') == 0
                && preg_match('/\bGaruda\s+Indonesia\s+Lounge\b/i', $lounge->getName())
            )
            || ( // I Gusti Hgurah Rai Int'l: Garuda Indonesia Lounge
                strcasecmp($lounge->getAirportCode(), 'DPS') == 0
                && preg_match('/\bGaruda\s+Indonesia\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Nairobi Jomo Kenyatta Int'l: Msafiri Lounge
                strcasecmp($lounge->getAirportCode(), 'NBO') == 0
                && preg_match('/\bMsafiri\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Fozhou Airport: Xiamen Airlines First Class Lounge
                strcasecmp($lounge->getAirportCode(), 'FOC') == 0
                && preg_match('/\bXiamen\s+Airlines\s+First\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Hangzhou Airport: Xiamen Airlines First Class Lounge
                strcasecmp($lounge->getAirportCode(), 'HGH') == 0
                && preg_match('/\bXiamen\s+Airlines\s+First\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Jinjiang Airport: Xiamen Airlines First Class Lounge
                strcasecmp($lounge->getAirportCode(), 'JJN') == 0
                && preg_match('/\bXiamen\s+Airlines\s+First\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Shanghai Hongqiao Int'l: Xiamen Airlines First Class Lounge
                strcasecmp($lounge->getAirportCode(), 'SHA') == 0
                && preg_match('/\bXiamen\s+Airlines\s+First\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Shanghai Hongqiao Int'l: V28 China Eastern First & Business Class Lounge
                strcasecmp($lounge->getAirportCode(), 'SHA') == 0
                && preg_match('/\bV28\s+China\s+Eastern\s+First\s+\&\s+Business\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Shanghai Hongqiao Int'l: V23 China Eastern First & Business Class Lounge
                strcasecmp($lounge->getAirportCode(), 'SHA') == 0
                && preg_match('/\bV23\s+China\s+Eastern\s+First\s+\&\s+Business\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Shanghai Hongqiao Int'l: V2 Shanghai Airlines Lounge
                strcasecmp($lounge->getAirportCode(), 'SHA') == 0
                && preg_match('/\bV2\s+Shanghai\s+Airlines\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Tianjin: Xiamen Airlines First Class Lounge
                strcasecmp($lounge->getAirportCode(), 'TSN') == 0
                && preg_match('/\bXiamen\s+Airlines\s+First\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Xiamen Airport: Xiamen Airlines First Class Lounge
                strcasecmp($lounge->getAirportCode(), 'XMN') == 0
                && preg_match('/\bXiamen\s+Airlines\s+First\s+Class\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Stockholm-Arlanda: SAS Lounge
                strcasecmp($lounge->getAirportCode(), 'ARN') == 0
                && preg_match('/\bSAS\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Copenhagen-Kastrup: SAS Lounge
                strcasecmp($lounge->getAirportCode(), 'CPH') == 0
                && preg_match('/\bSAS\s+Lounge\b/i', $lounge->getName())
            )
            || ( // New York Newark Liberty Int'l: SAS Lounge
                strcasecmp($lounge->getAirportCode(), 'EWR') == 0
                && preg_match('/\bSAS\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Gothenburg-Landvetter: SAS Lounge
                strcasecmp($lounge->getAirportCode(), 'GOT') == 0
                && preg_match('/\bSAS\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Oslo-Gardermoen: SAS Domestic Lounge
                strcasecmp($lounge->getAirportCode(), 'OSL') == 0
                && preg_match('/\bSAS\s+Domestic\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Chicago O'Hare Int'l: SAS Lounge
                strcasecmp($lounge->getAirportCode(), 'ORD') == 0
                && preg_match('/\bSAS\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Jeddah King Abdulaziz Int'l: Alfursan Domestic Lounge
                strcasecmp($lounge->getAirportCode(), 'JED') == 0
                && preg_match('/\bAlfursan\s+Domestic\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Riyadh King Khaled Int'l: Alfursan Domestic Lounge
                strcasecmp($lounge->getAirportCode(), 'RUH') == 0
                && preg_match('/\bAlfursan\s+Domestic\s+Lounge\b/i', $lounge->getName())
            )
            || ( // Noi Bai Int'l: Lotus Lounge
                strcasecmp($lounge->getAirportCode(), 'HAN') == 0
                && preg_match('/\bLotus\s+Lounge\b/i', $lounge->getName())
            );
    }

    public static function getBlogPostId(): int
    {
        return 204025;
    }

    public static function getPriority(): int
    {
        return 20;
    }
}
