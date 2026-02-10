<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240129070016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updating the booking service auto-post message for user 678336';
    }

    public function up(Schema $schema): void
    {
        $message = <<<HTML
Thanks for reaching out to AwardWallet about your upcoming trip! Please read through this message completely so you understand exactly what you can expect from our service.
<br><br>
We will now do a preliminary search to judge the feasibility of your request based on the parameters given and the point balances provided. If we believe we can help, we will send an invoice for a <strong>nonrefundable</strong> $250 booking fee based on a round trip. Should your trip encompass additional flights, there will be an additional fee of $100 per direction (a direction is any sequence of flights with less than 24hrs between them).
<br><br>
Once we find an option you&#8217;d like to book, we&#8217;ll provide instructions or assist in making any points transfers necessary and book the itinerary. Any post-booking service (voluntary changes, seat assignments, schedule changes, etc.) will incur a $100 per person, per request fee.
<br><br>
We ask that you keep in mind a few things throughout this process:
<ul>
<li>Our search is only as good as the information you provide. Please make sure your point balances are current.</li>
<li>We can only search award space the airlines make available. We can&#8217;t make airlines open up award space, and we don&#8217;t have a crystal ball to predict what may open up.</li>
<li>Award availability changes quickly, so do not hesitate if you see something you like. We will do our best to respond to you quickly, but keep in mind we keep regular office hours during weekdays.</li>
</ul>
We will be in touch shortly with an assessment of feasibility.
HTML;
        $this->addSql("
            UPDATE AbBookerInfo
            SET AutoReplyMessage = ?
            WHERE UserID = ?
        ", [$message, 678336]);
    }

    public function down(Schema $schema): void
    {
        $message = <<<HTML
Thanks for reaching out to AwardWallet about your upcoming trip! Please read through this message completely so you understand exactly what you can expect from our service.
<br><br>
We will now do a preliminary search to judge the feasibility of your request based on the parameters given and the point balances provided.
If we believe we can help, we will send an invoice for a <strong>nonrefundable</strong> $50 per person search fee. Upon payment, we will begin the search and provide options.
<br><br>
Once we find an option you&#8217;d like to book, we&#8217;ll provide instructions or assist in making any points transfers necessary and book the itinerary. We will then invoice you $100 per person for each direction. (Any layover longer than 24 hours is a new direction). Any post-booking service (voluntary changes, seat assignments, schedule changes, etc.) will incur a $75 per person, per request fee.
<br><br>
We ask that you keep in mind a few things throughout this process:
<ul>
<li>Our search is only as good as the information you provide. Please make sure your point balances are current.</li>
<li>We can only search award space the airlines make available. We can&#8217;t make airlines open up award space, and we don&#8217;t have a crystal ball to predict what may open up. </li>
<li>Award availability changes quickly, so do not hesitate if you see something you like. We will do our best to respond to you quickly, but keep in mind we keep regular office hours during weekdays.</li>
</ul>
We will be in touch shortly with an assessment of feasibility.
HTML;
        $this->addSql("
            UPDATE AbBookerInfo
            SET AutoReplyMessage = ?
            WHERE UserID = ?
        ", [$message, 678336]);
    }
}
