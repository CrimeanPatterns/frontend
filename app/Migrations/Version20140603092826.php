<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140603092826 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $message = <<<HTML
Dear {{ name }},
<p>We are poised to be creative and resourceful with your award request, as our customized searches find awards for over 98% of requests submitted.
Please keep in mind that the airline programs have been quite timid in disclosing some important trade-offs that could impact your award, so we have the unenviable task of doing the airlines dirty work!</p>
<h3>Out Of Pocket Costs </h3>
Some airline programs assess substantial fuel surcharges, that combined with government mandated taxes and airport fees, can range from $700-$1200 per person. BUT, these programs often have far more available and convenient award routing options.<br>
Domestic positioning flights to international gateway airports may not have any award space and require a separate purchased Economy airfare.
<h3>Flight Duration</h3>
Searching for the 'Saver' lowest level award often requires an extra stop and/or longer layover.<br>
Flights to Africa typically require routing via Europe.<br>
Flights to Australia/New Zealand typically require routing via Asia, with the possibility of an overnight (which can be extended to a multi-day stopover in a number of terrific Asian cities)
<h3>Economy Flights</h3>
Some domestic and regional flights under 4 hrs may only have award space in Economy<br>
Our process is straightforward:
<ul class="listBookingMessage">
    <li>We will clearly advise you of any of the above trade-offs that could impact your award. If you agree to accept the proposed provisos as still meeting your award parameters, our service will commence.</li>
    <li>We will initiate your customized mileage award search.</li>
    <li>We will find and present your award itinerary.</li>
    <li>Upon your approval of the itinerary we will request your account information and provide transfer instructions where applicable, and expedite your booking and prepay any taxes.</li>
    <li>Tax reimbursement and booking service are payable by check. Credit card payments have a 2.9% service charge</li>
</ul>
Obviously, our goal is to be creative and resourceful on your behalf. We will endeavor to avoid or mitigate as many trade-offs as possible. But over 85% of the 1.1 BILLION Miles we have booked include at least one of these trade-offs. We find mutually acceptable awards for over 98.5% of our clients because we educate our clients about aligning their expectations - we aspire toward achieving your ideal, but need you to be prepared to accept the suitable.
HTML;

        $this->connection->executeUpdate(
            "UPDATE AbBookerInfo SET AutoReplyMessage = ? WHERE UserID = ?",
            [$message, 116000],
            [\PDO::PARAM_STR, \PDO::PARAM_INT]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
