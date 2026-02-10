<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140604025717 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $message = <<<HTML
<p>Dear {{ name }}:</p>
<p style="padding-top: 15px;">We are poised to be creative and resourceful with your award request, as our customized searches find awards for over 98% of requests submitted. Please keep in mind that the airline programs have been quite timid in disclosing some important trade-offs that could impact your award, so we have the unenviable task of doing the airlines dirty work!</p>
<p style="padding-top: 15px;">Based on your mileage resources, date range and routing, there are some likely trade-offs you will need to consider.  Please keep in mind that the airline programs are too timid to advise you of these trade-offs, so we have the unenviable task of doing the airlines dirty work!</p>

<h3 style="color: rgb(41, 120, 185); border-top: 2px solid rgb(174, 174, 174); padding: 7px 0px 0px; margin: 10px 0px 7px; font-size: 16px;">OUT OF POCKET COSTS</h3>
<p style="padding: 0;">Some airline programs assess substantial fuel surcharges, that combined with government mandated taxes and airport fees, can range from $700-$1200 per person. BUT, these programs often have far more available and convenient award routing options.</p>
<p style="padding: 0;">Domestic positioning flights to international gateway airports may not have any award space and require a separate purchased Economy airfare.</p>

<h3 style="color: rgb(41, 120, 185); border-top: 2px solid rgb(174, 174, 174); padding: 7px 0px 0px; margin: 10px 0px 7px; font-size: 16px;">FLIGHT DURATION</h3>
<p style="padding: 0;">Searching for the 'Saver' lowest level award often requires an extra stop and/or longer layover.</p>
<p style="padding: 0;">Flights to Africa typically require routing via Europe.</p>
<p style="padding: 0;">Flights to Australia/New Zealand typically require routing via Asia, with the possibility of an overnight (which can be extended to a multi-day stopover in a number of terrific Asian cities)</p>

<h3 style="color: rgb(41, 120, 185); border-top: 2px solid rgb(174, 174, 174); padding: 7px 0px 0px; margin: 10px 0px 7px; font-size: 16px;">ECONOMY FLIGHTS</h3>
<p style="padding: 0;">Some domestic and regional flights under 4 hrs may only have award space in Economy or no award space at all, requiring a separate purchased airfare in Economy</p>
<p style="padding: 8px 0;"><strong>Our process is straightforward:</strong></p>
<table style="margin-bottom: 5px;" cellpadding="0" cellspacing="0" border="0" width="100%">
    <col width="20px" valign="top" align="left">
    <col valign="top" align="left">
    <tr>
        <td>1.</td>
        <td>We will clearly advise you of any of the above trade-offs that could impact your award. If you agree to accept the proposed provisos as still meeting your award parameters, our service will commence.</td>
    </tr>
    <tr>
        <td>2.</td>
        <td>We will initiate your customized mileage award search.</td>
    </tr>
    <tr>
        <td>3.</td>
        <td>We will find and present your award itinerary.</td>
    </tr>
    <tr>
        <td>4.</td>
        <td>Upon your approval of the itinerary we will request your account information and provide transfer instructions where applicable, and expedite your booking and prepay any taxes.</td>
    </tr>
    <tr>
        <td>5.</td>
        <td>Tax reimbursement and booking service are payable by check. Credit card payments have a 2.9% service charge</td>
    </tr>
</table>
<p>Obviously, our goal is to be creative and resourceful on your behalf.  We will endeavor to avoid or mitigate as many trade-offs as possible. But over 85% of the 1.1 BILLION Miles we have booked include at least one of these trade-offs.   We find mutually acceptable awards for over 98% of our clients because we educate our clients about aligning their expectations - we aspire toward achieving your ideal, but need you to be prepared to accept the suitable.</p>
<p>We look forward to the challenge of earning your confidence and business.</p>
<p>Regards,<br>Gary and Steve</p>

<div style="border: 1px solid rgb(41, 120, 185); margin-top: 5px; padding: 5px;">
    <div style="background-color: rgb(153, 153, 153); padding: 3px 10px; color: white;">TO GET STARTED:</div>
    <div style="padding: 5px;">
        <h3 style="color: rgb(41, 120, 185); font-size: 15px; margin: 0px; padding: 5px 0px 0px;">PLEASE RESPOND AND POST IF ANY OF THE  TRADE-OFFS ARE NOT ACCEPTABLE:</h3>
        <ul style="padding: 0px; margin: 8px 0px;">
            <li><strong>Out Of Pocket Costs</strong></li>
            <li><strong>Flight Duration</strong></li>
            <li><strong>Economy Flights</strong></li>
        </ul>
        ...so that we can assess if we can realistically meet your parameters.
    </div>
</div>
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
