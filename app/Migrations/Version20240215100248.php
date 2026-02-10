<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240215100248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updating pricing details for the booking service';
    }

    public function up(Schema $schema): void
    {
        $message = <<<HTML
Thank you for inquiring about AwardWallet’s award concierge.
<br><br>
AwardWallet proudly offers a convenient award concierge service that searches for the best award travel options to help 
you maximize your points and miles. Once you submit the request, the award concierge team will review it to ensure 
it’s feasible based on your points balances and flight availability. If we believe we can help, we will send an 
invoice for a <strong>nonrefundable</strong> $250 booking fee based on a round trip. Should your trip encompass additional flights, 
there will be an additional fee of $100 per direction (a direction is any sequence of flights with less than 24hrs 
between them). Any post-booking service (voluntary changes, seat assignments, schedule changes, etc.) will incur 
a $100 per person, per request fee.
HTML;
        $this->addSql("
            UPDATE AbBookerInfo
            SET PricingDetails = ?
            WHERE UserID = ?
        ", [$message, 678336]);
    }

    public function down(Schema $schema): void
    {
        $message = <<<HTML
Thank you for inquiring about AwardWallet’s award concierge.
<br><br>
AwardWallet proudly offers a convenient award concierge service that searches for the best award travel options 
to help you maximize your points and miles. Once you submit the request, the award concierge team will review it 
to ensure it's feasible based on your points balances and flight availability. 
At that point, a <strong>nonrefundable</strong> $50 per traveler search fee will apply. 
Once your award travel is booked, we charge a booking fee of $100 per traveler per direction (any layover longer 
than 24 hours). Any post-booking assistance costs $75 per person per request.
HTML;
        $this->addSql("
            UPDATE AbBookerInfo
            SET PricingDetails = ?
            WHERE UserID = ?
        ", [$message, 678336]);
    }
}
