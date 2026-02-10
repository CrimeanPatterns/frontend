
CKEDITOR.addTemplates('default',
    {
        imagesPath: CKEDITOR.getUrl('/assets/awardwalletmain/js/booking/awardmagic/images/'),

        templates:[
            {
                title: 'Deposit Paid',
                image:  'pre-appt.png',
                description: '',
                html:
                    '<p>Hi [Name],</p>' +
                    '<p>Thanks so much for getting that $25 deposit in! </p>'+
                    '<p>We’re excited to start working on your flight request as your mileage ally and advocate!</p>'+
                    '<p><strong>We offer dedicated appointments by phone or online chat to go over your specific flight options</strong>, any applicable out-of-pocket expenses, and answer any questions you may have.</p>'+
                    '<p>Please choose a time from the calendar below (displayed in US Eastern time) and we will walk you through your options and the award booking process at that time.</p>'+
                    '<p>We strive to find you the best possible flights using your miles, but please be aware that almost 90% of awards include modest trade-offs like:</p>'+
                    '<ul>'+
                    '<li style="list-style-position: inside;">an extra connection or longer layover</li>'+
                    '<li style="list-style-position: inside;">domestic connections in economy</li>'+
                    '<li style="list-style-position: inside;">separately purchased tickets from your home airport to major airport hubs, often requiring claim/recheck of luggage</li>'+
                    '<li style="list-style-position: inside;">out-of-pocket expenses such as government tariffs and/or fuel surcharges</li>'+
                    '</ul>'+
                    '<p>Any applicable trade-offs will be shared during your appointment so you can make an informed decision.</p>'+
                    '<p>For all appointments, including phone appointments, we require you to be logged into this website (<a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/login">https://awardwallet.com/login</a>) at the time of your appointment. This allows us to display a written copy of your flight schedule and securely request your account information to reserve any and all flights you approve. If there are multiple travelers, please make sure everyone is available during the appointment time to review options and make a final decision on which flights to reserve.</p>'+
                    '<p>We look forward to saving you thousands of dollars in cash, hundreds of thousands of miles, and hours of hassle. </p>'+
                    '<p>The Award Magic Team <br />(Ari, Steve, Ceca, Becky, Ricardo and Andrew)</p>' +
                    '{{ awardmagic_schedule }}'
            },
            {
                title: 'PRE APPT',
                image:  'pre-appt.png',
                description: '',
                html:
                    '<p>At Award Magic, we don\'t like asterisks or fine print, but unfortunately airline and credit card programs are shameless in that regard. Please don\'t kill the messenger, but it\'s important you\'re aware of the following provisos that will impact your award (and over 90% of the awards that we present). We want you to make an informed decision about whether you would like to proceed to your appointment.</p>' +
                    '<p><strong>ROUTING/CONNECTIONS</strong></p>'+
                    '<p>Nonstop flights, peak dates, and other preferred routings typically sell out to paying customers, so airlines don\'t have much incentive to make those flights available as free awards. Our mission is to find you the <strong>next-best-possible</strong> flights that are still available at a low mileage price.</p>'+
                    '<p>Saving hundreds of thousands of miles and enjoying a leisurely shower, dining and relaxation in connecting airport lounges or even an occasional option for a day of sightseeing in an unexpected new city are worth forgoing the modest time savings for over 95% of our clients.</p>'+
                    '<p><strong>DOMESTIC FLIGHTS</strong></p>'+
                    '<p>U.S. flights frequently sell out to paying customers, so ironically award space is often tighter on domestic flights than international flights. More than 75% of awards we book have domestic segments in economy class, and often require a separately purchased airfare to the connecting hub for international flights if award space is not available.</p>'+
                    '<p>Because airlines have limited partners, your itinerary will require claiming your luggage from the domestic flight and re-checking at ticket counter of international flight and clearing security again. So, we will ensure that you have at least 2 hours connection time to allow for this scenario.</p>'+
                    '<p><strong>TAXES AND FEES</strong></p>'+
                    '<p>Airlines have been very timid in disclosing that awards have associated out-of-pocket costs like taxes and fuel surcharges. We preemptively try to mitigate such costs by searching for airlines and routings with the lowest costs possible, but if they aren’t available, we will disclose all costs in advance of booking.</p>'+
                    '<p><strong>AWARD BOOKING ON A ROLLING BASIS</strong></p>'+
                    '<p>Sometimes, only a one-way award is available. In these instances, we suggest booking the one-way award immediately and then we will continue to look for awards on the unbooked one-way (so you end up with a complete round-trip). Choosing to wait for a round-trip award leads to losing the suitable one-way in the interim over 80% of the time. Rest assured, we find award space for the unbooked half of the trip 99% of the time using this strategy.</p>'+
                    '<p><strong>TRANSFER LIABILITY</strong></p>'+
                    '<p>Mileage transfers from credit cards and hotel programs often take a few days to process, even if you get an immediate confirmation note. This delay may impact award availability in the interim, but happens less than 5% of the time. If that happens, we will search for alternate award options to use your newly transferred miles but our company has no liability for delayed transfers, loss of proposed award space, or for credit card policies that do not allow transfers to be rescinded.</p>'+
                    '<table cellspacing="0" cellpadding="0" style="margin: 7px 0; border-collapse:collapse; width:auto; border: 2px solid #4dbfa2;">'+
                    '<tr>'+
                    '<td style="color: #fff !important; background: #4dbfa2; padding: 2px 6px 2px 4px;"><strong style="color: #fff !important">SHARE FLIGHT DETAILS:</strong></td>'+
                    '<td style="padding: 2px 6px;"><strong>If you are generally satisfied with the above context</strong></td>'+
                    '</tr>'+
                    '</table>'+
                    '<p>Please respond that you understand the trade-offs associated with this award and would like to proceed with our services and continue with your scheduled appointment.</p>'+
                    '<div style="border-left: 6px solid #2978b9; padding-left: 4px; margin: 4px 0;">'+
                   '<strong>We will provide</strong> <br />'+
                   '<ul>'+
                   '<li style="list-style-position: inside;">specific airlines, routings, departure/arrival times</li>'+
                   '<li style="list-style-position: inside;">instructions as needed to register for airline programs and/or transfer credit card/hotel points</li>'+
                   '<li style="list-style-position: inside;">answer any questions</li>'+
                   '<li style="list-style-position: inside;">review timetable for award approval and bookings; our goal is to reserve by end of day</li>'+
                   '</ul>'+
                   '</div>'+
                   '<div style="border-left: 6px solid #4dbfa2; padding-left: 4px; margin: 4px 0;">'+
                   '<strong style="color: #00a67c">IMPORTANT</strong> <br />'+
                   '<p>We share your award details on our secure site to protect your account security and privacy. So, it is <strong>mandatory</strong> that you are logged online on our secure messaging system (<a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/login">https://awardwallet.com/login</a>) <strong>before</strong> your appointment. <br />Please have all other parties involved with the decision online and/or available by phone so you can make real-time decisions together.  Award space is volatile and at risk of disappearing.</p>'+
                   '</div>'+
                   '<table cellspacing="0" cellpadding="0" style="margin: 7px 0; border-collapse:collapse; width:auto; border: 2px solid #ca3f5f;">'+
                    '<tr>'+
                    '<td style="color: #fff !important; background: #ca3f5f; padding: 2px 6px 2px 4px;"><strong style="color: #fff !important">CANCEL:</strong></td>'+
                    '<td style="padding: 2px 6px;"><strong>if you are not satisfied with the award structure above</strong></td>'+
                    '</tr>'+
                    '</table>'+
                   '<p>In our 6 years of booking awards, we see certain trends emerge on the structure and pricing of award availability.  Based on your flight criteria and mileage resources, we estimate that waiting 1-2 weeks for different award options typically results in the following:</p>'+
                   '<ul>'+
                   '<li style="list-style-position: inside;">10% of the time we see better availability</li>'+
                   '<li style="list-style-position: inside;">25% of the time we see the same or similar availability</li>'+
                   '<li style="list-style-position: inside;">50% of the time we see worse availability</li>'+
                   '<li style="list-style-position: inside;">15% of the time we see NO availability</li>'+
                   '</ul>'+
                   '<p>However, if these realities are a deal-breaker for you, please cancel your request and feel free to re-open this or other travel requests in the future.</p>'+
                   '<p><strong>Please post below which of these options you choose:</strong><br /><strong style="color:#fff; background: #4dbfa2;display: inline-block;height: 20px;line-height: 20px;padding: 0 5px;">SHARE FLIGHT DETAILS</strong> or <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">CANCEL</strong></p>'+
                   '<p>We look forward to the challenge of earning your confidence and business. <br />The AM Crew (Ari, Steve, Ceca, Becky, Ricardo and Andrew)</p>'
            },
            {
                title: 'Initial Deposit Follow-Up ',
                image:  'pre-appt.png',
                description: '',
                html:
                    '<p>Hi [name],</p>' +
                    '<p>Thank you again for reaching out to Award Magic to fulfill your award-booking needs. While we are poised to begin working with you and for you in securing this mileage ticket, we’re unable to get started until the initial $25 deposit has been paid. You’ll notice an invoice was sent in this thread, and can easily be paid with the credit card of your choice.</p>'+
                    '<p>As soon as we receive that payment we’ll move you into our booking queue and will get the ball rolling on your request!</p>'+
                    '<p>Thank you again for choosing Award Magic and we look forward to working with you.</p>'+
                    '<p>Best, <br />The Award Magic Team <br />(Ari, Steve, Ceca, Becky, Ricardo, Andrew)</p>'
            },
            {
                title: 'Credit Card Transfer Instructions',
                image:  'proposal_prep.png',
                description: '',
                html:
                    '<p>Since most airlines don`t offer courtesy holds, it is important that you quickly execute the following transfer to ensure we can book what we have proposed.</p>'+
                    '<p>We value your mileage and credit card account security and privacy. So, we have instituted a three point security protocol to ensure that your accounts will remain protected while working with us.</p>'+
                    '<ol>'+
                    '<li style="list-style-position: inside;">Our company has proprietary software that allow you to share access to your account with us, without revealing your private login and password information.</li>'+
                    '<li style="list-style-position: inside;">You will be sent a yellow box with the requested programs to share and you simply click that box to activate the auto-sharing function.</li>'+
                    '<li style="list-style-position: inside;">If you choose to share account info on this thread, our site itself is password protected.</li>'+
                    '<li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
                    '</ol>'+
                    '<p><strong>AMEX</strong></p>'+
                    '<ol>'+
                    '<li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com</li>'+
                    '<li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points in real time: XXXK</li>'+
                    '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow" target="_blank">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li>'+
                    '</ol>'+
                    '<p><strong>CHASE</strong></p>'+
                    '<ol>'+
                    '<li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now</li>'+
                    '<li style="list-style-position: inside;">Login to your Ultimate Rewards account or contact Chase at 800-537-7783 to transfer the following number of Chase points in real time: XXXK</li>'+
                    '</ol>'+
                    '<p><strong>CITI</strong><br />Open a new mileage account at: http://www.xx.com now <br />Login to your Thank You points account or contact Citi at 877-288-2484 to transfer the following number of Citi points (usually instant, but sometimes takes 2-3 days): XXXK</p>'
            },
            {
                title: 'Recurring Search',
                image:  'proposal_prep.png',
                description: '',
                html:
                    '<p>Currently, no award space is available for your itinerary. We are maintaining an ongoing customized search on your behalf spanning over 60 routing options and 12 airline partners. We will contact you:</p>'+
                    '<div style="border-left: 6px solid #2978b9; padding-left: 4px; margin: 4px 0;">as soon as award space opens up</div>'+
                    '<table cellspacing="0" cellpadding="0" border="0" style="width: 42px; margin: 4px 0; border-collapse:collapse; ">'+
                    '<tr>'+
                    '<td style="background: #ccc; text-align: center; height: 26px;">or</td>'+
                    '</tr>'+
                    '</table>'+
                    '<div style="border-left: 6px solid #2978b9; padding-left: 4px; margin: 4px 0;">once two weeks elapses with no award space released, we will contact you to reassure you that we are indeed remaining vigilant on your behalf.</div>'+
                    '<p>Look forward, <br />(Staff Name)</p>'
            },
            {
                title: 'L1. No Appt. Scheduled / Cancel',
                image:  'proposal_prep.png',
                description: '',
                html:
                    '<p>Dear [Name],</p>'+
                    '<p>We have endeavored to communicate with you in a timely and clear manner. Haven\'t heard from you in a bit, so we welcome you to re-engage if/when you are so inclined.</p>'+
                    '<p>Cheers, <br />(Staff Name)</p>'
            },
            {
                title: 'Itinerary Objection',
                image:  'objections.png',
                description: '',
                html:
                    '<p>Airlines typically only release the mileage-efficient "Saver" awards on routings that sell out less frequently. Therefore, popular non-stops and other streamlined routings have minimal (if any) award space released. </p>'+
                    '<p>While your proposed award routing includes a modest amount of extra travel time, The overwhelming advantage is that such trade-offs are balanced off by our finding low level "Saver" award space that saves hundred of thousands of miles.</p>'+
                    '<p>Based on our 6 years of experience, over 14,000 client searches and 3 BILLION miles worth of award redemptions, we find that waiting a couple of weeks or even a few months rarely is a fruitful strategy to find improved award space:</p>'+
                    '<ul>'+
                    '<li style="list-style-position: inside;"><strong>5% better than proposed</strong></li>'+
                    '<li style="list-style-position: inside;"><strong>15% same as proposed</strong></li>'+
                    '<li style="list-style-position: inside;"><strong>70% worse than proposed</strong></li>'+
                    '<li style="list-style-position: inside;"><strong>10% no award space</strong></li>'+
                    '</ul>'+
                    '<table cellspacing="0" cellpadding="0" style="margin: 7px 0; border-collapse:collapse; width:auto; border: 2px solid #4dbfa2;">'+
                    '<tr>'+
                    '<td style="color: #fff !important; background: #4dbfa2; padding: 2px 6px 2px 4px;"><strong style="color: #fff !important">OPTION1</strong></td>'+
                    '<td style="padding: 2px 6px;"><strong>DON\'T WAIT</strong></td>'+
                    '</tr>'+
                    '</table>'+
                    '<p>So, our suggestion is to book what we we`ve proposed now. This will provide you the certainty and peace of mind of having a confirmed award locked in. Then, we welcome you to circle back one week prior to your departure date for one complimentary award search, when last minute award space might be released to improve your itinerary.</p>'+
                    '<table cellspacing="0" cellpadding="0" style="margin: 7px 0; border-collapse:collapse; width:auto; border: 2px solid #cc3d5e;">'+
                    '<tr>'+
                    '<td style="color: #fff !important; background: #cc3d5e; padding: 2px 6px 2px 4px;"><strong style="color: #fff !important">OPTION2</strong></td>'+
                    '<td style="padding: 2px 6px;"><strong>WAIT</strong></td>'+
                    '</tr>'+
                    '</table>'+
                    '<p>Currently, no award space is available for your itinerary. We will maintain an ongoing customized search on your behalf and will contact you:</p>'+
                    '<div style="border-left: 6px solid #2978b9; padding-left: 4px; margin: 4px 0;">as soon as award space opens up</div>'+
                    '<table cellspacing="0" cellpadding="0" border="0" style="width: 42px; margin: 4px 0; border-collapse:collapse; ">'+
                    '<tr>'+
                    '<td style="background: #ccc; text-align: center; height: 26px;">or</td>'+
                    '</tr>'+
                    '</table>'+
                    '<div style="border-left: 6px solid #2978b9; padding-left: 4px; margin: 4px 0;">once two weeks elapses with no award space released, we will contact you to reassure you that we are indeed remaining vigilant on your behalf.</div>'+
                    '<p>We await your guidance how best to proceed on your behalf.</p>'+
                    '<p>Sincerely, <br />Award Magic Team</p>'
            },
            {
                title: 'Airline Objection',
                image:  'objections.png',
                description: '',
                html:
                    '<p>In the past several years, the airline industry has enjoyed an unprecedented influx of new aircraft like the A380, A350 and Boeing 787, as well as renovating premium-class cabins with lie-flat seats. As a result, many of the negative preconceived notions about a given airline (and their reputation) have likely changed quite a bit.</p>'+
                    '<p>_ _ _ _ is a great example of this. Our company principals have personally flown and vetted this airline they both agree that _ _ _ _ meets all criteria for us to recommend to our clients. We have booked awards on this airline for hundreds of other clients and we are pleased to report overwhelmingly positive feedback. This airline has clearly stepped up its game and we hope that we’ve provided you the tangible reassurance to merit moving forward with your booking.</p>'
            },
            {
                title: 'Economy Flight Objection',
                image:  'objections.png',
                description: '',
                html:
                    '<p>In order to find award space at the low cost ‘Saver’ level, over 90% of our bookings are subject to modest “workarounds.” In your particular case, this includes flying a flight of modest duration in economy.</p>'+
                    '<p>Many short flights do not offer award seats in business class because:</p>'+
                    '<ul>'+
                    '<li style="list-style-position: inside;">Most short-haul aircraft are regional jets with no business class seats on board.</li>'+
                    '<li style="list-style-position: inside;">For those domestic flights that do offer a business class cabin, there are often only 8 business class seats. They are often allocated exclusively for top-tier elite members or for last-minute upgrades to full-fare passengers.</li>'+
                    '</ul>'+
                    '<p>We hope you can agree that this modest workaround will be well worth the benefit of saving hundreds of thousands of miles.</p>'+
                    '<p>Thank you, <br />Award Magic Team</p>'
            },
            {
                title: 'TK/IST Objection',
                image:  'objections.png',
                description: '',
                html:
                    '<p>The Istanbul airport is currently one of the safest in all of Europe due to the security measures they`ve implemented (in addition to being across from an air force base). Another advantage to transiting IST is that it almost always a premium experience: great in-flight product with convenient connections, a top-notch airport lounge, no fuel surcharges, and a competitive mileage price compared to other programs.</p>'+
                    '<p>Ari, the owner, recently flew Turkish with his wife and son, and they all had terrific experiences. As well, none of our clients that have flown Turkish in the last few months have expressed any negative feedback about either the IST airport or in flight experience. We trust that provides some tangible and first hand reassurance. Please let us know how you want to proceed.</p>'+
                    '<p>Thank you, <br />(Staff Name)</p>'
            },
            {
                title: 'Taxes/Fuel Surcharges Objection',
                image:  'objections.png',
                description: '',
                html:
                    '<p>The vast majority of European airlines and Emirates levy \'hefty fuel surcharges\' on top of already high airport taxes and security fees. If there were other award alternatives to mitigate such out of pocket costs, please rest assured that we would have preemptively offered them. Despite these costs, redeeming the low level \'Saver\' award we found you, i still a compelling value proposition- saving you hundreds of thousands of miles.</p>'
            },
            {
                title: 'Amex to Asia Miles',
                image:  'misc.png',
                description: '',
                html:
                    '<p>In order to book your award using Asia Miles, please follow the steps below exactly. It is important to begin this process immediately since the Amex transfer typically takes 3 business days</p>'+
                    '<p><b>Step 1: Provide Our Team with VALID Passport Information</b></p>'+
                    '<p>Valid Passport information is required for ticketing, we will need the following information for each traveler:</p>'+
                    '<ol>'+
                    '<li style="list-style-position: inside;">Passport Numbers</li>'+
                    '<li style="list-style-position: inside;">Issuing Country</li>'+
                    '<li style="list-style-position: inside;">Expiration Date</li>'+
                    '</ol>'+
                    '<p>If your passport has expired you will need to have it reissued, once complete you can re-engage with our team and we will check for updated award options.</p>'+
                    '<p><b>Step 2: Transfer Amex to Asia Miles</b></p>'+
                    '<ol>'+
                    '<li style="list-style-position: inside;">Open a new Asia Miles account at and make sure the name matches your passport EXACTLY: <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.asiamiles.com" target="blank">http://www.asiamiles.com</a></li>'+
                    '<li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points: <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">XXXX miles</strong></li>'+
                    '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow" target="blank">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li>'+
                    '</ol>'+
                    '<p><b>Step 3: Add All Passengers to your Asia Miles Redemption Group</b></p>'+
                    '<p>Asia Miles requires you to add all travelers to your redemption group before we can book their tickets. <br />Sign in to Asia Miles, under the profile section you can expand on “Your Redemption Group” .</p>'+
                    '<ul>'+
                    '<li style="list-style-position: inside;">Select - “Edit Redemption Group”</li>'+
                    '<li style="list-style-position: inside;">For each additional traveler, add their name <strong>EXACTLY</strong> as it is on their passport</li>'+
                    '<li style="list-style-position: inside;">Enter <b><i>your</i></b> birthdate and agree to the terms in order to submit</li>'+
                    '</ul>'+
                    '<p>After you’ve added each passenger, Asia Miles will email you a one-time code that you need to enter in the profile to complete the process.</p>'+
                    '<p><b>Step 4: Provide Electronic Authorization of Your Account to Our Team</b></p>'+
                    '<p>We will need access and authorization on your Asia Miles account in order to call them and book your tickets. <br />For your privacy and security, we have proprietary software that allows you to provide authorization without revealing your password.</p>'+
                    '<ol>'+
                    '<li style="list-style-position: inside;">You will see a yellow box below requesting your account access. Please click through that box to enter your account number and password + grant access/permission to our team.</li>'+
                    '<li style="list-style-position: inside;">If you have any technical difficulties, you can type the account number/password as a text message and we’ll set it up for you.</li>'+
                    '<li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
                    '</ol>'+
                    '<p>Then, we will contact Asia Miles to redeem your award and prepay all applicable taxes. <br />We trust this process is clear and straightforward and look forward to finalizing your booking.</p>'+
                    '<p>Much obliged, <br />Award Magic</p>'
            },
            {
                title: 'Korean Air Booking Process',
                image:  'misc.png',
                description: '',
                html:
                '<p>Hi :,</p>'+
                '<p>The following flights are on hold with Korean Airways for your itinerary and can be referenced under confirmation <b>XXXX</b>.</p>'+
                '<p>Korean Air requires all awards to be redeemed directly by the account holder so to proceed you will need to</p>' +
                '<ul>' +
                '<li style="list-style-position: inside;">Transfer xxxx points to each account and complete the Skypass Award application.</li>' +
                '<li style="list-style-position: inside;">Download and Complete Skypass Award Application at <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.koreanair.com">www.koreanair.com</a></li>' +
                '<li style="list-style-position: inside;">Email completed form along with copies of passports to <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="mailto:enskypass@koreanair.com">enskypass@koreanair.com</a></li>' +
                '<li style="list-style-position: inside;">Once received you can contact Korean Airways at 800.438.5000 to finalize the award and pay taxes</li>' +
                '</ul>' +
                '<p>Our service typically offers a far more seamless service, but the restrictions imposed by Korean Air require more customer involvement.</p>' +
                '<p>Please keep us posted on your progress and advise if you have any questions.</p>' +
                '<p>Thank you,<br>(Staff Name)</p>'
            },
            {
                title: 'BA Companion Cert',
                image:  'misc.png',
                description: '',
                html:
                '<p>Please be well aware of the important provisos of BA Companion cert:</p>' +
                '<ol>' +
                '<li style="list-style-position: inside;"><b>Both</b> passengers are responsible for taxes/fuel surcharges of approx. $1300-$1600 per person</li>'+
                '<li style="list-style-position: inside;">Only BA aircraft is eligible, no partners, so if no award space out of origin airport or into destination airport, you will be responsible for purchasing separate airfares from whatever alternate airports we find.</li>'+
                '<li style="list-style-position: inside;">Not bookable online nor bookable by any third party with BA call center, so we will provide you the exact flight info, and you will contact BA direct to redeem award and prepay taxes.</li>'+
                '</ol>' +
                '<p>Our service typically offers a far more seamless service, but the restrictions imposed by the companion cert require more customer involvement.</p>' +
                '<p>If you are amenable to the above, we will be poised to try and assist.</p>' +
                '<p>Look forward<br>(Staff name)</p>'
            },
            {
                title: 'Account Access Error',
                image:  'misc.png',
                description: '',
                html:
                '<p>Our system is returning an error with the XXX information you provided. This typically just means there was a typo in either the XXX account number and/or password. Please double-check that both were entered correctly and update as necessary. After you`ve double-checked, you can confirm it`s correct by clicking the "Auto-login" link below."</p>' +
                '<p>Alternatively you can type the account number and password in this message thread as text and we`ll set it up manually.</p>' +
                '<p>Thanks, <br />The Award Magic Team</p>'
            },
            {
                title: 'United Security Questions ',
                image:  'misc.png',
                description: '',
                html:
                '<p>United has recently updated their Mileage Plus log in and now require the following security questions to be answered when logging in from another computer, please provide our team with this information and we will finalize your award and prepay taxes. We only need the answers to the questions you answered (typically 5).</p>' +
                '<p>You can change the answers to these questions once the award is confirmed. Please post your United account number and password.</p>' +
                '<p>Thanks, <br />The Award Magic Team</p>' +
                '<ul>'+
                '<li style="list-style-position: inside;">What is your favorite type of vacation?</li>'+
                '<li style="list-style-position: inside;">In what month is your best friend’s birthday?</li>'+
                '<li style="list-style-position: inside;">What is your favorite sport?</li>'+
                '<li style="list-style-position: inside;">What is your favorite flavor of ice cream?</li>'+
                '<li style="list-style-position: inside;">During what month did you first meet your spouse/significant other?</li>'+
                '<li style="list-style-position: inside;">When you were young, what did you want to be when you grew up?</li>'+
                '<li style="list-style-position: inside;">What was the make of your first car?</li>'+
                '<li style="list-style-position: inside;">What is your favorite sea animal?</li>'+
                '<li style="list-style-position: inside;">What is your favorite cold-weather activity?</li>'+
                '<li style="list-style-position: inside;">What is your favorite breed of dog?</li>'+
                '<li style="list-style-position: inside;">What was the first major city that you visited?</li>'+
                '<li style="list-style-position: inside;">What was your least favorite fruit or vegetable as a child?</li>'+
                '<li style="list-style-position: inside;">Who is your favorite artist?</li>'+
                '<li style="list-style-position: inside;">What is your favorite type of music?</li>'+
                '<li style="list-style-position: inside;">What is your favorite type of reading?</li>'+
                '<li style="list-style-position: inside;">What is your favorite pizza topping?</li>'+
                '</ul>'
            },
            {
                title: 'Response to Bad 3d party Reviews',
                image:  'misc.png',
                description: '',
                html:
                '<p>Between Ari, Steve and the rest of the Award Magic team, they have personally flown and vetted every airline that we recommend to folks like you. Beyond that, we have booked hundreds of awards with this airline over the last three years with nary a negative feedback.</p>' +
                '<p>Third party "review" sites are not even remotely statistically significant. Plus, many reviewers make unrealistic comparisons between airlines.</p>' +
                '<p>These sites INHERENTLY skew towards those more apt to complain who are very motivated vs. those who have a satisfying experience and are typically not motivated to "vent" for a positive experience to share on a third party site.</p>' +
                '<p>Your call about assessing our first hand analysis versus the anonymity of a third party site. <br /> <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://boardingarea.com/viewfromthewing/2014/05/22/forget-first-class-business-class-want-even-better-think/" target="blank">http://boardingarea.com/viewfromthewing/2014/05/22/forget-first-class-business-class-want-even-better-think/</a></p>'
            },
            {
                title: 'Client Approval - Booking Queue',
                image:  'misc.png',
                description: '',
                html:
                '<p>Thanks for your approval and sharing the requested account information with our secure site; <br />We will expedite your award to the booking queue to redeem award and prepay taxes and keep you posted of our progress.</p>'
            },
            {
                title: 'Award Space Changed after Phone Session',
                image:  'after_appt.png',
                description: '',
                html:
                '<p>Thanks for your timely approval of the award proposed during our phone session.  Despite our equal timeliness in contacting the airline to book your award, inventory changed in the brief interval between our session and contacting the airline.  We are dependent on reaching most airlines by phone and wait times are unpredictable from circumstances like severe weather, air traffic control, or mechanical issues impacting flying passengers.   As well, most airlines don’t allow courtesy holds to protect the award space that we find.</p>' +
                '<p>So, award space will hopefully be refreshed to our advantage in the next 3-4 days, so please schedule a followup phone session below in that time window and we hope to have good news by that time.  We appreciate your patience and understanding about this issue that unfortunately is beyond our control.</p>' +
                '<p>More soon.</p>'+
                '<p>Cheers, <br />Award Magic Team</p>'
            },
            {
                title: 'Follow up as No Client Response',
                image:  'after_appt.png',
                description: '',
                html:
                '<p>Dear :</p>' +
                '<p>Our advice and counsel is straightforward regarding continuing to wait for the possibility of a more streamlined award space to be released - Don\'t wait!  You run the dual risk of not finding anything better and very likely losing the proposed award routing in the interim... leaving you with inferior and possibly even no options. Instead, book now and enjoy the <b>certainty</b> of a confirmed in advance premium class award.</p>' +
                '<p>Look forward,<br>(Staff Name)</p>'
            },
            {
                title: 'No Upgrades',
                image:  'admin.png',
                description: '',
                html:
                '<p>Our company only handles outright mileage awards, you will need to contact airline directly if you wish to upgrade from an Economy fare. We believe you will be far better served with an outright award vs an upgrade as noted below. <br /><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/blog/upgrade-airline-tickets-miles/" target="_blank">https://awardwallet.com/blog/upgrade-airline-tickets-miles/</a></p>'
            },
            {
                title: 'Post Payment Thank You',
                image:  'admin.png',
                description: '',
                html:
                '<p>Dear [Name],</p>'+
                '<p>While Award Magic is known for our expertise in redeeming mileage awards (we recently crested over 2.5 BILLION miles), we also figured you might appreciate being privy to some of our mileage earning strategies.</p>'+
                '<p>There are some terrific credit card promotions that offer tens of thousands of bonus miles for a modest amount of qualified spending. To maximize your mileage earning potential, consider signing up for both a personal and business card for each member of your household. We have summarized the best offers below to replenish your mileage balances and urge you to sign up soon as most of these offers are time-sensitive.</p>'+
                '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/blog/link/ccbya" target="blank">https://awardwallet.com/blog/link/ccbya</a></p>'+
                '<p>We look forward to the challenge of earning your future confidence and business..</p>'+
                '<p>Cheers <br />Team Award Magic</p>'+
                '<p style="border-top: 1px solid #b3b3b3; padding-top: 5px"><strong>P.S.</strong> If an 11day team travel competition featuring a mystery itinerary of Europe accomplishing surprise challenges (creativity and resourcefulness, not speed or fitness) in pursuit of up to $6600 cash prize sounds exciting, check out our other company <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.competitours.com" target="_blank">www.competitours.com</a>. Enjoy a trip packed with rivalry by day and revelry by night!</p>'
            },
            {
                title: 'Office Closed',
                image:  'admin.png',
                description: '',
                html:
                '<p>We have endeavored to stay in good touch with you during business hours. We look forward to jumpstarting our efforts on your behalf tomorrow (or if its the weekend, on Monday!) We appreciate your patience and look forward to earning your confidence and business.</p>'
            },
            {
                title: 'Monitoring Service',
                image:  'admin.png',
                description: '',
                html:
                '<p>Dear [Name],</p>'+
                '<p>We’re happy to get back to work for you, and hopefully find an even more perfect itinerary than what was already booked. To do this however, because we use a proprietary software that allows us to search across dozens of airlines and routings on an ongoing basis, there is a fee associated.</p>'+
                '<p><strong>If you are interested in setting up monitoring with us, the fee is $85 per person.</strong> Keep in mind, the money is paid up front, and the service fee is not dependent on whether or not the availability ever opens up.</p>'+
                '<p>Alternatively you may simply respond to this thread within one week of departure and we will do a single, complimentary search of your ideal award itinerary.</p>'+
                '<p>Please let us know how/if you’d like to proceed with this monitoring service and we can then move forward accordingly. </p>'+
                '<p>Thank you, <br />The Award Magic <Teambr></Teambr>(Ari, Steve, Ceca, Becky, Ricardo and Andrew)</p>'
            },
            {
                title: 'Cancellation',
                image: 'admin.png',
                description: '',
                html: '<p>Dear [Name],'+
                      '<p>We charge a cancellation fee of $85 per person for cancellations, as most airlines require re-engagement via phone to reinstate miles and many times it takes hours to complete. If you agree, then we\'re happy to move forward with your cancellation and confirm it within 2-5 business days. Otherwise, you may contact the airline(s) yourself to process the cancellation and let us know once completed. Whether we process the cancellation on your behalf or you do it yourself, we\'ll cut you a check for the airline taxes refund amount, provided the refund is debited back to our corporate account. So, please provide us with a mailing address to send it to.</p>'+
                      '<p>Additionally, please be aware that airlines charge a miles redeposit/cancellation fee that varies from program to program. You may refer to this article for more information: <br /><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://upgradedpoints.com/airline-award-cancellation-change-fees/" target="_blank">https://upgradedpoints.com/airline-award-cancellation-change-fees/</a></p>'+
                      '<p>Regards, <br />Award Magic Team</p>'
            },
            {
                title: 'Booking Instructions: ANA',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>ANA has a two part registration process that you will follow below. Once you have completed both parts, please forward us your new ANA Mileage Club account number and password. Amex transfers to ANA can take up to 48-72 hrs so please check your account daily and alert us when the transfer has actually posted into the ANA account so that we can expedite to booking queue.</p>'+
                '<p><strong>REGISTRATION</strong></p>'+
                '<ol>'+
                '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://cam.ana.co.jp/amcmember/amcentry/AMCEntryFacadeEn" target="_blank">https://cam.ana.co.jp/amcmember/amcentry/AMCEntryFacadeEn</a></li>'+
                '<li style="list-style-position: inside;">Mail/Residence Entry, click</li>'+
                '<li style="list-style-position: inside;">Customer Information, Address, Email, Password Information, click (no fill in cell phone, triggers an error)</li>'+
                '<li style="list-style-position: inside;">Confirming Your Information</li>'+
                '</ol>'+
                '<p>After submitting and confirming able info, then.........</p>'+
                '<p><strong>AWARD REDEMPTION GROUP</strong></p>'+
                '<p>For each additional person (who must be related to you somehow!!), there is this separate section that must be completed to authorize booking for them.</p>'+
                '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://rps.ana.co.jp/awe_a/afa/registration/us/form_e.html" target="_blank">https://rps.ana.co.jp/awe_a/afa/registration/us/form_e.html</a></p>'+
                '<ol>'+
                '<li style="list-style-position: inside;">fill out your account info handsome account number for each registered name/relationship/birthdate of each traveler</li>'+
                '</ol>'+
                '<p>Submit and confirm.</p>'+
                '<p>Because award space is quite volatile, we count on your cooperation to execute this transfer quickly to ensure we can secure the award we have found on your behalf. As we discussed, we’re confirming your agreement to execute these credit card transfers by TIME AND DAY</p>'+
                '<p>Much obliged,<br />Award Magic Team</p>'
            },
            {
                title: 'Hawaii',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>Award space to Hawaii in the "Saver" low level amount is very rarely released, as the Business Class cabins are small and leisure travelers often purchase high Economy airfares to then use mileage to upgrade, so precious little if any for outright mileage awards. We are happy to do an ongoing boi weekly search but we owe you the candor of knowing that we find suitable Hawaii awards less than 10% of the time and suggest you hang on to your mileage resources for a future trip where the odds are more in your favor. Please let us know if you concur.</p>'
            },
            {
                title: 'Air France Client Instructions',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>Air France has updated the booking policy and the following steps are required to process the reservation:</p>'+
                '<ol>'+
                '<li style="list-style-position: inside;">Create a new account at www.flyingblue.com or use an existing account.</li>'+
                '<li style="list-style-position: inside;">Provide our team with the account number and password, we will send you a request to share this information or you can post this information in your reply and we will manually access the account.</li>'+
                '<li style="list-style-position: inside;">Award Magic will secure a 24 hour courtesy hold for the <strong>XXX-XXX</strong> award and provide you with final transfer instructions.</li>'+
                '<li style="list-style-position: inside;">Once the courtesy hold is in place you will need to contact Air France via phone to pay final taxes and have the award processed.</li>'+
                '</ol>'
            },
            {
                title: 'Likelihood OneWorld Europe',
                image:  'likelihood.png',
                description: '',
                html:
                '<p>We are excited to get your award request from <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">DEP</strong> to <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">ARR</strong> underway.  Based on similar requests we’ve booked over the past 10 years, we expect the MOST LIKELY award flights to be as follows:</p>'+
                '<p><strong>Number of Connections</strong> <br />Popular routings sell out to customers paying full-fare, so adding a connection allows us to confirm business class award flights.</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">75% chance you’ll make 1 connection</li>'+
                '<li style="list-style-position: inside;">15% chance you’ll get a nonstop flight</li>'+
                '<li style="list-style-position: inside;">10% chance you’ll make 2 connections</li>'+
                '</ul>'+
                '<p><strong>Airlines Flown</strong> <br />All airlines we book and recommend are high quality, reputable airlines that our company members have personally flown and vetted, as well as receiving positive feedback from other clients like yourself.</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">75% chance you’ll fly on British Airways</li>'+
                '<li style="list-style-position: inside;">15% chance you’ll fly on Iberia Airlines</li>'+
                '<li style="list-style-position: inside;">10% chance you’ll fly on American Airlines, Jet Airways, or Finnair</li>'+
                '</ul>'+
                '<p><strong>Taxes and fees</strong> <br />Award tickets include government-mandated taxes and security fees, and some airlines also collect fuel surcharges.  Your expected costs:</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">75% $900-1200 per person</li>'+
                '<li style="list-style-position: inside;">15% $200-400 per person</li>'+
                '<li style="list-style-position: inside;">10% $100-200 per person</li>'+
                '</ul>'+
                '<p><strong>Separately purchased airfare</strong> <br />More often than not, there is no award space from your home airport to an international gateway hub.  We expect you’ll need to buy flights within the U.S. separately, at an estimated cost of <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">$200</strong>.</p>'+
                '<p><strong>Your personalized appointment</strong> <br />We don’t want you to be surprised by this context or the realities of award travel.<br /><strong>If these scenarios do NOT meet your threshold of acceptability, please cancel your upcoming appointment and use this message board to communicate any concerns or questions you may have.</strong></p>'+
                '<p>Otherwise, we look forward to your phone appointment in order to:</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">Provide you with specific flight details, including routings, airlines, and costs</li>'+
                '<li style="list-style-position: inside;">Collect account information in order to book the award with your approval</li>'+
                '<li style="list-style-position: inside;">Prepay any applicable award taxes/fees during booking and include them on your final invoice with our service fee of $185 per passenger</li>'+
                '</ul>'+
                '<p>Since award inventory changes quickly and most airlines do not allow courtesy holds, please be prepared to assess flight options and provide approval within 6 hours of your appointment.</p>'+
                '<p>Thanks,<br />Award Magic Team</p>'
            },
            {
                title: 'Likelihood Aust/NZ',
                image:  'likelihood.png',
                description: '',
                html:
                '<p>We are excited to get your award request from <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">NYC</strong> to <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">Auckland</strong> underway.</p>'+
                '<p>Based on other requests we\'ve booked to Australia/New Zealand over the past 10 years, we want to share with you the MOST LIKELY parameters of your award flights in advance of your appointment:</p>'+
                '<p><strong>Routing</strong> <br />Popular nonstops from the USA to Australia/New Zealand almost always sell out to paying customers. <br />Because of this, your routing will most likely be</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">85% chance of routing through Asia</li>'+
                '<li style="list-style-position: inside;">10% chance of routing through the Middle East (Dubai, etc.), South America, or Europe</li>'+
                '<li style="list-style-position: inside;">4% chance of routing through Hawaii, Tahiti, Fiji</li>'+
                '<li style="list-style-position: inside;">1% chance of a nonstop from the west coast to Australia/NZ</li>'+
                '</ul>'+
                '<p>Some airlines allow stopovers at major Asian airports (e.g. Hong Kong).  If this is of interest to you, please let us know.</p>'+
                '<p><strong>Airlines Flown</strong> <br />All airlines we book and recommend are high quality, reputable airlines that our company members have personally flown and vetted, as well as receiving positive feedback from other clients like yourself.</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">75% chance you’ll fly on China Eastern, China Southern, Air China, China Airlines, Asiana Airlines, Cathay Pacific</li>'+
                '<li style="list-style-position: inside;">15% chance you’ll fly on Eva Airways, Thai Airways, Japan Airlines, Korean Airlines, All Nippon Airways, Singapore Airlines, Qatar Airways, Emirates, Etihad</li>'+
                '<li style="list-style-position: inside;">5% chance you’ll fly on LATAM Airlines or any European-based airline</li>'+
                '<li style="list-style-position: inside;">4% chance you’ll fly on Fiji Airways, Air Tahiti Nui, or Hawaiian Airlines</li>'+
                '<li style="list-style-position: inside;">1% chance you’ll fly on American Airlines, Delta Airlines, United Airlines, Qantas, Virgin Australia, or Air New Zealand</li>'+
                '</ul>'+
                '<p><strong>Taxes and fees</strong> <br />Award tickets include government-mandated taxes and security fees, and some airlines also collect fuel surcharges.  Your expected costs:</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">25% less than $200 per person</li>'+
                '<li style="list-style-position: inside;">50% $200-500 per person</li>'+
                '<li style="list-style-position: inside;">25% more than $500 per person</li>'+
                '</ul>'+
                '<p><strong>Separately purchased airfare</strong> <br />More often than not, there is no award space from your home airport to an international gateway hub.  We expect you’ll need to buy flights within the U.S. separately, at an estimated cost of <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">$200</strong>.</p>'+
                '<p><strong>Your personalized appointment</strong> <br />We don’t want you to be surprised by this context or the realities of award travel.  If these scenarios do NOT meet your threshold of acceptability, please cancel your upcoming appointment and use this message board to communicate any concerns or questions you may have. </p>'+
                '<p>Otherwise, we look forward to your phone appointment in order to:</p>'+
                '<ul>'+
                '<li style="list-style-position: inside;">Provide you with specific flight details, including routings, airlines, and costs</li>'+
                '<li style="list-style-position: inside;">Collect account information in order to book the award with your approval</li>'+
                '<li style="list-style-position: inside;">Prepay any applicable award taxes/fees during booking and include them on your final invoice with our service fee of $185 per passenger</li>'+
                '</ul>'+
                '<p>Since award inventory changes quickly and most airlines do not allow courtesy holds, please be prepared to assess flight options and provide approval within 6 hours of your appointment.</p>'+
                '<p>Thanks, <br />Award Magic Team</p>'
            },
        ]
    }
);
