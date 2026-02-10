CKEDITOR.addTemplates('default',
    {
        imagesPath: CKEDITOR.getUrl('/assets/awardwalletmain/js/booking/bya/images/'),

        templates:[
            //{
            //    title: 'L1. 1st Post Received (New Client)',
            //    image:  'workflow1.png',
            //    description: 'We will commence award search and report our progress within 1-2 business days.',
            //    html:
            //        '<p>Dear :</p>' +
            //            '<p>Thanks for helping us ensure that we can meet the award parameters that will merit your approval and will allow us to earn our keep with you. We will commence your customized award search and report our progress within 1-2 business days and as such, appreciate your patience.</p>' +
            //            '<p>Look forward,<br>(Staff Name)</p>'
            //},
            //{
            //    title: 'L1. 1st Post Received (Repeat Client)',
            //    image:  'workflow1.png',
            //    description: 'We will commence award search and report our progress within 1-2 business days.',
            //    html:
            //        '<p>Dear :</p>' +
            //            '<p>Thank you for taking the initiative to allow us the opportunity to re-earn your continued confidence and business. There is no better validation of our efforts on your behalf than receiving repeat business, so we appreciate your re-engaging with us.</p>' +
            //            '<p>We will commence your customized award search and report our progress within 1-2 business days and as such, appreciate your patience.</p>' +
            //            '<p>Look forward,<br>(Staff Name)</p>'
            //},
            //{
            //    title: 'L1. Wait For 1st post',
            //    image:  'workflow1.png',
            //    description: 'Please respond and post if any of the trade-offs are not acceptable.',
            //    html:
            //        '<p>Dear :</p>' +
            //            '<p>We are poised to commence your customized award search, but we need you to read our introductory post to ensure you are well acquainted with our service.</p>' +
            //            '<br>' +
            //            '<p><strong>To get started, please respond and post if any of the trade-offs (described in intro post) are not acceptable:</strong></p>' +
            //            '<ul style="padding-left:0">' +
            //            '<li style="list-style-position: inside;">Out Of Pocket Costs</li>' +
            //            '<li style="list-style-position: inside;">Flight Duration</li>' +
            //            '<li style="list-style-position: inside;"><strong>Short Haul</strong> Economy Flights</li>' +
            //            '</ul>' +
            //            '<br>' +
            //            '<p>...so that we can assess if we can realistically meet your parameters.</p>' +
            //            '<br>' +
            //            '<p>Much obliged,<br>(Staff Name)</p>'
            //},
            // {
            //    title: 'Pre Appt Reminders',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>To ensure that we can be mutually productive in expediting booking your award:</p>'+
            //        '<ul style="padding-left:0">'+
            //        '<li style="list-style-position: inside;">review the award overview chart above to make sure that all your submission info is accurate, otherwise if we present an award that meets criteria that you inaccurately submitted, there will be a $50 revised award search fee.</li>'+
            //        '<li style="list-style-position: inside;">it is critical that you have any third party/other passengers decision makers accessible to you or you have final authority to approve third parties\' awards.</li>'+
            //        '<li style="list-style-position: inside;">you need to be online on this site on time</li>'+
            //        '</ul>'+
            //        '<p>Your cooperation will ensure that we can actually secure what we found for you.</p>'+
            //        '<p>Thanks <br />Team BYA <br />Steve, Ceca, Ricardo, Andrew</p>'
            // },
            {
               title: 'Chart',
               image:  'pre-appt.png',
               description: '',
               html:
                  '<p>Dear :</p>'+
                  '<p>As your personal \'mileage bloodhound\', our hunt has been creative and thorough on your behalf.  And the projected award search results are the best out there at the low cost \'Saver\' level and should merit your approval. </p>'+
                  '<div style="min-width: 600px; overflow-y: hidden; overflow-x: auto"><table style="min-width: 600px; margin: 10px 0; border: 1px solid #b3b3b3; border-collapse: collapse; width: 100%; border-spacing: 0; background: #fff; ">'+
                        '<thead>'+
                              '<tr>'+
                                    '<th style="width: 140px; background: #dedede; border: 1px solid #b3b3b3; padding: 15px 10px; color: #000; font-weight: normal; text-align: left; font-size: 12px;"></th>'+
                                    '<th style="background: #dedede; border: 1px solid #b3b3b3; padding: 15px 10px; color: #000; font-weight: normal; text-align: left"></th>'+
                                    '<th style="background: #dedede; border: 1px solid #b3b3b3; padding: 15px 10px; color: #000; font-weight: normal; text-align: left"></th>'+
                                    '<th style="background: #dedede; border: 1px solid #b3b3b3; padding: 15px 10px; color: #000; font-weight: normal; text-align: left"></th>'+
                                    '<th style="background: #dedede; border: 1px solid #b3b3b3; padding: 15px 10px; color: #000; font-weight: normal; text-align: left"></th>'+
                                    '<th style="background: #dedede; border: 1px solid #b3b3b3; padding: 15px 10px; color: #000; font-weight: normal; text-align: left"></th>'+
                              '</tr>'+
                        '</thead>'+
                        '<tbody>'+
                              '<tr>'+
                                    '<td style="width: 140px; border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000">Travel Date</td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000; text-align: center">OUTBOUND</td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000; text-align: center">OUTBOUND2</td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000; text-align: center">INBOUND</td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000; text-align: center">INBOUND2</td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                              '</tr>'+
                              '<tr>'+
                                    '<td colspan="5" style="height: 30px;"></td>'+
                              '</tr>'+
                              '<tr>'+
                                    '<td style="width: 140px; background: #808080; border: 1px solid #b3b3b3; padding: 10px; font-size: 13px; color: #fff !important"><strong style="color: #fff !important">Saver\’ Redemption Amount for BUSINESS Class award per person</strong></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: rgb(88,56,153)">We only work with airlines that have been personally flown and vetted by our company principals to ensure quality in flight and on the ground experience.</td>'+
                              '</tr>'+
                              '<tr>'+
                                    '<td style="width: 140px; background: #808080; border: 1px solid #b3b3b3; padding: 10px; font-size: 13px; color: #fff !important"><strong style="color: #fff !important"># Stops/ <br />Connection Time</strong></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: rgb(88,56,153)">Your routing might have an extra stop or longer layover than you (and us too!) prefer. That’s the ’workaround required offer a low cost ‘Saver’ award which avoids your spending an extra 250-400% more for your award.</td>'+
                              '</tr>'+
                              '<tr>'+
                                    '<td style="width: 140px; background: #808080; border: 1px solid #b3b3b3; padding: 10px; font-size: 13px; color: #fff !important"><strong style="color: #fff !important">Out of Pocket Costs:<br/>* Taxes/Fees <br/>* Domestic Economy Airfares per person</strong></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: rgb(88,56,153)">We inform about the unavoidable government-imposed taxes, airport-imposed fees and airline-imposed fuel surcharges that the airlines are timid to disclose. <br />Over 85% of our bookings require separate domestic positioning flights, as no \'Saver\' award space since airlines flying over 90% capacity.</td>'+
                              '</tr>'+
                              '<tr>'+
                                    '<td style="width: 140px; background: #808080; border: 1px solid #b3b3b3; padding: 10px; font-size: 13px; color: #fff !important"><strong style="color: #fff !important">Only One Way Award Available</strong></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: #000"></td>'+
                                    '<td style="border: 1px solid #b3b3b3; padding: 10px; font-size: 12px; color: rgb(88,56,153)">Only one way award space is available, which happens about 35% of the time. We strongly suggest to lock in the one way award, as we find the unbooked award space over 97% of the time. Waiting for simultaneous roundtrip awards runs the significant risk that the suitable one way award we present now, can easily disappear in the interim.</td>'+
                              '</tr>'+
                        '</tbody>'+
                  '</table></div>'+
                  '<p>We are poised to share your flight details and booking instructions during our scheduled appointment.</p>'+
                  '<ul>'+
                  '<li><strong>review the chart proposal above</strong> to ensure the basic parameters are acceptable to you</li>'+
                  '<li><strong>log on to this project thread at start of appointment</strong> because key info will be shared here</li>'+
                  '<li><strong>expedite a final decision within 6 hrs. of appointment because</strong> of airlines" customer unfriendly policies: <br />- no courtesy holds: awards can be snatched away in the interim <br />- dynamic pricing: award amounts can now fluctuate totally randomly</li>'+
                  '</ul>'+
                  '<p>Please post your reply:</p>'+
                  '<p><strong style="color: #fff !important; background: rgb(77,191,162); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">YES</strong>: if you are comfortable with the above areas of cooperation, and we will commence your appointment as scheduled.</p>'+
                  '<p>or</p>'+
                  '<p><strong style="color: #fff !important; background: rgb(202,63,95); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">NO</strong>: if you don\'t wish to proceed, and we will cancel your appointment and welcome you back when helpful.</p>'+
                  '<p>Thank you, <br />Team BYA</p>'
            },
            // {
            //    title: 'Choose Option',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>With this core information you can make an informed decision:</p>'+
            //        '<div style="display: block; margin: 7px 0;">'+
            //             '<div style="color: #fff !important; background: #4dbfa2; padding: 7px 10px 5px 10px; display: inline-block">OPTION 1:</div>'+
            //             '<div style="padding: 8px; border: 2px solid #4dbfa2; display: block"><p style="padding:0"><span style="background: #4dbfa2; color: #fff">Keep</span> your appointment to receive:</p><ul style="padding-left:0"><li style="list-style-position: inside">award details</li><li style="list-style-position: inside">transfer instructions</li><li style="list-style-position: inside">expedite award to booking queue</li></ul><p>Please be prepared to approve award during your appointment, so if there are other travelers that need to in the decision loop, make sure they are accessible so you can forward them the award details.  Award space is volatile and most airlines don\'t allow courtesy holds, so your quick decision will ensure preserving the award presented.</p></div>'+
            //        '</div>'+
            //        '<div style="display: block; margin: 7px 0;">'+
            //             '<div style="color: #fff !important; background: #ca3f5f; padding: 7px 10px 5px 10px; display: inline-block">OPTION 2:</div>'+
            //             '<div style="padding: 8px; border: 2px solid #ca3f5f; display: block"><p style="padding:0">The award structure outlined in the chart is PREEMPTIVELY THE BEST OUT THERE, so if does not meet your needs, simply <span style="color: #fff; background: #ca3f5f">cancel</span> your appointment, and we welcome you back when helpful. <br />OR <br />If you wish to revise the dates and/or routing, please post such new info below, and we will execute another search .If so, please reschedule your appointment at least 1 day after your current appointment</p></div>'+
            //        '</div>'+
            //        '<p>We look forward to earning your confidence and business.</p>'+
            //        '<p><strong style="color: #fff !important; background: #ffaa33; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">PLEASE POST YOUR REPLY  -</strong></p>'+
            //        '<p><strong style="color: #fff !important; background: rgb(77,191,162); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">KEEP appt.</strong><strong style="color: #fff !important; background: #ffaa33; vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">or</strong><strong style="color: #fff !important; background: rgb(202,63,95); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">CANCEL appt.</strong></p>'+
            //        '<p>Team BYA <br />Steve, Ceca, Ricardo, Andrew, Irina</p>'
            // },
            // {
            //    title: 'Client Conditions New Search',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>Dear:</p>'+
            //        '<p>We are excited share our award search results and booking strategy with you when we call you for our appointment together.  We will appreciate your cooperation with the following:</p>'+
            //        '<ul>'+
            //        '<li><strong>review the chart proposal above</strong> to ensure the basic parameters are acceptable</li>'+
            //        '<li><strong>log on to this project thread at start of appointment</strong> because key info will be shared here</li>'+
            //        '<li><strong>expedite a final decision</strong> to approve award for booking because of airlines" customer unfriendly policies: <br />- no courtesy holds: awards can be snatched away in the interim <br />- dynamic pricing: award amounts can now fluctuate totally randomly</li>'+
            //        '</ul>'+
            //        '<p>Please post your reply:</p>'+
            //        '<p><strong style="color: #fff !important; background: rgb(77,191,162); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">YES</strong>: if you are comfortable with the above areas of cooperation, and we will commence your appointment as scheduled.</p>'+
            //        '<p>or</p>'+
            //        '<p><strong style="color: #fff !important; background: rgb(202,63,95); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">NO</strong>: if you don\'t wish to proceed, and we will cancel your appointment and welcome you back when helpful.</p>'+
            //        '<p>or</p>'+
            //        '<p>No Reply at All Before Appointment:  we will assume you don\'t wish to proceed and will cancel your appointment</p>'+
            //        '<p>Thank you, <br />Team BYA</p>'
            // },
            // {
            //    title: 'Client Conditions Recurring Pitch',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>We are excited share our award search results and booking strategy with you <strong>via this online thread</strong>. We will appreciate your cooperation with the following:</p>'+
            //        '<ul>'+
            //        '<li><strong>review the chart proposal above</strong> to ensure the basic parameters are acceptable</li>'+
            //        '<li><strong>be prepared expedite a final decision</strong> to approve award for booking because of airlines" customer unfriendly policies: <br />- no courtesy holds: awards can be snatched away in the interim <br />- dynamic pricing: award amounts can now fluctuate totally randomly</li>'+
            //        '</ul>'+
            //        '<p>Please post your reply:</p>'+
            //        '<p><strong style="color: #fff !important; background: rgb(77,191,162); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">YES</strong>: if you are comfortable with the above areas of cooperation, and we will commence presenting on this online thread during the next business day.</p>'+
            //        '<p>or</p>'+
            //        '<p><strong style="color: #fff !important; background: rgb(202,63,95); vertical-align: top; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">NO</strong>: or No Reply at All Before Appointment: we will assume you don\'t wish to proceed and will cancel your request and welcome you back when helpful.</p>'+
            //        '<p>Thank you, <br />Team BYA</p>'
            // },
            // {
            //    title: 'Pre Appt Reminders and Trade-Offs',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>Hi XXX-</p>'+
            //        '<p>To ensure that we can be mutually productive in expediting booking your award:</p>'+
            //        '<ul>'+
            //        '<li>review the award trade-off overview below to make sure that all your submission info is accurate.</li>'+
            //        '<li>it is critical that you have any third party/other passengers decision makers accessible to you or you have final authority to approve third parties\' awards.</li>'+
            //        '<li>you need to be online on this site on time.</li>'+
            //        '<li>your cooperation will ensure that we can actually secure what we found for you.</li>'+
            //        '</ul>'+
            //        '<p>Your search results are preemptively the best out there, and we hope that the award profile below will merit your approval:</p>'+
            //        '<ul>'+
            //        '<li>Date/Miles/Class of Service: <br />X/XX / XXK per person / Economy Domestic and Business International <br />X/XX / XXK per person / Economy Domestic and Business International</li>'+
            //        '<li># stops/connection duration: <br />OUTBOUND - X stop / X hours and X hours <br />INBOUND - X stop / X hours and X hours</li>'+
            //        '<li>airport taxes/airline-imposed fuel surcharges: <br />OUTBOUND - $XX approximate per person <br />INBOUND - $XX approximate per person</li>'+
            //        '<li>domestic Economy positioning flights: <br />OUTBOUND - No award space XXX-XXX, you purchase at $ per person <br />INBOUND - No award space XXX-XXX, you purchase at $ per person</li>'+
            //        '<li>only one direction of your award flight is available (Xbound): <br />Because award space is so volatile, we recommend you book this one-way flight. Waiting for both directions of travel to be released simultaneously risks losing this attractive one-way flight in the interim. We will continue to monitor award space for the other half of your trip.</li>'+
            //        '</ul>'+
            //        '<p>If any of these trade-offs are indeed a deal breaker, then we will understand if you choose to cancel your appointment. But we are hopeful you will find the award profile acceptable.</p>'+
            //        '<p>We look forward to earning your confidence and award approval during our appointment.</p>'+
            //        '<p>Team BYA <br />Steve, Ceca, Ricardo, Andrew, Irina</p>'
            // },
            // {
            //    title: 'PRE APPT ONE WAY',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>Dear [name]:</p>'+
            //        '<p>As award space can be incredibly volatile and most airlines don\'t allow \'courtesy holds\', its important that we are fully prepared before your phone appointment, so as to expedite your award redemption after your phone call.</p>'+
            //        '<p>The following chart shows the best \'Saver\' (lowest level) award itinerary availability for your evaluation</p>'+
            //        '<table style="border:1px solid #000; border-collapse:collapse; border-spacing:0; margin-top:10px">'+
            //        '<thead>'+
            //        '<tr>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px"><strong>PROJECTED AWARD</strong></th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Date / Route</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Class of Service</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Award Cost (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Award Taxes, Fees, and Fuel Surcharges</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px"># Stops / (Layover Times)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Economy Segments (Duration & Cost)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Luggage Claim / Recheck</th>'+
            //        '</tr>'+
            //        '</thead>'+
            //        '<tbody>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Outbound Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Outbound Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Stopover Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Stopover Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Inbound Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Inbound Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '</tbody>'+
            //        '</table>'+
            //        '<p>We strongly suggest to lock in the available one-way award NOW. Waiting for both directions of travel to be released simultaneously risks losing this attractive one-way flight in the interim. Due to the volatility in released award space, we book flights one direction at a time with over 30% of our clients.  Rest assured, strategy has proven effective in ultimately securing round-trip flights 99% of the time. We will continue to look for flights for the other half of your award and will report back on a bi-weekly basis. </p>'+
            //        '<p>On the very, very off-chance the other half of your award is not released within 4 weeks of your date window, we will split the cost of any mileage redeposit/cancellation fees, so that you know we are vested in a successful outcome.</p>'+
            //        '<p><strong>We are confident in this one way booking strategy</strong></p>'+
            //        '<p>* Our award search was exhaustive, spanning all three airline alliances and dozens of creative and non-intuitive routing options.</p>'+
            //        '<p>* Rest assured, the airlines we will recommend have been flown and personally vetted by our company principals.  And we have had overwhelmingly positive feedback from your fellow clients.</p>'+
            //        '<p>* Our booking service is $199 per roundtrip award per person.</p>'+
            //        '<p><strong style="background-color:#6aa84f">PROCEED: If you are generally satisfied with above award routing,</strong></p>'+
            //        '<p>* We will call you at your scheduled time.</p>'+
            //        '<p>* Please be aware our phone number displays \'Unknown\'</p>'+
            //        '<p>* Our phone call/online consultation will cover:</p>'+
            //        '<p>&nbsp;- specific airlines, routing, departure/arrival times</p>'+
            //        '<p>&nbsp;- credit card/hotel transfer and new account registration instructions, where applicable</p>'+
            //        '<p>&nbsp;- answer any questions</p>'+
            //        '<p>&nbsp;- agree on award approval/expedited booking timetable; our goal is same day award booking/prepay taxes</p>'+
            //        '<p style="color:#6aa84f"><strong>IMPORTANT</strong></p>'+
            //        '<p style="color:#6aa84f">* We share your award details on our secure site to protect your account security and privacy. So, it is <strong>mandatory</strong> that you are logged online on this site (paste link) BEFORE we call you for your phone appointment.</p>'+
            //        '<p style="color:#6aa84f; text-decoration:underline"><strong>IMPORTANT</strong></p>'+
            //        '<p style="color:#6aa84f; text-decoration:underline">If you need any other third parties to approve the awards, lit is <strong>mandatory</strong> that you provide us their email address, so they receive award info and you all can make real-time decisions together.</p>'+
            //        '<p style="color:#6aa84f; text-decoration:underline">Having to wait for 3rd party decision makers much beyond your appointment  puts the award space we found for you, at considerable risk.</p>'+
            //        '<p><span style="background-color:#e06666"><strong>DECLINE:</strong>if you are not satisfied.</span></p>'+
            //        '<p>please cancel your phone appointment and we will welcome you back for future award requests.</p>'+
            //        '<p><strong style="background-color:#8e7cc3">RESCHEDULE an appointment in 2 weeks if you wish to wait for possible \'better\' options</strong></p>'+
            //        '<p>In our 6 years of experience, we find that choosing to wait instead of booking what is currently proposed has the following results in follow up award searches:</p>'+
            //        '<p>* 5% better</p>'+
            //        '<p>* 15% same</p>'+
            //        '<p>* 65% worse</p>'+
            //        '<p>* 15% no space at all</p>'+
            //        '<p>Please post below which of these options you choose:</p>'+
            //        '<p><strong>PROCEED</strong><br /><strong>DECLINE</strong><br /><strong>WAIT</strong></p>'+
            //        '<p>We look forward to the challenge of earning your confidence and business.</p>'+
            //        '<p>The BookYourAward Crew</p>'
            // },
            // {
            //    title: 'PRE APPT ONLINE',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>Dear Phillip:</p>'+
            //        '<p>Below is an overview of the award flights available for your trip.  Please review it and let us know how you’d like to proceed by responding in this secure messaging system.</p>'+
            //        '<p>Award space is incredible volatile and most airlines don’t allow us to hold flights, so we request you get back to us as soon as possible so we can answer any questions and complete your booking while the award space is still available.</p>'+
            //        '<table style="border:1px solid #000; border-collapse:collapse; border-spacing:0; margin-top:10px">'+
            //        '<thead>'+
            //        '<tr>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px"><strong>PROJECTED AWARD</strong></th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Date / Route</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">International Class of Service</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px"># Stops / (Layover Times)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Award Cost (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Flight Taxes, Fees, and Fuel Surcharges (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Economy Segments: Duration / Cost (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Luggage Claim / Recheck</th>'+
            //        '</tr>'+
            //        '</thead>'+
            //        '<tbody>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Outbound Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Outbound Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Stopover Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Stopover Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Inbound Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Inbound Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '</tbody>'+
            //        '</table>'+
            //        '<p>* Our award search was exhaustive, spanning all options available using your mileage resources, including creative and non-intuitive routing options if necessary.</p>'+
            //        '<p>* Rest assured, our team has flown and vetted all airlines we recommend, plus we’ve received positive reviews from other clients.</p>'+
            //        '<p>After reviewing the chart, please let us know how you prefer to proceed.</p>'+
            //        '<p><strong style="background-color:#00ff00">PROCEED: If you are generally satisfied with above award routing,</strong></p>'+
            //        '<p>* You can request an online consultation covering</p>'+
            //        '<p> - specific airlines, routings, departure/arrival times<br /> - instructions as needed to register for airline programs and/or transfer credit card/hotel points <br /> - answer any questions<br /> - review timetable for award approval and bookings; our goal is to reserve by end of day</p>'+
            //        '<p style="color: #38761d"><strong><span style="text-decoration:underline;">IMPORTANT</span><br />Please have all other parties involved with the decision online and/or available by phone so you can make real-time decisions together.  Award space is volatile and at risk of disappearing.</strong></p>'+
            //        '<p style="background-color:#ffff00"><strong>RESCHEDULE: if you are not 100% ready to confirm reservations within 12 hours</strong></p>'+
            //        '<p>Award space changes quickly. If you are still working out travel dates, destinations, or other details, please reschedule your appointment for a time in the future when you will be ready to confirm flights immediately.</p>'+
            //        '<p>We cannot guarantee these exact same flights will be available in the future, but we will share up-to-date details with you when you’re ready to book.</p>'+
            //        '<p style="background-color:#ff0000"><strong>DECLINE: if you are not satisfied with the award structure above,</strong></p>'+
            //        '<p>In our 6 years of booking awards, we see certain trends emerge on the structure and pricing of award availability.  Based on your flight criteria and mileage resources, we estimate that waiting 1-2 weeks for different award options typically results in the following:</p>'+
            //        '<p> * 10% of the time we see better availability<br /> * 25% of the time we see the same or similar availability<br /> * 50% of the time we see worse availability<br /> * 15% of the time we see NO availability</p>'+
            //        '<p>However, if today’s flights options are an absolute deal-breaker for you, please cancel your request and feel free to re-open this or other travel requests in the future.</p>'+
            //        '<p><strong>Please post below <span style="text-decoration:underline">which</span> of these options you choose:</strong><br /><span style="background-color:#00ff00">PROCEED</span> or <span style="background-color:#ffff00">RESCHEDULE</span> or <span style="background-color:#ff0000">CANCEL</span></p>'+
            //        '<p>We look forward to the challenge of earning your confidence and business. <br />The BYA Crew (Steve, Ceca, Becky, and Ricardo</p>'
            // },
            // {
            //    title: 'PRE APPT ONLINE (ONE-WAY)',
            //    image:  'pre-appt.png',
            //    description: '',
            //    html:
            //        '<p>Dear Phillip:</p>'+
            //        '<p>Below is an overview of the award flights available for your trip.  Please review it and let us know how you’d like to proceed by responding in this secure messaging system.</p>'+
            //        '<p><strong>Right now, only one direction of your award flight is available.  Because award space is so volatile, we recommend you book this one-way flight.  We will continue to monitor award space for the other half of your trip.  There is no additional miles, taxes, or service fees to book your reservation as two separate one-ways.</strong></p>'+
            //        '<p>We use this booking strategy for about a third of our clients and it is successful over 97% of the time.</p>'+
            //        '<table style="border:1px solid #000; border-collapse:collapse; border-spacing:0; margin-top:10px">'+
            //        '<thead>'+
            //        '<tr>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px"><strong>PROJECTED AWARD</strong></th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Date / Route</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">International Class of Service</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px"># Stops / (Layover Times)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Award Cost (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Flight Taxes, Fees, and Fuel Surcharges (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Economy Segments: Duration / Cost (per person)</th>'+
            //        '<th style="background:#ffe061; text-align:left; font-weight:normal; border:1px solid #000; padding:5px">Luggage Claim / Recheck</th>'+
            //        '</tr>'+
            //        '</thead>'+
            //        '<tbody>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Outbound Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Outbound Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Stopover Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Stopover Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Inbound Option 1</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '<tr>'+
            //        '<td style="border:1px solid #000;padding:5px">Inbound Option 2</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px">Business</td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '<td style="border:1px solid #000;padding:5px"></td>'+
            //        '</tr>'+
            //        '</tbody>'+
            //        '</table>'+
            //        '<p>* Our award search was exhaustive, spanning all options available using your mileage resources, including creative and non-intuitive routing options if necessary.</p>'+
            //        '<p>* Rest assured, our team has flown and vetted all airlines we recommend, plus we’ve received positive reviews from other clients.</p>'+
            //        '<p>After reviewing the chart, please let us know how you prefer to proceed.</p>'+
            //        '<p><strong style="background-color:#00ff00">PROCEED: If you are generally satisfied with above award routing,</strong></p>'+
            //        '<p>* You can request an online consultation covering</p>'+
            //        '<p> - specific airlines, routings, departure/arrival times<br /> - instructions as needed to register for airline programs and/or transfer credit card/hotel points <br /> - answer any questions<br /> - review timetable for award approval and bookings; our goal is to reserve by end of day</p>'+
            //        '<p style="color: #38761d"><strong><span style="text-decoration:underline;">IMPORTANT</span><br />Please have all other parties involved with the decision online and/or available by phone so you can make real-time decisions together.  Award space is volatile and at risk of disappearing.</strong></p>'+
            //        '<p style="background-color:#ffff00"><strong>RESCHEDULE: if you are not 100% ready to confirm reservations within 12 hours</strong></p>'+
            //        '<p>Award space changes quickly.  If you are still working out travel dates, destinations, or other details, please reschedule your appointment for a time in the future when you will be ready to confirm flights immediately.</p>'+
            //        '<p>We cannot guarantee these exact same flights will be available in the future, but we will share up-to-date details with you when you’re ready to book.</p>'+
            //        '<p style="background-color:#ff0000"><strong>DECLINE: if you are not satisfied with the award structure above,</strong></p>'+
            //        '<p>In our 6 years of booking awards, we see certain trends emerge on the structure and pricing of award availability.  Based on your flight criteria and mileage resources, we estimate that waiting 1-2 weeks for different award options typically results in the following:</p>'+
            //        '<p> * 10% of the time we see better availability<br /> * 25% of the time we see the same or similar availability<br /> * 50% of the time we see worse availability<br /> * 15% of the time we see NO availability</p>'+
            //        '<p>However, if today’s flights options are an absolute deal-breaker for you, please cancel your request and feel free to re-open this or other travel requests in the future.</p>'+
            //        '<p><strong>Please post below <span style="text-decoration:underline">which</span> of these options you choose:</strong><br /><span style="background-color:#00ff00">PROCEED</span> or <span style="background-color:#ffff00">RESCHEDULE</span> or <span style="background-color:#ff0000">CANCEL</span></p>'+
            //        '<p>We look forward to the challenge of earning your confidence and business. <br />The BYA Crew (Steve, Ceca, Becky, and Ricardo)</p>'
            // },
            // {
            //     title: 'PROPOSAL CHART',
            //     image:  'proposal_prep.png',
            //     description: '',
            //     html:
            //     '<p>As your ally and advocate, please note that the chart below describes your BEST award option(s) using established and personally vetted airlines with 170-180 degree seat reclines for international sectors, which represent our thorough and exhaustive search representing over 60 routings and up to 12 airlines.</p>'+
            //     '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-family:arial; width:100%">'+
            //     '<tbody>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"></td>'+
            //     '<td valign="top" style="width:20%;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>OPTION 1</b></td>'+
            //     '<td valign="top" style="width:5px;border-style:solid;background:#ffffff;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="width:20%;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>OPTION 2</b></td>'+
            //     '<td valign="top" style="width:5px;border-style:solid;background:#ffffff;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="border-style:solid;background:#dedede;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>EXPLANATIONS</b></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#48b397;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>AWARD DESCRIPTION</b></td>'+
            //     '<td valign="top" colspan="5" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#b3b3b3;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Outbound</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Date/Routing/ Class of Service</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Mileage Redemption (pp)</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Number of Connections/ Layover Duration</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">Any fewer connections would require redeeming a ‘Standard’award for 250-400% more miles</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#b3b3b3;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Inbound</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Date/Routing/ Class of Service</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Mileage Redemption (pp)</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Number of Connections/ Layover Duration</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">Any fewer connections would require redeeming a ‘Standard’award for 250-400% more miles</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#b3b3b3;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Stopover</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Date/Routing/ Class of Service</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Mileage Redemption (pp)</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Number of Connections/ Layover Duration</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">Any fewer connections would require redeeming a ‘Standard’award for 250-400% more miles</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#48b397;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>OUT OF POCKET COSTS</b></td>'+
            //     '<td valign="top" colspan="5" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Taxes/Fees</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">Governments charge mandatory airport upkeep, security and premium class taxes. Airlines levy mandatory "fuel surcharges"</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Domestic Economy Class Airfare/ Flight Duration</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">With airlines running at over 90% capacity, there is no award space on your domestic sector(s). Airlines are not required to re-accomodate passengers who miss their connection between separately booked domestic purchase flights and internal award flights.So, we mandate a minimum 2 hr connection time.</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#48b397;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>MISCELLANEOUS</b></td>'+
            //     '<td valign="top" colspan="5" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Outbound Baggage Clim and Recheck</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">This award includes a short flight in Economy that has no reciprocal baggage policy with your international carrier. You will need to claim your luggage in-between flights to re-check it with the second airline. Your baggage will be subject to limits on number of items and weight based on the Economy sector and may be require baggage fees.</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" colspan="6" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Transfer Liability</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 1px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:0 1.0px 1px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">Transfers may post to account on a delayed basis despite immediate confirmation, impacting award availability in the interim.  This scenario happens less than 2% of the time. So while we will very likely find alternate award space once transfer is complete, our company has no liability for delayed transfers or loss of proposed award space.</td>'+
            //     '</tr>'+
            //     '</tbody>'+
            //     '</table>'+
            //     '<p>We understand that no one can determine the value of your time, money and mileage like you, so we have provided you all of the possible factors that might impact your award routing, class of service and/or out of pocket costs to help you make an informed decision.</p>'+
            //     '<p>Sincerely, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Proposal Chart-One Way',
            //     image:  'proposal_prep.png',
            //     description: '',
            //     html:
            //     '<p><strong>Right now, only one direction of your award flight is available.  Your specific flight details for the available pieces are shown below.</strong></p>'+
            //     '<p>Because award space is so volatile, we recommend you book this one-way flight.  Waiting for both directions of travel to be released simultaneously risks losing this attractive one-way flight in the interim.</p>'+
            //     '<p>We will continue to monitor award space for the other half of your trip.  There is no additional miles, taxes, or service fees to book your reservation as two separate one-ways.  We use this booking strategy for about a third of our clients and it is successful in ultimately securing round-trip business class flights over 97% of the time.</p>'+
            //     '<p><span style="background-color:00ffff">Option 1</span><br />70,000 Amex miles per person + approx $150pp tax</p>'+
            //     '<p>No award space New York-Phoenix; you purchase separately after international award is confirmed (sample flight shown)</p>'+
            //     '<p>[INSERT FLIGHT HERE]</p>'+
            //     '<p><span style="background-color:00ffff">Option 2</span><br />70,000 Amex miles per person + approx $150pp tax</p>'+
            //     '<p>[INSERT FLIGHT HERE]</p>'+
            //     '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-family:arial; width:100%; border:1px solid #b3b3b3; margin-top:15px">'+
            //     '<tbody>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border:1px solid #b3b3b3; padding:5px 10px"><b>Transfer Liability</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border:1px solid #b3b3b3; padding:5px 10px; color:#000">Transfers may post to account on a delayed basis despite immediate confirmation, impacting award availability in the interim. While we will very likely find alternate award space once transfer is complete, our company has no liability for delayed transfers or loss of proposed award space.</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border:1px solid #b3b3b3; padding:5px 10px"><b>Separately Purchased Segments</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border:1px solid #b3b3b3; padding:5px 10px; color:#000">This award includes a paid flight operated in economy, which you will need to purchase independently AFTER you’ve received your final booking confirmation on the international ticket. <br /><br />You will most likely need to claim your luggage in-between flights to re-check it with the second airline; baggage size limits and/or fees may apply. We always recommend showing BOTH the paid and award tickets at check-in to politely request if they’ll check your bags all the way through.</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border:1px solid #b3b3b3; padding:5px 10px"><b>Booking Fees</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border:1px solid #b3b3b3; padding:5px 10px; color:#000">$199 per person, upon booking round-trip award</td>'+
            //     '</tr>'+
            //     '</tbody>'+
            //     '</table>'+
            //     '<p>Please let us know if you have any additional questions.</p>'+
            //     '<p>With your approval, we will contact the airline(s) to book your award as soon as possible since most airlines don’t allow courtesy holds and award space is notoriously volatile.</p>'+
            //     '<p>Sincerely, <br />BookYourAward Team (Steve, Becky, Ceca, and Ricardo)</p>'
            // },
            {
                title: 'Transfer Instructions',
                image: 'pre-appt.png',
                description: '',
                html:
                    '<p>Since most airlines don`t offer courtesy holds, it is important that you quickly execute the following transfer to ensure we can book what we have proposed.</p>'+
                    '<p>We value your mileage and credit card account security and privacy. So, we have instituted a three point security protocol to ensure that your accounts will remain protected while working with us.</p>'+
                    '<ol style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Our company has proprietary software that allow you to share access to your account with us, without revealing your private login and password information.</li>'+
                    '<li style="list-style-position: inside;">You will be sent a yellow box with the requested programs to share and you simply click that box to activate the auto-sharing function.</li>'+
                    '<li style="list-style-position: inside;">If you choose to share account info on this thread, our site itself is password protected.</li>'+
                    '<li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
                    '</ol>'+
                    '<p><strong>AMEX</strong></p>'+
                    '<ol style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com or use an already existing one</li>'+
                    '<li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points in real time: $$$K</li>'+
                    '</ol>'+
                    '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow" target="_blank">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></p>'+
                    '<p><strong>CHASE</strong></p>'+
                    '<ol style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now or use an already existing one</li>'+
                    '<li style="list-style-position: inside;">Login to your Ultimate Rewards account or contact Chase at 800-537-7783 to transfer the following number of Chase points in real time: XXXK</li>'+
                    '</ol>'+
                    '<p><strong>CITI</strong></p>'+
                    '<ol style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now or use an already existing one</li>'+
                    '<li style="list-style-position: inside;">Login to your Thank You points account or contact Citi at 877-288-2484 to transfer the following number of Citi points (usually instant, but sometimes within 2-3 days): XXXK</li>'+
                    '</ol>'+
                    '<p><strong>MARRIOTT</strong></p>'+
                    '<ol style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now or use an already existing one</li>'+
                    '<li style="list-style-position: inside;">Click the link below to transfer the following number of Marriott points (usually within 24 hours, but sometimes several days) : XXXK</li>'+
                    '</ol>'+
                    '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://www.marriott.com/loyalty/redeem/travel/points-to-miles.mi" target="_blank">https://www.marriott.com/loyalty/redeem/travel/points-to-miles.mi</a></p>'
            },
            {
               title: 'No Results/Cancel Appt/Search Again',
               image:  'pre-appt.png',
               description: '',
               html:
                   '<p>Hi XXX-</p>'+
                   '<p>Sometimes our search efforts come back with less than ideal scenarios.  We are dependent on what the airlines release and at this time the option available might be more that you are willing to consider as a potential trade off.</p>'+
                   '<p>We are happy to run a continuous recurring search in hopes that award options improve and we can present new options for your consideration and hopeful approval in the next week or so.</p>'+
                   '<p>Please respond below if you are in agreement and we will continue our efforts.</p>'+
                   '<p>All the best, <br />Team BYA</p>'
            },
            // {
            //     title: 'Inter Accounts Transfer Chart',
            //     image:  'proposal_prep.png',
            //     description: '',
            //     html:
            //     '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-family:arial; width:100%">'+
            //     '<tbody>'+
            //     '<tr>'+
            //     '<td valign="top" rowspan="2" style="width:120px;background:#808080;color:#fff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b style="font-weight:bold">INTER ACCOUNTS MILEAGE TRANSFERS</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">To aggregate enough miles for you award, you well need to execute transfers between accounts in the same program.</td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px; color:#fff"><b style="font-weight:bold">Giver Name</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px; color:#fff"><b style="font-weight:bold">Receiver Name</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px; color:#fff"><b style="font-weight:bold">Program</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px; color:#fff"><b style="font-weight:bold">#miles/points to be transferred</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px; color:#fff"><b style="font-weight:bold">cost per mile/point</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px; color:#fff"><b style="font-weight:bold">posting time</b></td>'+
            //     '<td valign="top" style="background:#ffffff;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '</tbody>'+
            //     '</table>'
            // },
            {
                title: 'Recurring Search',
                image:  'proposal_prep.png',
                description: '',
                html:
                '<p>Dear:</p>'+
                '<p>We share your anticipation about finding a suitable low cost \'Saver\' award with the most efficient routing possible. While our award searches on your behalf are ongoing and exhaustive, ultimately we can only search and find what the airlines choose to to release. Even the most creative and resourceful \'mileage bloodhound\' can only succeed if there is prey to be hunted!</p>'+
                '<p>The airlines often force us into a perseverance situation when playing the mileage game. We urge your continued patience and wish to reassure you that we expect to include you as part of the 98% of clients that we ultimately find suitable award space.</p>'+
                '<p>There is no need for you to make ongoing inquiries about the status of your award request; we will surely alert you with alacrity once there is a promising opportunity.</p>'+
                '<p>Look forward <br />Team BYA</p>'
            },
            {
                title: 'Cancel Project',
                image:  'proposal_prep.png',
                description: 'We have endeavored to communicate with you in a timely and clear manner.',
                html:
                '<p>Dear :</p>' +
                '<p>We have endeavored to communicate with you in a timely and clear manner. Haven\'t heard from you in a bit, so we welcome you to re-engage if/when you are so inclined.</p>' +
                '<p>Cheers,<br>(Staff Name)</p>'
            },
            {
               title: 'Recurring Search Results Alert',
               image:  'proposal_prep.png',
               description: '',
               html:
                   '<p>Dear XX</p>'+
                   '<p>Thanks for your patience. We have found award space and are poised to share it with you.  Please advise if you are still interested in engaging our services. If so, we will present award search results.</p>'+
                   '<p>Cheers, <br />Team BookYourAward</p>'
            },
            // {
            //     title: 'Australia / New Zealand',
            //     image:  'proposal_prep.png',
            //     description: '',
            //     html:
            //     '<p>While Australia and New Zealand are legendary for their scenic landscapes, you can also enjoy equally \'scenic\' routing when using award space!!</p>' +
            //     '<p>Nonstop flight award space is especially sparse since airlines often sell out to paying customers,so there is no incentive to release the lowest level award space. Our strategic workaround is to find you alternate \'scenic\' routing that provides you the certainty and peace of mind of securing an award redemption now.</p>' +
            //     '<p>Your \'scenic\' options may include</p>' +
            //     '<ul style="padding-left:0"><li style="list-style-position: inside;">Delta/partners: a free layover (under 23 hours) outbound and/or inbound to explore an interesting Asian or destination</li>' +
            //     '<li style="list-style-position: inside;">United/partners: a free stopover outbound OR inbound (you can decide duration, subject to award space) to explore an interesting Asian destination</li>' +
            //     '<li style="list-style-position: inside;">American/partners: a free stopover outbound AND/OR inbound (any duration)  to explore an interesting Asian or Gulf state destination. Please be aware that American Airlines award redemptions via Asia are 110K each way and via Mideast 150K each way.</li>' +
            //     '</ul>' +
            //     '<p>We always search the most streamlined options first, but since those awards are rare, please let us know how you wish to proceed with considering other routings so that we can successfully award space on your behalf.</p>' +
            //     '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Africa',
            //     image: 'proposal_prep.png',
            //     description: '',
            //     html:
            //     '<p>While Africa is legendary for its scenic landscapes, you can also enjoy equally \'scenic\' routing when using award space!! Nonstop flight award space is especially sparse as airlines often sell paid airfares close to or at capacity, so there is no incentive to release \'Saver\' award space.</p>' +
            //     '<p>Our strategic workaround is to find you alternate \'scenic\' routing that provides you the certainty and peace of mind of securing an award redemption now, with the added bonus of having the option of a free multi-day stopover in a cool European city or Gulf state.</p>' +
            //     '<p>Your \'scenic\' options may include</p>' +
            //     '<ul style="padding-left:0">' +
            //     '<li style="list-style-position: inside;">Delta/partners: a free layover (under 23 hours) outbound and/or inbound to explore an interesting European destination</li>' +
            //     '<li style="list-style-position: inside;">United/partners: a free stopover outbound OR inbound (over 24 hours, you can decide duration, subject to award space) to explore an interesting European or northern Africa destination</li>' +
            //     '<li style="list-style-position: inside;">American/partners:  a free layover (under 23 hours) outbound and/or inbound to explore an interesting European city or Gulf state destination.  Longer stopovers may be available at additional mileage.  Please be aware that American Airlines treats some awards via Mideast as 2 separate awards, so please expect to redeem 120K one way.</li>' +
            //     '</ul>' +
            //     '<p>We always search the most streamlined options first, but since those awards are rare, please let us know how you wish to proceed with considering other routings so that we can successfully award space on your behalf.</p>' +
            //     '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            
            {
                title: 'Ethiopian Airlines',
                image: 'objections.png',
                description: '',
                html:
                    '<p>We can appreciate your first blush hesitation about accepting an award with Ethiopian Airlines, as we actually had the same sense of skepticism when we were first exposed to that award option.</p>'+
                    '<p>We would like to provide you objective facts instead of subjective opinions, to bolster your confidence to make an informed decision.</p>'+
                    '<p>BAD THIRD PARTY REVIEWS <br />Bear with us on the math about Skytrax, the primary airline review website, because it\'s insightful:</p>'+
                    '<p><strong>427</strong> negative reviews of Ethiopian Airlines on Skytrax since 2010 <br />vs <br /><strong>945,000</strong> Business Class passengers since 2010. <br />(10 long haul intl. routes with average 30 Business Class seats flying 365 days a year x 9 years)</p>'+
                    '<p>The reality is Skytrax is <strong>not</strong> remotely a statistically relevant sample size. Rather, Skytrax is primarily a venting vehicle for ultra-outlier negative situations that every airline stumbles with once in a while. We don\'t put any credence in such a tiny sample size and neither should you.</p>'+
                    '<p>BAD BLOGGER REVIEWS</p>'+
                    '<ul style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Irrelevant comparisons: <br />Airlines like Singapore and Cathay Pacific are considered the \'gold\' standard Business Class cabins and service, but these airlines don\'t even service USA-Africa routes, so its a moot comparison.</li>'+
                    '<li style="list-style-position: inside;">Expensive comparisons <br />Airlines like Emirates that fly to Africa, have taxes and fuel surcharges exceeding $1600 per roundtrip award, as well as award redemptions 30-45% more than Ethiopian. And, only limited airline and credit card partners even have a partnership with Emirates.</li>'+
                    '</ul>'+
                    '<p>OUR PRINCIPALS REVIEWS</p>'+
                    '<p>Gary and Steve have personally flown on Ethiopian, and as BookYourAward company owners, they will only recommend airlines to clients like you, that they are willing to book for their friends and family. The airline has invested in updated aircraft like the 787.</p>'+
                    '<p>OUR CUSTOMERS UNANIMOUS REVIEWS</p>'+
                    '<p>This is the ultimate indicator. Like you, a good chunk of our clients are initially hesitant about Ethiopian Airlines and yet, they choose to trust our reassurance about a suitable in-flight experience. After 8 years and many hundreds of Ethiopian Airline bookings, we have had a grand total of <strong>zero</strong> client complaints. Surely, we couldn\'t maintain our company reputation, if we had hordes of clients spreading negative reactions.</p>'+
                    '<p>Thanks for hearing us out and hope we have endeavored to provide you helpful context and await your guidance to proceed together.</p>'
            },
            {
                title: 'China Southern',
                image: 'objections.png',
                description: '',
                html:
                    '<p>We can appreciate your first blush hesitation about accepting an award with China Southern Airlines, as we actually had the same sense of skepticism when we were first exposed to that award option.</p>'+
                    '<p>We would like to provide you objective facts instead of subjective opinions, to bolster your confidence to make an informed decision.</p>'+
                    '<p>BAD THIRD PARTY REVIEWS <br />Bear with us on the math about Skytrax, the primary airline review website, because it\'s insightful:</p>'+
                    '<p><strong>1742</strong> reviews of China Southern Airlines on Skytrax since 2010 <br />vs <br /><strong>1,890,000</strong> Business Class passengers since 2010. <br />(20 long haul intl. routes with average 30 Business Class seats flying 365 days a year x 9 years)</p>'+
                    '<p>The reality is Skytrax is <strong>not</strong> remotely a statistically relevant sample size. Rather, Skytrax is primarily a venting vehicle for ultra-outlier negative situations that every airline stumbles with once in a while.</p>'+
                    '<p>Further, the bulk of the reviews are over 6 years old and the airline has invested substantial resources in new aircraft and Business Class amenities over the last 3 years.</p>'+
                    '<p>We don\'t put any credence in such a tiny sample size and neither should you.</p>'+
                    '<p>BAD BLOGGER REVIEWS</p>'+
                    '<ul style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Irrelevant comparisons: <br />Airlines like Singapore and Cathay Pacific are considered the \'platinum\' standard Business Class cabins and service, while China Southern is merely \'gold\'. Please rest assured that comparative differences are mostly subjective and those that are objective, are truly incremental/stylistic in nature, not impacting your core comfort, enjoyment and safety.</li>'+
                    '<li style="list-style-position: inside;">Expensive comparisons <br />Airlines like Emirates have taxes and fuel surcharges exceeding $1600 per roundtrip award, as well as award redemptions 30-45% more than China Southern. And, only limited airline and credit card partners even have a partnership with Emirates.</li>'+
                    '</ul>'+
                    '<p>OUR PRINCIPALS REVIEWS</p>'+
                    '<p>Gary and Steve have personally flown on China Southern, and as BookYourAward company owners, they will only recommend airlines to clients like you, that they are willing to book for their friends and family. The airline has invested in updated aircraft like the 787.</p>'+
                    '<p>OUR CUSTOMERS UNANIMOUS REVIEWS</p>'+
                    '<p>This is the ultimate indicator. Like you, a good chunk of our clients are initially hesitant about China Southern and yet, they choose to trust our reassurance about a suitable in-flight experience. After 8 years and many hundreds of China Southern bookings, we have had a grand total of <strong>zero</strong> client complaints. Surely, we couldn\'t maintain our company reputation, if we had hordes of clients spreading negative reactions.</p>'+
                    '<p>Thanks for hearing us out and hope we have endeavored to provide you helpful context and await your guidance to proceed together.</p>'
            },
            {
                title: 'China Eastern',
                image: 'objections.png',
                description: '',
                html:
                    '<p>We can appreciate your first blush hesitation about accepting an award with China Eastern Airlines, as we actually had the same sense of skepticism when we were first exposed to that award option.</p>'+
                    '<p>We would like to provide you objective facts instead of subjective opinions, to bolster your confidence to make an informed decision.</p>'+
                    '<p>BAD THIRD PARTY REVIEWS <br />Bear with us on the math about Skytrax, the primary airline review website, because it\'s insightful:</p>'+
                    '<p><strong>620</strong> negative reviews of China Eastern Airlines on Skytrax since 2010 <br />vs <br /><strong>1,345,000</strong> Business Class passengers since 2010. <br />(15 long haul intl. routes with average 30 Business Class seats flying 365 days a year x 9 years)</p>'+
                    '<p>The reality is Skytrax is <strong>not</strong> remotely a statistically relevant sample size. Rather, Skytrax is primarily a venting vehicle for ultra-outlier negative situations that every airline stumbles with once in a while.</p>'+
                    '<p>Further, the bulk of the reviews are over 6 years old and the airline has invested substantial resources in new aircraft and Business Class amenities over the last 3 years.</p>'+
                    '<p>We don\'t put any credence in such a tiny sample size and neither should you.</p>'+
                    '<p>BAD BLOGGER REVIEWS</p>'+
                    '<ul style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Irrelevant comparisons: <br />Airlines like Singapore and Cathay Pacific are considered the \'platinum\' standard Business Class cabins and service, while China Eastern is merely \'gold\'. Please rest assured that comparative differences are mostly subjective and those that are objective, are truly incremental/stylistic in nature, not impacting your core comfort, enjoyment and safety.</li>'+
                    '<li style="list-style-position: inside;">Expensive comparisons <br />Airlines like Emirates have taxes and fuel surcharges exceeding $1600 per roundtrip award, as well as award redemptions 30-45% more than China Eastern. And, only limited airline and credit card partners even have a partnership with Emirates.</li>'+
                    '</ul>'+
                    '<p>OUR PRINCIPALS REVIEWS</p>'+
                    '<p>Gary and Steve have personally flown on China Eastern, and as BookYourAward company owners, they will only recommend airlines to clients like you, that they are willing to book for their friends and family. The airline has invested in updated aircraft like the 787.</p>'+
                    '<p>OUR CUSTOMERS UNANIMOUS REVIEWS</p>'+
                    '<p>This is the ultimate indicator. Like you, a good chunk of our clients are initially hesitant about China Eastern and yet, they choose to trust our reassurance about a suitable in-flight experience. After 8 years and many hundreds of China Eastern bookings, we have had a grand total of <strong>zero</strong> client complaints. Surely, we couldn\'t maintain our company reputation, if we had hordes of clients spreading negative reactions.</p>'+
                    '<p>Thanks for hearing us out and hope we have endeavored to provide you helpful context and await your guidance to proceed together.</p>'
            },
            {
                title: 'Egypt Air',
                image: 'objections.png',
                description: '',
                html:
                    '<p>We can appreciate your first blush hesitation about accepting an award with Egypt Air, as we actually had the same sense of skepticism when we were first exposed to that award option.</p>'+
                    '<p>We would like to provide you objective facts instead of subjective opinions, to bolster your confidence to make an informed decision.</p>'+
                    '<p>BAD THIRD PARTY REVIEWS <br />Bear with us on the math about Skytrax, the primary airline review website, because it\'s insightful:</p>'+
                    '<p><strong>335</strong> negative reviews of Egypt Air on Skytrax since 2010 <br />vs <br /><strong>487,000</strong> Business Class passengers since 2010. <br />(5 long haul intl. routes with average 30 Business Class seats flying 365 days a year x 9 years)</p>'+
                    '<p>The reality is Skytrax is <strong>not</strong> remotely a statistically relevant sample size. Rather, Skytrax is primarily a venting vehicle for ultra-outlier negative situations that every airline stumbles with once in a while.</p>'+
                    '<p>Plus, over half of the reviews are over 6 years old, well before the airline invested in new aircraft in Business Class amenities.</p>'+
                    '<p>We don\'t put any credence in such a tiny sample size and neither should you.</p>'+
                    '<p>BAD BLOGGER REVIEWS</p>'+
                    '<ul style="padding-left:0">'+
                    '<li style="list-style-position: inside;">Irrelevant comparisons: <br />Airlines like Singapore and Cathay Pacific are considered the \'gold\' standard Business Class cabins and service, but these airlines don\'t even service USA-Africa routes, so its a moot comparison.</li>'+
                    '<li style="list-style-position: inside;">Expensive comparisons <br />Airlines like Emirates that fly to Africa, have taxes and fuel surcharges exceeding $1600 per roundtrip award, as well as award redemptions 30-45% more than EgyptAir. And, only limited airline and credit card partners even have a partnership with Emirates.</li>'+
                    '</ul>'+
                    '<p>OUR PRINCIPALS REVIEWS</p>'+
                    '<p>Gary and Steve have personally flown on EgyptAir, and as BookYourAward company owners, they will only recommend airlines to clients like you, that they are willing to book for their friends and family. The airline has invested in updated aircraft like the 787.</p>'+
                    '<p>OUR CUSTOMERS UNANIMOUS REVIEWS</p>'+
                    '<p>This is the ultimate indicator. Like you, a good chunk of our clients are initially hesitant about EgyptAir and yet, they choose to trust our reassurance about a suitable in-flight experience. After 8 years and many hundreds of EgyptAir bookings, we have had a grand total of <strong>zero</strong> client complaints. Surely, we couldn\'t maintain our company reputation, if we had hordes of clients spreading negative reactions.</p>'+
                    '<p>Thanks for hearing us out and hope we have endeavored to provide you helpful context and await your guidance to proceed together.</p>'
            },
            {
                title: 'ITINERARY',
                image: 'objections.png',
                description: '',
                html:
                    '<p>Dear:</p>'+
                    '<p>We recognize that you have diligently saved up to redeem for the \'front-of-the-curtain\' international Business Class experience. And the airlines and credit card companies have done a wonderful job stoking your expectations when you were spending money on airfares and credit card purchases to accumulate your points.</p>'+
                    '<p>Yet, instead of easily redeeming your hoped-for nonstop or streamlined 1 stop award routing directly with your airline program or credit company, you ended up on the door step of BookYourAward.</p>'+
                    '<p>We have the unenviable, but necessary task of sharing the realities of the mileage game that the others have chosen not to disclose to you. Which puts us in the \'kill the messenger\' position- the ideal routing you desire requires redeeming \'Peak\' awards, costing 275-450% more miles. To redeem the low cost \'Saver\' awards requires the tradeoff of \'next-best\'/ less streamlined routing. Why? Because the airlines won\'t release popular (streamlined!) award space at the \'Saver\' level that they are confident they can sell as a revenue ticket.</p>'+
                    '<p>The knee-jerk reactions of most clients is to play chicken and wait out the airlines to release the more desired awards. We used to suggest that strategy, but it has become increasingly risky:</p>'+
                    '<ul style="padding-left:0">'+
                    '<li style="list-style-position: inside;">if you waited too long and no award space was released, the paid airfares zoomed upwards significantly in the interim</li>'+
                    '<li style="list-style-position: inside;">more than 90% of the time, waiting resulted in an inferior award or sometimes, no award being released in the interim.</li>'+
                    '</ul>'+
                    '<p>Only you can be the final arbiter of what\'s more important- saving time or miles. Saving <strong>both</strong> is an aspiration that we share with you, but we owe you the candor that it is extremely unlikely.</p>'+
                    '<p>If you decide, conserving miles is your priority, please let us know so we can jumpstart your award to our booking queue. Otherwise, we wish we had more encouraging news, but we will welcome you back for future award requests.</p>'+
                    '<p>Cheers, <br />Team BYA</p>'
            },
            {
                title: 'AIRLINE',
                image: 'objections.png',
                description: '',
                html:
                    '<p>In the past several years, the airline industry has enjoyed an unprecedented influx of new aircraft like the A380, A350 and Boeing 787, as well as renovating premium-class cabins with lie-flat seats. As a result, many of the negative preconceived notions about a given airline (and their reputation) have likely changed quite a bit.</p>'+
                    '<p>_____ is a great example of this. Our company principals have personally flown and vetted this airline they both agree that ____ meets all criteria for us to recommend to our clients. We have booked awards on this airline for hundreds of other clients and we are pleased to report overwhelmingly positive feedback. This airline has clearly stepped up its game and we hope that we’ve provided you the tangible reassurance to merit moving forward with your booking.</p>'+
                    '<p>Thank you, <br />BookYourAward Team</p>'
            },
            {
                title: 'Bad 3rd Party Reviews',
                image:  'objections.png',
                description: '',
                html:
                '<p>Gary and Steve personally fly and vet every airline that we recommend to folks like you. As well, we have booked hundreds of awards with this airline over the last three years with nary a negative feedback.</p>'+
                '<p>Third party "review" sites are not even remotely statistically significant. Plus, many reviewers make unrealistic comparisons between airlines.</p>'+
                '<p>These sites INHERENTLY skew towards those more apt to complain who are very motivated vs. those who have a satisfting experience and are typically not motivated to "vent" for a positive experience to share on a third party site.</p>'+
                '<p>Your call about assessing our first hand analysis versus the anonymity of a third party site. <br /><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://boardingarea.com/viewfromthewing/2014/05/22/forget-first-class-business-class-want-even-better-think/">http://boardingarea.com/viewfromthewing/2014/05/22/forget-first-class-business-class-want-even-better-think/</a></p>'
            },
            {
                title: 'HAWAII',
                image: 'objections.png',
                description: '',
                html: 
                    '<p>Award space to Hawaii in the "Saver" low level amount is very rarely released, as the Business Class cabins are small and leisure travelers often purchase high Economy airfares to then use mileage to upgrade, so precious little if any for outright mileage awards. We are happy to do an ongoing bi-weekly search but we owe you the candor of knowing that we find suitable Hawaii awards less than 4% of the time and suggest you hang on to your mileage resources for a future trip where the odds are more in your favor. Please let us know if you concur.</p>'
            },
            {
                title: 'EUROPE',
                image: 'objections.png',
                description: '',
                html: 
                    '<p>We are excited to get your award request to Europe underway using the one world alliance. Based on similar requests we’ve booked over the past 10 years, we expect the MOST LIKELY award flights to be as follows:</p>'+
                    '<p><strong>Number of Connections</strong></p>'+
                    '<p>Popular routings sell out to customers paying full-fare, so adding a connection allows us to confirm business class award flights.</p>'+
                    '<ul>'+
                    '<li>75% chance you’ll make 1 connection</li>'+
                    '<li>15% chance you’ll get a nonstop flight</li>'+
                    '<li>10% chance you’ll make 2 connections</li>'+
                    '</ul>'+
                    '<p><strong>Airlines Flown</strong></p>'+
                    '<p>All airlines we book and recommend are high quality, reputable airlines that our company members have personally flown and vetted, as well as receiving positive feedback from other clients like yourself.</p>'+
                    '<ul>'+
                    '<li>85% chance you’ll fly on British Airways</li>'+
                    '<li>10% chance you’ll fly on Iberia Airlines</li>'+
                    '<li>5% chance you’ll fly on American Airlines or Finnair</li>'+
                    '</ul>'+
                    '<p><strong>Taxes and fees</strong></p>'+
                    '<p>Award tickets include government-mandated taxes and security fees, and some airlines also collect fuel surcharges. Your expected costs:</p>'+
                    '<ul>'+
                    '<li>85% $900-1200 per person</li>'+
                    '<li>10% $200-400 per person</li>'+
                    '<li>5% $100-200 per person</li>'+
                    '</ul>'+
                    '<p><strong>Separately purchased airfare</strong></p>'+
                    '<p>More often than not, there is no award space from your home airport to an international gateway hub. We expect you’ll need to buy flights within the U.S. separately</p>'+
                    '<p>We don’t want you to be surprised by this context or the realities of award travel using oneworld partners to Europe. <strong>If these scenarios do NOT meet your threshold of acceptability, please cancel your upcoming appointment</strong></p>'+
                    '<p>Otherwise, we look forward to your phone appointment.</p>'+
                    '<p>Thanks, <br />BookYourAward Team</p>'
            },
            {
                title: 'AUST/NZ',
                image: 'objections.png',
                description: '',
                html: 
                    '<p>We are excited to get your award request to the South Pacific underway.</p>'+
                    '<p>Based on other requests we\'ve booked to Australia/New Zealand over the past 10 years, we want to share with you the MOST LIKELY parameters of your award flights in advance of your appointment:</p>'+
                    '<p><strong>Routing</strong></p>'+
                    '<p>Popular nonstops from the USA to Australia/New Zealand almost always sell out to paying customers. <br />Because of this, your routing will most likely be</p>'+
                    '<ul>'+
                    '<li>85% chance of routing through Asia</li>'+
                    '<li>12% chance of routing through the Middle East, South America, or Europe</li>'+
                    '<li>2% chance of routing through Hawaii, Tahiti, Fiji</li>'+
                    '<li>1% chance of a nonstop from the west coast to Australia/NZ</li>'+
                    '</ul>'+
                    '<p>Some airlines allow stopovers at major Asian airports (e.g. Hong Kong). If this is of interest to you, please let us know.</p>'+
                    '<p><strong>Airlines Flown</strong></p>'+
                    '<p>All airlines we book and recommend are high quality, reputable airlines that our company members have personally flown and vetted, as well as receiving positive feedback from other clients like yourself.</p>'+
                    '<ul>'+
                    '<li>80% chance you’ll fly on China Eastern, China Southern, Air China, China Airlines, Asiana Airlines, Cathay Pacific</li>'+
                    '<li>17% chance you’ll fly on Eva Airways, Thai Airways, Japan Airlines, Korean Airlines, All Nippon Airways, Singapore Airlines, Qatar Airways, Emirates, Etihad</li>'+
                    '<li>2% chance you’ll fly on LATAM Airlines or any European-based airline, Fiji Airways</li>'+
                    '<li>1% chance you’ll fly on American Airlines, Delta Airlines, United Airlines, Qantas, Virgin Australia, or Air New Zealand, , Air Tahiti Nui, or Hawaiian Airlines</li>'+
                    '</ul>'+
                    '<p>We don’t want you to be surprised by this context or the realities of South Pacific award travel. If these scenarios do NOT meet your threshold of acceptability, please cancel your upcoming appointment and we will welcome you back when helpful.</p>'+
                    '<p>Thanks, <br />BookYourAward Team</p>'
            },
            // {
            //     title: 'Economy Flight Objection',
            //     image: 'objections.png',
            //     description: '',
            //     html: 
            //     '<p>In order to find award space at the low cost ‘Saver’ level, over 90% of our bookings are subject to modest “workarounds.” In your particular case, this includes flying a flight of modest duration in economy.</p>'+
            //     '<p>Many short flights do not offer award seats in business class because:</p>'+
            //     '<ul style="padding-left:0">'+
            //     '<li style="list-style-position: inside;">Most short-haul aircraft are regional jets with no business class seats on board.</li>'+
            //     '<li style="list-style-position: inside;">For those domestic flights that do offer a business class cabin, there are often only 8 business class seats. They are often allocated exclusively for top-tier elite members or for last-minute upgrades to full-fare passengers.</li>'+
            //     '</ul>'+
            //     '<p>We hope you can agree that this modest workaround will be well worth the benefit of saving hundreds of thousands of miles.</p>'+
            //     '<p>Thank you, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'TK/IST Objection',
            //     image:  'objections.png',
            //     description: '',
            //     html:
            //     '<p>The Istanbul airport is currently one of the safest in all of Europe due to the security measures they`ve implemented (in addition to being across from an air force base).  Another advantage to transiting IST is that it almost always a premium experience: great in-flight product with convenient connections, a top-notch airport lounge, no fuel surcharges, and a competitive mileage price compared to other programs.</p>' +
            //     '<p>Steve recently flew Turkish and he his wife and daughter also just recently returned they all had terrific experiences. As well, none of our clients that have flown Turkish in the last few months have expressed any negative feedback about either the IST airport or in flight experience. We trust that provides some tangible and first hand reassurance.  Please let us know how you want to proceed.</p>' +
            //     '<p>Thank you,<br>(Staff Name)</p>'
            // },
            // {
            //    title: 'Taxes/Fuel Surcharges Objection',
            //    image:  'objections.png',
            //    description: '',
            //    html:
            //       '<p>The vast majority of European airlines and Emirates levy \'hefty fuel surcharges\' on top of already high airport taxes and security fees. If there were other award alternatives to mitigate such out of pocket costs, please rest assured that we would have preemptively offered them. Despite these costs, redeeming the low level \'Saver\' award we found you, i still a compelling value proposition- saving you hundreds of thousands of miles.</p>'
            // },
             // {
            //     title: 'Amex Transfer to Air France',
            //     image:  'misc.png',
            //     description: '',
            //     html:
            //         '<p>Air France does not allow third party bookings and requires that the account holder directly redeem awards and prepay taxes. We will assist with your award booking through this straightfoward process:<ul style="padding-left:0"><li style="list-style-position: inside;">Open a new mileage account at: http://www.airfrance.com now (or use an existing account if you have one)</li><li style="list-style-position: inside;">Provide us with the account number and 4 digit pin code</li><li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points: XXXK<br /><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li><li style="list-style-position: inside;">We will secure a courtesy hold on your flights after this phone appointment and provide the 6-digit reservation code for your hold</li><li style="list-style-position: inside;">Once the hold is secure, we will advise your final AmEx transfer instructions and you will have 24 hours to contact Flying Blue at 800-375-8723 to pay taxes and redeem the award</li><li style="list-style-position: inside;">Please post on this site your confirmation that award was indeed booked</li></ul><p>We trust this process is clear and straightforward and look forward to finalizing your booking.</p><p>Much obliged,<br />BookYourAward Team</p>'
            // },
            {
                title: 'DECIDE QUICKLY ALERT',
                image:  'during_appt.png',
                description: '',
                html:
                '<p>Dear:</p>'+
                '<p>We recognize you might need a bit of time to consult with other travelers after our award presentation.</p>'+
                '<p>Please be aware that the quicker you can turnaround a final approval, the better chances that we can secure your award.</p>'+
                '<p>Clients who wait more than 4 hrs typically have a 40% higher risk that the award presented won\'t still be available because of recent <strong>airline-unfriendly policies</strong>:</p>'+
                '<p>- no courtesy holds: awards can be snatched by others in the interim</p>'+
                '<p>- dynamic pricing: award amounts now fluctuate in quite a volatile and random manner</p>'+
                '<p>So, your cooperation with the following will help ensure our ability to book your preferred award:</p>'+
                '<p>1. Post on this thread the specific award(s) that you approve <strong>at your absolute earliest convenience</strong>.</p>'+
                '<p>2. We will provide transfer instructions and request you share account information</p>'+
                '<p>3. Once you confirm the transfer and account sharing are completed, we will expedite your approved award to our booking queue.</p>'+
                '<p>Thanks for being part of the solution that the airlines have confronted us as a problem.</p>'+
                '<p>Cheers, <br />Team BYA</p>'
            },
            {
                title: 'False Positives/Don\'t Transfer Yet Alert',
                image:  'during_appt.png',
                description: '',
                html:
                '<p>Dear:</p>'+
                '<p>One of the most frustrating but unsolvable issues with XXX airline program is the problem of \'false positive award space\' or \'phantom space\'.</p>'+
                '<p>This scenario happens when award space is displayed by the award search engine but is unbookable once we get your approval and are 16 steps of the way through the 17 step booking process. The airlines update revenue seat inventory in real time, but to save money, some airlines allow award seat inventory to update with 24-72 hours of LAG TIME.</p>'+
                '<p>We want to protect you from doing a speculative mileage transfer and not being able to book the award. So, upon your approval of the award presented, our booking staff will execute a \'dummy\' booking to ensure award space is actually available. If so, we will rely on your cooperation to try and <strong>finalize your booking as close to real time as possible:</strong></p>'+
                '<p>1. We will contact you to confirm award space</p>'+
                '<p>2. We will provide transfer instructions and request account information</p>'+
                '<p>3. Please make the transfer and account sharing at your earliest convenience, and we will expedite to our booking queue.</p>'+
                '<p>On the off chance that award space is not available, we will remain vigilant on your behalf, AND you wont have any points stuck in an unwanted program.</p>'+
                '<p>Thanks for being part of the solution that the airlines have confronted us as a problem.</p>'+
                '<p>Team BYA</p>'
            },
            {
               title: 'Delayed Transfer Alert',
               image:  'during_appt.png',
               description: '',
               html:
                   '<p>Dear:</p>'+
                   '<p>Our mission is to make the award booking process as simple and streamlined as possible for you, as we recognize and respect that you have competing demands on your time.</p>'+
                   '<p>However, (card/hotel-->airline) normally have a lag time of posting until (x days).</p>'+
                   '<p>These unavoidable delays which are <strong>caused by customer unfriendly airline policies</strong>, can occasionally impact the award we just presented you as follows:</p>'+
                   '<p>- award can disappear: due to no courtesy holds allowed)</p>'+
                   '<p>or</p>'+
                   '<p>- award can have increased redemption amounts: due to new \'dynamic pricing\').</p>'+
                   '<p>Rest assured that if award space changes in the interim, w will stay vigilant on your behalf as we find alternate award space 98% of the time.</p>'+
                   '<p>Please keep an eagle eye on your airline account and alert us as soon as the transfer posts, so that we can expedite your award to the booking queue to maximize our chances of finalizing it.</p>'+
                   '<p>We appreciate your cooperation in being part of the solution to the unfortunate problem created by the airlines!</p>'+
                   '<p>Cheers, <br />Team BYA</p>'
            },
            {
                title: 'Only One Way Available Proposal',
                image:  'during_appt.png',
                description: '',
                html:
                '<p>Right now, only one direction of your award flight is available. Your specific flight details for the available segments are shown below.</p>'+
                '<p>Because award space is so volatile, we recommend you book this one-way flight. Waiting for both directions of travel to be released simultaneously risks losing this attractive one-way flight in the interim.</p>'+
                '<p>We will continue to monitor award space for the other half of your trip. There is no additional miles, taxes, or service fees to book your reservation as two separate one-ways. We use this booking strategy for about a third of our clients and it is successful in ultimately securing round-trip business class flights over 97% of the time.</p>'+
                '<p>The following awards are available for your consideration.</p>'
            },
            {
                title: 'Client Approval - Booking Queue',
                image:  'during_appt.png',
                description: '',
                html:
                '<p>Thanks for your approval and sharing the requested account information with our secure site;<br>' +
                'We will expedite your award to the booking queue to redeem award and prepay taxes and keep you posted of our progress.</p>'
            },
            // {
            //     title: 'Forced Overnight',
            //     image: 'during_appt.png',
            //     description: '',
            //     html:
            //     '<p>Some airline routing schedules have intrinsic forced overnight layovers because the first connection arrives too late to continue onward on the same day.  Your particular routing fits this scenario, and there is no workaround to avoid this layover.</p>' +
            //     '<p>You will be responsible for booking your own lodging, of which there are typically 3 options.</p>' +
            //     '<ul style="padding-left:0">' +
            //     '<li style="list-style-position: inside;">some large airports have hotels within the secure area of the airport, so are super convenient... and usually a bit pricey</li><li style="list-style-position: inside;">most airports have hotels beyond the security area, but attached to the airport, again quite convenient and usually a bit pricey.</li><li style="list-style-position: inside;">all airports have a number of hotel options in terms of quality, price and distance from airport</li>' +
            //     '</ul>' +
            //     '<p>All of these options are easily accessible to book at <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.booking.com">www.booking.com</a></p>' +
            //     '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            {
               title: 'PM Shift Real Time Search/Book',
               image:  'after_appt.png',
               description: '',
               html:
                   '<p>Dear:</p>'+
                   '<p>Our mission is to make the award booking process as simple and streamlined as possible for you, as we recognize that you have competing demands on your time.</p>'+
                   '<p>However, in the last few months, there has been a huge increase in the number of clients who have made timely credit card/hotel transfers in good faith, yet award space had disappeared (due to no courtesy holds allowed) or had increased redemption amounts (due to new \'dynamic pricing\').</p>'+
                   '<p>To protect against speculative transfers, we need to solicit your cooperation with the following straightforward process:</p>'+
                   '<p>1. Please make final decision about approving award presented by (whatever is 1.5 hrs before that daily PM shift starts) and post on this online thread.</p>'+
                   '<p>2. Please return to this online thread anytime between (PM shift daily window):</p>'+
                   '<p>- our booking staff will do a real time search to ensure your award is still available and at the presented routing and price. </p>'+
                   '<p>- if so, you will be provided simple instructions to do a <strong>real time</strong> transfer to the designated airline program. And then, we will do a <strong>real time</strong> booking.</p>'+
                   '<p>3. If you are unable to accommodate our designated booking window, you can try again the following morning between 9-10a Eastern time and hopefully the award will still be there.</p>'+
                   '<p>We wish we could avoid having to drag you into this new part of the booking process, but the airlines have forced our (and your!) hand with their convoluted and customer unfriendly policies.</p>'+
                   '<p>Thank you for being part of the solution here!</p>'+
                   '<p>Cheers, <br />Team BYA</p>'
            },
            // {
            //    title: 'RESPONSE TIME DEADLINE',
            //    image:  'after_appt.png',
            //    description: '',
            //    html:
            //        '<p>Despite the urgency to book awards during the phone call, because of volatile award space and lack of courtesy holds, we understand you need a bit more time. As agreed upon during our conversation, you will do your necessary due diligence to finalize approval of the proposed award and report back to this thread by <strong>(day/time)</strong>.</p>'+
            //        '<p>If we don\'t hear back from you in that mutually agreed upon window, then we will assume you have declined to move forward, and will welcome you back when helpful.</p>'+
            //        '<p>Thanks <br />Team BYA <br />Steve, Ceca, Ricardo, Andrew</p>'
            // },
            {
                title: 'AWARD SPACE CHANGE AFTER APPROVAL',
                image:  'after_appt.png',
                description: '',
                html:
                '<p>Thank you for your approval of the award previously proposed online or during our phone session. Despite timeliness in contacting the airline to book your award, inventory changed in the brief interval between our proposal, your approval, and us contacting the airline. We are dependent on reaching most airlines by phone and wait times are unpredictable from circumstances like severe weather, air traffic control, or mechanical issues impacting flying passengers. As well, most airlines don’t allow courtesy holds to protect the award space that we find.</p>' +
                '<p>So, award space will hopefully be refreshed to our advantage in the next 3-4 days, and we hope to have good news by that time. We appreciate your patience and understanding about this issue that unfortunately is beyond our control.</p>' +
                '<p>More soon.</p><p>Cheers,<br />BookYourAward Team</p>'
            },
            // {
            //    title: 'POST APPT',
            //    image:  'after_appt.png',
            //    description: '',
            //    html:
            //        '<p>Despite the urgency to book awards during the phone call, because of volatile award space and lack of courtesy holds, we understand you need a bit more time. As agreed upon during our conversation, you will do your necessary due diligence to finalize approval of the proposed award and report back to this thread by <strong>(day/time)</strong>.</p>'+
            //        '<p>We have endeavored to accomplish what you tasked us to do- finding a suitable award that you agreed met your parameters. We appreciate your reciprocity of quick turnaround time to secure your award.</p>'+
            //        '<p>Thanks <br />Team BYA <br />Steve, Ceca, Ricardo, Andrew</p>'
            // },
            // {
            //     title: 'POST PHONE APPT',
            //     image: 'after_appt.png',
            //     description: '',
            //     html:
            //     '<p style="">I\`m pleased that our phone session together allowed us to establish your trust and confidence to proceed with your award booking. I think we are both clear on the path forward to finalize your award, so at this point, we will communicate with you on an ongoing basis via this secure online site, which will ensure your account privacy and security and allow our whole team to contribute and stay apprised of our progress.</p><p style="">OPTION 1. AWARD APPROVAL</p><p style="">We will await your hoped-for approval of the award option(s) discussed. As award space is incredibly volatile and most airlines allow no courtesy holds, your initiative in green lighting our expediting your award to the booking queue, will ensure that we can secure what we have proposed. &nbsp;</p><p style="">To finalize,&nbsp;<b>at your earliest convenience</b>, simply copy and paste the award option you prefer into your next post. Then, we will provide the mileage redemption process and where appropriate, the credit card transfer and/or mileage/credit card purchase instructions.</p><p style=""><br></p><p style="">OPTION 2. PREFER TO WAIT, BUT DON\'T</p><p style="">While we aspire to meet your ideal itinerary preferences, award availability often requires modest trade-offs regarding modestly less streamlined routing, Economy Class for short domestic segments and/or separate purchases of modest priced domestic airfares when no award space. The overwhelming advantage is that such trade-offs are balanced off by our finding low level "Saver" award space that saves hundred of thousands of miles.</p><p style="">Based on our experience with similar situations, we find that waiting for 1-2 weeks:<br></p><p style="">*&nbsp;<b>only 5% of the time are we able to find “better” award space/routings</b>&nbsp;</p><p style=""><b>* 35% of the time we find exactly the same award space as originally proposed</b></p><p style="">*&nbsp;<b>50% of the time clients end up with less favorable routings when we&nbsp;search&nbsp; several weeks (or months) after our original proposal</b></p><p style="">*&nbsp;<b>10% of the time, we are unable to find any award space&nbsp;</b></p><p style="">So, our suggestion is to book what we we\`ve proposed now. This will provide you the certainty and peace of mind of having a confirmed award locked in. Then, we welcome you to circle back one week prior to your departure date for one complimentary award search, when last minute award space might be released to improve your itinerary.</p><p style=""><br></p><p style="">OPTION 3. PREFER TO WAIT</p><p style="">Choosing to wait for something better is certainly your prerogative, and you are welcome to have us re-engage our search with the clear understanding of the consequences of waiting as summarized above.</p><p style="">Currently, no award space is available for your itinerary.&nbsp; We are maintaining an ongoing customized search on your behalf spanning over 60 routing options and 12 airline partners.&nbsp; We will contact you:<b>&nbsp;</b><br></p><p style="">* as soon as award space opens up&nbsp;</p><p style="">or</p><p style="">* once two weeks elapses with no award space released, we will contact you to reassure you that we are indeed remaining vigilant on your behalf.</p><p style="">We await your guidance how best to proceed on your behalf.<br></p><p>Best,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Ongoing Contact',
            //     image: 'after_appt.png',
            //     description: '',
            //     html:
            //     '<p>I`m pleased that our phone session together allowed us to establish your trust and confidence to proceed with your award booking.  I think we are both clear on the path forward to finalize your award, so at this point, we will communicate with you on an ongoing basis via this secure online site, which will ensure your account privacy and security and allow our whole team to contribute and stay apprised of our progress.</p>' +
            //     '<p>We expect to get back in touch with you on _________/</p>' +
            //     '<p>Best,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Follow Up As No Client Response',
            //     image:  'after_appt.png',
            //     description: '',
            //     html:
            //     '<p>Dear :</p>' +
            //     '<p>Our advice and counsel is straightforward regarding continuing to wait for the possibility of a more streamlined award space to be released - Don\'t wait!  You run the dual risk of not finding anything better and very likely losing the proposed award routing in the interim... leaving you with inferior and possibly even no options. Instead, book now and enjoy the <b>certainty</b> of a confirmed in advance premium class award.</p>' +
            //     '<p>Look forward,<br>(Staff Name)</p>'
            // },
            // {
            //    title: '4pm Response',
            //    image:  'after_appt.png',
            //    description: '',
            //    html:
            //        '<p>Dear XXXX,</p>'+
            //        '<p>We hope you’ve had a chance to review the flight information we sent over earlier. Award space changes quickly, which means it’s imperative to look over these flights and let us know if you’d like to proceed to the next steps while the award flights are still available.</p>'+
            //        '<p>After reviewing your flight options, please respond by 4:00 pm EST today with an update on how you wish to proceed. If we don’t hear from you, we’ll assume you’re not quite ready to commit to flights yet and close this request (you can re-open it in the future when you’re ready to book).</p>'+
            //        '<p>We look forward to the challenge of earning your confidence and business. <br />The BYA Crew (Steve, Ceca, Ricardo, Andrew and Irina)</p>'
            // },
            // {
            //    title: 'FALSE POSITIVES',
            //    image:  'after_appt.png',
            //    description: '',
            //    html:
            //        '<p>One of the most frustrating but unsolvable issues with XXX airline program is the problem of \'false positive award space\' or \'phantom space\'. This scenario happens when award space is displayed by the award search engine but is unbookable once we get your approval and are 16 steps of the way through the 17 step booking process.</p>'+
            //        '<p>The airlines update revenue seat inventory in real time, but to save money, airlines allow award seat inventory to update with 24-72 hours of LAG TIME.</p>'+
            //        '<p>Our company (and no award booking company) does not have enough time and staff to do \'dummy\' bookings to ensure the award space we present is or isn\'t false positive. Nor can we expect that the airlines will remedy the \'false positives\' by investing hard dollars into the software.</p>'+
            //        '<p>So, while you AND our company are the unintended victims of this lousy situation, please rest assured that we find suitable award space with the miles that you ave already transferred over 98.5% of the time. So, your patience will be appreciated as we stay vigilant on your behalf and will keep you posted with ongoing search efforts and progress.</p>'+
            //        '<p>Thanks for hearing us out.</p>'+
            //        '<p>Cheers <br />Team BYA</p>'
            // },
            {
                title: 'ACCOUNT ACCESS ERROR',
                image: 'after_appt.png',
                description: '',
                html: '<p>Our system is returning an error with the XXX information you provided.  This typically just means there was a typo in either the XXX account number and/or password. Please double-check that both were entered correctly and update as necessary.  After you`ve double-checked, you can confirm it`s correct by clicking the "Auto-login" link below."</p>' +
                '<p>Alternatively you can type the account number and password in this message thread as text and we`ll set it up manually.</p>' +
                '<p>Thanks,<br />The BookYourAward Team</p>'
            },
            // {
            //    title: 'Award Space Changed Complaint',
            //    image:  'after_appt.png',
            //    description: '',
            //    html:
            //        '<p>Hi XXXXX-</p>'+
            //        '<p>The template says it all, I’m afraid.</p>'+
            //        '<p>We found the award.</p>'+
            //        '<p>You approved it.</p>'+
            //        '<p>We tried to book it, but it disappeared in the interim.</p>'+
            //        '<p>We share in your disappointment but don’t control the inventory. We can only make best efforts to move with alacrity, which we did. That said, in this situation, the path forward is clearly articulated below.</p>'+
            //        '<p><State next steps></p>'+
            //        '<p>Look forward, <br />Team BYA</p>'
            // },
            {
                title: 'Friday Hours',
                image: 'admin.png',
                description: '',
                html: '<p>Dear</p>' +
                '<p>We hope you have reviewed the options presented for your itinerary.</p>'+
                '<p>If you have any questions or updates for our team please post them on this thread so we can ensure you have the necessary information to make the best decision OR we can move forward with finalizing your booking.</p>'+
                '<p>Our offices are open through <strong>6:00pm EST on Fridays</strong> and we have <strong>very</strong> limited weekend hours.</p>'+
                '<p>We look forward to hearing from you in the next couple hours.</p>'+
                '<p>The BYA Team</p>'
            },
            {
                title: 'POST PAYMENT THANK YOU',
                image: 'admin.png',
                description: '',
                html: '<p>Dear</p>' +
                '<p>While BookYourAward is known for our expertise in redeeming mileage awards (we recently crested over 3 BILLION miles), we also figured you might appreciate being privy to some of our mileage earning strategies.</p>'+
                '<p>There are some terrific credit card promotions that offer tens of thousands of bonus miles for a modest amount of qualified spending. To maximize your mileage earning potential, consider signing up for both a personal and business card for each member of your household. We have summarized the best offers below to replenish your mileage balances and urge you to sign up soon as most of these offers are time-sensitive.</p>'+
                '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/blog/link/ccbya">https://awardwallet.com/blog/link/ccbya</a></p>'+
                '<p>We look forward to the challenge of earning your future confidence and business..</p>'+
                '<p>Cheers <br />Team BookYourAward</p>'+
                '<p style="border-top: 1px solid #b3b3b3; padding-top: 5px"><strong>P.S.</strong> If a 7 day team travel competition featuring a mystery itinerary of Europe accomplishing surprise challenges (creativity and resourcefulness, not speed or fitness) in pursuit of up to $3000 cash prize sounds exciting, check out our other company <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.competitours.com">www.competitours.com</a>. Enjoy a trip packed with rivalry by day and revelry by night!</p>'
            },
            {
                title: 'CANCELLATION',
                image: 'admin.png',
                description: '',
                html: '<p>Dear [Name],</p>'+
                      '<p>We charge a cancellation fee of $85 per person for cancellations, as most airlines require re-engagement via phone to reinstate miles and many times it takes hours to complete. If you agree, then we\'re happy to move forward with your cancellation and confirm it within 2-5 business days. Otherwise, you may contact the airline(s) yourself to process the cancellation and let us know once completed. Whether we process the cancellation on your behalf or you do it yourself, we\'ll cut you a check for the airline taxes refund amount, provided the refund is debited back to our corporate account. So, please provide us with a mailing address to send it to.</p>'+
                      '<p>Additionally, please be aware that airlines charge a miles redeposit/cancellation fee that varies from program to program. You may refer to this article for more information: <br /><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/blog/airline-award-ticket-change-and-cancellation-policies/" target="_blank">https://awardwallet.com/blog/airline-award-ticket-change-and-cancellation-policies/</a></p>'+
                      '<p>Regards, <br />BookYourAward Team</p>'
            },
            {
                title: 'NO UPGRADES',
                image:  'admin.png',
                description: '',
                html:
                '<p>Our company only handles outright mileage awards, you will need to contact airline directly if you wish to upgrade from an Economy fare. We believe you will be far better served with an outright award vs an upgrade as noted below.</p>' +
                '<a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/blog/upgrade-airline-tickets-miles/">https://awardwallet.com/blog/upgrade-airline-tickets-miles/</a>'
            },
            {
                title: 'MONITORING',
                image: 'admin.png',
                description: '',
                html: '<p>Dear XXX:</p>'+
                      '<p>We’re happy to get back to work for you, and hopefully find an even better-fitting itinerary than what we already booked. However, since this requires ongoing labor and monitoring, there is a fee associated.</p>'+
                      '<p>If you are interested in setting up monitoring with us, the fee is $85 per person. Alternatively, you may simply respond to this thread within one week of departure and we will do a single, complimentary search for more streamlined flights (last minute is when we most often see the “best” or “ideal” flights).</p>'+
                      '<p>Regards, <br />BookYourAward Team</p>'
            },
            {
                title: 'Office Closed',
                image:  'admin.png',
                description: '',
                html:
                    '<p>We have endeavored to stay in good touch with you during business hours. We look forward to jumpstarting our efforts on your behalf tomorrow (or if its the weekend, on Monday!) We appreciate your patience and look forward to earning your confidence and business.</p>'
            },
            // {
            //     title: 'Flight Share Reminders',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>TRANSFER LIABILITY</p>'+
            //           '<p>Transfers may post to account on a delayed basis despite immediate confirmation, impacting award availability in the interim. While we will very likely find alternate award space once transfer is complete, our company has no liability for delayed transfers or loss of specific award proposals.</p>'+
            //           '<p>BOOKING FEE</p>'+
            //           '<p>$199 per person, upon booking round-trip award</p>'+
            //           '<p>Your award advisor will answer any questions about the above proposal during your phone session.</p>'+
            //           '<p>After approval, next steps:</p>'+
            //           '<ol style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">You may need to complete a credit card transfer, to convert your credit card miles into airline miles. We will send specific instructions on how to complete this (it typically takes less than 5 minutes of your time).</li>'+
            //           '<li style="list-style-position: inside;">We will send you an electronic authorization form for you to securely share your account number and password, and grant permission for us to book your award.</li>'+
            //           '<li style="list-style-position: inside;">We will contact the airline(s) to book your award. All taxes are prepaid on your behalf and included with our service fee on your final invoice once the reservation is complete.</li>'+
            //           '</ol>'+
            //           '<p>Sincerely, <br />BookYourAward Team (Steve, Ceca, Ricardo, Andrew, and Irina)</p>'
            // },
            {
                title: 'International Infant Tickets',
                image: 'admin.png',
                description: '',
                html: '<p>Complete policies for flying with an infant in arms (or “lap child”) vary by airline, but there are a few guidelines you should expect:</p>'+
                      '<ul style="padding-left:0">'+
                      '<li style="list-style-position: inside;">Traveling as an infant in arms is only allowed if the child will be under the age of 24 months at the time of travel.</li>'+
                      '<li style="list-style-position: inside;">All travelers, including infants, require a ticket issued in advance of travel for international itineraries.</li>'+
                      '<li style="list-style-position: inside;">Most infant tickets are priced at 10% of the adult’s fare, so if your mileage ticket would have otherwise cost $3,000, an infant ticket would cost $300.</li>'+
                      '<li style="list-style-position: inside;">Infant tickets are processed separately through a different department than award tickets; you can call directly to add an infant to your reservation after the award is finalized.</li>'+
                      '</ul>'
            },
            {
                title: 'Technical Support for request',
                image: 'admin.png',
                description: '',
                html: '<p>If you would like technical assistance on accessing your booking request, you may submit a help request using the form at the bottom of this page: <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/contact">https://awardwallet.com/contact</a></p>'+
                      '<p>My best guess is that you have two different AwardWallet accounts and that you are currently signed into the wrong one. By signing out of AwardWallet and then signing in again with the other username, you should have no issues getting to the online booking thread.</p>'
            },
            {
                title: 'BA seating info',
                image: 'admin.png',
                description: '',
                html: '<p>BA Seating - \'Please be advised that BA charges a $100 per person advance seat assignment fee, which can be avoided by waiting until 24 hr prior to departure.\'</p>'+
                      '<p>BA will assign you two seats together since you are traveling on the same reservation, you just won\'t be able to select the exact row or left/right hand side until check-in.</p>'
            },
            {
                title: 'Past Due',
                image: 'admin.png',
                description: '',
                html: '<p>Our records indicate that we have not received payment for our services and the prepaid taxes for your itinerary.</p>'+
                      '<p>If you have mailed the payment please indicate the check number and/or if it has cleared your account so we can update our records accordingly.</p>'+
                      '<p>You can also make payment via credit card by accessing the invoice listed above.</p>'+
                      '<p>Thank you for your prompt attention. <br />BookYourAward Team</p>'
            },
            {
                title: 'Payment Thank You',
                image: 'admin.png',
                description: '',
                html: '<p>Dear XXXX,</p>' +
                '<p>Thank you for your prompt payment.</p>'+
                '<p>While BookYourAward is known for our expertise in redeeming mileage awards (we recently crested over 3 BILLION miles), we also figured you might appreciate being privy to some of our mileage earning strategies.</p>'+
                '<p>There are some terrific credit card promotions that offer tens of thousands of bonus miles for a modest amount of qualified spending. To maximize your mileage earning potential, consider signing up for both a personal and business card for each member of your household. We have summarized the best offers below to replenish your mileage balances and urge you to sign up soon as most of these offers are time-sensitive.</p>'+
                '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://awardwallet.com/blog/link/ccbya">https://awardwallet.com/blog/link/ccbya</a></p>'+
                '<p>We look forward to the challenge of earning your future confidence and business..</p>'+
                '<p>Cheers <br />BookYourAward Team</p>'+
                '<p style="border-top: 1px solid #b3b3b3; padding-top: 5px"><strong>P.S.</strong> If a 7 day team travel competition featuring a mystery itinerary of Europe accomplishing surprise challenges (creativity and resourcefulness, not speed or fitness) in pursuit of up to $3000 cash prize sounds exciting, check out our other company <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.competitours.com">www.competitours.com</a>. Enjoy a trip packed with rivalry by day and revelry by night!</p>'
            },
            // {
            //     title: 'After Proposal Appt Req Response',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear XXX:</p>'+
            //           '<p>You are welcome to select an appointment time with from the calendar below. However, we must make you aware that waiting for an appointment after today may result in the loss of the currently available award space. For this reason, we strongly suggest that we communicate now through this thread, as we do with the vast majority of our clients.</p>'+
            //           '<p>Our team member rotation allows for timely response to your questions and concerns.</p>'+
            //           '<p>Look forward, <br />BookYourAward Team</p>'
            // },
            {
                title: 'Unused Transferred Points complaint',
                image: 'admin.png',
                description: '',
                html: '<p>Dear XXXXX,</p>'+
                      '<p>These situations of transfers not being bookable happen extremely rarely, but they do happen which is why we endeavored to clearly articulate our position on this issue previously on this thread.</p>'+
                      '<p><strong>TRANSFER LIMITATION OF LIABILITY</strong></p>'+
                      '<p>Mileage transfers from credit cards and hotel programs often take a few days to process, even if you get an immediate confirmation note. This delay may impact award availability in the interim, but happens less than 3% of the time. If that happens, we will search for alternate award options to use your newly transferred miles. Our company has no liability for delayed transfers, loss of proposed award space, or for credit card policies that do not allow transfers to be rescinded.</p>'+
                      '<p>XXXXX program points are quite versatile:</p>'+
                      '<ul>'+
                      '<li>Delta domestic and international</li>'+
                      '<li>Air France/KLM worldwide</li>'+
                      '<li>Air Europa/Aeroflot to Europe</li>'+
                      '<li>China Eastern/China Southern/China Air/Korean Air to Asia</li>'+
                      '<li>Saudia to the Middle East</li>'+
                      '</ul>'+
                      '<p>We are pleased to offer 50% off your next booking.</p>'+
                      '<p>Thank you, <br /> BYA</p>'
            },
            // {
            //     title: 'Award Booked Confirmation',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>We have completed the XXXXbound portion of your award booking and prepaid for airline taxes for your convenience. The flight information is included below and you should also be receiving an e-ticket via e-mail directly from the airlines within the next 48 hours.</p>' +
            //     '<p>Please thoroughly review your reservation to ensure that it\'s been finalized correctly.</p>'+
            //     '<p><strong>Total Cost</strong></p>'+
            //     '<p>XXX <Program Name> miles</p>'+
            //     '<p>$XXX airline taxes (prepaid by BookYourAward)</p>'+
            //     '<p>Regards,<br /><Staff Name></p>'
            // },
            // {
            //     title: 'Likelihood: Europe H/H',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections:<br /> 75% chance you’ll make 1 connection<br /> 15% chance you’ll get a non-stop flight<br /> 10% chance you’ll make 2 connections</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one connection can be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown:<br /> 75% chance you’ll fly on British Air, Air France, KLM, Lufthansa, Austrian, Turkish and/or Brussels Airlines<br /> 15% chance you’ll fly on Iberia, TAP Portugal, Air Canada and/or LOT Polish<br /> 10% chance you’ll fly on Swiss, Scandinavian, FinnAir and/or Aer Lingus</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these airlines.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks BookYourAward Team</p>'
            // },
            // {
            //     title: 'ASIA: Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections:<br /> 80% chance you’ll make 1 connection<br /> 15% chance you’ll get a non-stop flight<br /> 5% chance you’ll make 2 connections</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown:<br /> 65% chance you’ll fly on Turkish Airlines, Air France, KLM, China Eastern, China Southern, Air China, Asiana and/or United<br /> 30% chance you’ll fly on All Nippon (“ANA”), Korean, Japan Airlines, Cathay Pacific, Lufthansa, EVA Air, China Air and/or Air Canada<br /> 5% chance you’ll fly on Singapore, Qatar, Etihad, Swiss, British Air, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'AUSTRALIA/NZ: Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections:<br /> 65% chance you’ll make 2 connections<br /> 33% chance you’ll make 1 connections<br /> 2% chance you’ll get a non-stop flight</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown:<br /> 70% chance you’ll fly on China Eastern, China Southern, Air China and/or Asiana<br /> 20% chance you’ll fly on All Nippon (“ANA”), Japan Airlines, Korean, Qatar, Etihad, Singapore and/or EVA Air<br /> 9% chance you’ll fly on Virgin Australia, Cathay Pacific, United, Emirates and/or Air Canada<br /> 1% chance you’ll fly on QANTAS, Air New Zealand, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'AFRICA/INDIA/MIDEAST Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows…</p> <p>Number of Connections:<br /> 50% chance you’ll make 2 connection<br /> 49% chance you’ll make 1 connection<br /> 1% chance you’ll get a non-stop flight</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown<br /> 65% chance you’ll fly on Ethiopian, Turkish, Air France and/or EgyptAir<br /> 25% chance you’ll fly on Etihad, Qatar, Emirates, KLM and/or Kenya Air<br /> 8% chance you’ll fly on Iberia and/or British Air<br /> 2% chance you’ll fly on South African, Lufthansa, Swiss, Cathay Pacific and/or Virgin Atlantic</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks BookYourAward Team</p>'
            // },
            // {
            //     title: 'S. AMERICA hub/hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 50% chance you’ll make 1 connection<br /> 45% chance you’ll make 2 connections<br /> 5% chance you’ll get a direct flight</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown<br /> 85% chance you’ll fly on LATAM, Avianca, Copa and/or Aeromexico<br /> 15% chance you’ll fly on United, American, Delta and/or Air Canada</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'EUROPE: Non-Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 65% chance you’ll make 2 connections<br /> 33% chance you’ll make 1 connection<br /> 2% chance you’ll  get a direct flight</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one connection can be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown<br /> 75% chance you’ll fly on British Air, Air France, KLM, Lufthansa, Austrian and/or Turkish<br /> 15% chance you’ll fly on Iberia, Virgin Atlantic, TAP Portugal and/or LOT Polish<br /> 10% chance you’ll fly on Swiss, Scandinavian, FinnAir and/or Aer Lingus</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p>OPTION 1:<br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p>OPTION 2:<br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'ASIA: Non-Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections:<br /> 65% chance you’ll make 2 connection<br /> 33% chance you’ll make 1 connection<br /> 2% chance you’ll get a direct flight</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown:<br /> 65% chance you’ll fly on Turkish, China Eastern, China Southern, Air China, Asiana, United and/or Air Canada<br /> 30% chance you’ll fly on All Nippon (“ANA”), Korean, Cathay Pacific, Japan Airlines, Air France, KLM, Lufthansa, EVA and/or China Airlines<br /> 5% chance you’ll fly on Singapore, Qatar, Etihad, Swiss, British Air, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'AUSTRALIA/NZ: Non Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 90% chance you’ll make 2 connections<br /> 8% chance you’ll make 3 connections<br /> 2% chance you’ll make 1 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown<br /> 70% chance you’ll fly on China Eastern, China Southern, Air China, Cathay Pacific and/or Asiana<br /> 20% chance you’ll fly on All Nippon (“ANA”), Japan Air, Korean, Emirates, Qatar, Etihad and/or Singapore<br /> 9% chance you’ll fly on Virgin Australia, United and/or Air Canada<br /> 1% chance you’ll fly on QANTAS, Air New Zealand, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'AFRICA/INDIA/MIDEAST Non-Hub/Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 90% chance you’ll make 2 connections<br /> 8% chance you’ll make 3 connections<br /> 2% chance you’ll make 1 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown<br /> 70% chance you’ll fly on China Eastern, China Southern, Air China, Cathay Pacific and/or Asiana<br /> 20% chance you’ll fly on All Nippon (“ANA”), Japan Air, Korean, Emirates, Qatar, Etihad and/or Singapore<br /> 9% chance you’ll fly on Virgin Australia, United and/or Air Canada<br /> 1% chance you’ll fly on QANTAS, Air New Zealand, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed.  Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'EUROPE: Non-Hub/Non-Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 65% chance you’ll make 3 connections<br /> 35% chance you’ll make 2 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown<br /> 75% chance you’ll fly on British Air, Air France, KLM, Lufthansa, Austrian, Turkish and/or Brussels Airlines<br /> 15% chance you’ll fly on Iberia, Aeroflot, TAP Portugal, Virgin Atlantic, Air Canada and/or LOT Polish<br /> 10% chance you’ll fly on Swiss, Scandinavian, FinnAir and/or Aer Lingus</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed. Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'ASIA: Non-Hub/Non-Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 65% chance you’ll make 3 connection<br /> 35% chance you’ll make 2 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown<br /> 65% chance you’ll fly on China Eastern, China Southern, Air China, Asiana, Japan Airlines and/or United<br /> 30% chance you’ll fly on Turkish Airlines, Air France, KLM, All Nippon (“ANA”), Korean, Cathay Pacific, Lufthansa, EVA Air, China Airlines and/or Air Canada,<br /> 5% chance you’ll fly on Singapore, Qatar, Etihad, Swiss, British Air, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed. Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'AUSTRALIA/NZ Non-Hub/Non-Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 90% chance you’ll make 3 connections<br /> 6% chance you’ll make 2 connections<br /> 4% chance you’ll make 4 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown<br /> 70% chance you’ll fly on China Eastern, China Southern, Cathay Pacific, Air China and/or Asiana<br /> 20% chance you’ll fly on All Nippon (“ANA”), Japan Airlines, Korean, Emirates, Qatar, Etihad and/or Singapore<br /> 9% chance you’ll fly on Virgin Australia, United and/or Air Canada<br /> 1% chance you’ll fly on QANTAS, Air New Zealand, Delta and/or American</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed. Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'S. AMERICA Non-hub/hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p><p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections:<br /> 90% chance you’ll make 2 connections<br /> 8% chance you’ll make 3 connections<br /> 2% chance you’ll make 1 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown:<br /> 85% chance you’ll fly on LATAM, Avianca, Copa and/or Aeromexico<br /> 15% chance you’ll fly on United, American, Delta and/or Air Canada</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed. Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'AFRICA/INDIA/MIDEAST Non-Hub/Non-Hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections:<br /> 85% chance you’ll make 3 connection<br /> 8% chance you’ll make 2 connections<br /> 6% chance you’ll make 4 connection</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airline(s) Flown:<br /> 65% chance you’ll fly on Ethiopian, Turkish, Air France and/or EgyptAir<br /> 25% chance you’ll fly on Etihad, Qatar, Emirates, KLM and/or Kenya Airways<br /> 8% chance you’ll fly on Brussels Airlines, Iberia, Lufthansa, TAP Portugal and/or British Air<br /> 2% chance you’ll fly on South African, Swiss, Cathay Pacific and/or Virgin Atlantic</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed. Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'S. AMERICA Non-hub/Non-hub',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>Dear [FirstName],</p> <p>We are excited to get your award request from DEP to ARR underway.</p> <p>Based on our 10 years of tracking lowest-cost (“Saver”) award availability trends, we expect the MOST LIKELY scenarios for you are as follows...</p> <p>Number of Connections<br /> 90% chance you’ll make 3 connections<br /> 8% chance you’ll make 4 connections<br /> 2% chance you’ll make 2 connections</p> <p>Please keep in mind, this is one of the keys to our success. Adding even one extra connection can often be the difference between our lowest-level (“Saver”) award prices and spending up to 400% more miles.</p> <p>Airlines Flown<br /> 85% chance you’ll fly on LATAM, Avianca, Copa, Aeromexico<br /> 15% chance you’ll fly on United, American, Delta and/or Air Canada</p> <p>We only work with high quality, reputable airlines that our company principals have personally flown and vetted. We also have over 98% satisfaction with our 11,000+ clients flying all of these MOST LIKELY airlines for your award request.</p> <p>At this point, please review the options below and respond by posting which option you prefer.</p> <p><b>OPTION 1:</b><br /> If these most likely connection and airline scenarios are all generally acceptable, your scheduled appointment will proceed. Your Award Advisor will provide you with award specifics, and will answer any questions you may have. We will then await your final approval to expedite and ticket your award.</p> <p><b>OPTION 2:</b><br /> If these connection and/or airline scenarios do NOT meet your threshold of acceptability, despite their enabling you to redeem your miles at the lowest rate, please cancel your upcoming appointment and we welcome you to circle back when you see fit.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'Pitch: Roundtrip',
            //     image:  'admin.png',
            //     description: '',
            //     html:
            //     '<p>Dear __________,</p>'+
            //     '<p>Thanks to our proprietary software, together with the creativity and resourcefulness of our award search team, we are pleased to present our itinerary proposal. We’ve scoured flights across 20+ airline and have filtered through over 80 routing options  Below is/are your best case option(s) to review.</p>'+
            //     '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-family:arial; width:100%">'+
            //     '<tbody>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"></td>'+
            //     '<td valign="top" style="width:20%;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>OPTION 1</b></td>'+
            //     '<td valign="top" style="width:5px;border-style:solid;background:#ffffff;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="width:20%;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>OPTION 2</b></td>'+
            //     '<td valign="top" style="width:5px;border-style:solid;background:#ffffff;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="border-style:solid;background:#dedede;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>EXPLANATIONS</b></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Award Redemption Amount</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Taxes/Fees</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Separate Airfare</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Transfer/Buy-Up Costs</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Award Booking Service Fee</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">$165 per person</td>'+
            //     '</tr>'+
            //     '</tbody>'+
            //     '</table>'+
            //     '<p>Transfers may post to account on a delayed basis despite immediate confirmation, impacting award availability in the interim. This scenario happens less than 2% of the time. So while we will very likely find alternate award space once transfer is complete, our company has no liability for delayed transfers or loss of proposed award space.</p>'+
            //     '<p>I welcome any questions you have regarding the award space presented above.  Award space is notoriously fickle and most airlines don’t allow courtesy holds, so we count on your quick decision-making to ensure we can secure the award we have found here.</p>'+
            //     '<p>I look forward to earning your confidence and business and await your guidance on how best to proceed together.  </p>'+
            //     '<p>Best, <br />Team BookYourAward</p>'
            // },
            // {
            //     title: 'Pitch: One Way',
            //     image:  'admin.png',
            //     description: '',
            //     html:
            //     '<p>Dear __________,</p>'+
            //     '<p>Thanks to our proprietary software, together with the creativity and resourcefulness of our award search team, we are pleased to present our itinerary proposal. We’ve scoured flights across 20+ airline and have filtered through over 80 routing options  Below is/are your best case option(s) to review.</p>'+
            //     '<p><strong>Currently, award space is only available in one direction of your travel. You should know, this is quite common; it happens with almost 30% of our clients.</strong></p>'+
            //     '<p>Due to the volatility in award availability, we strongly suggest you lock in one direction NOW.  We will then stay vigilant in searching for awards for the other half of your travel and will report back on a regular basis. </p>'+
            //     '<p>In the very unlikely situation whereby the other half of your award is never made available, we will give you plenty of notice to consider options for alternative dates or different classes of service. Alternatively, you’ll have plenty of time to purchase a one-way ticket, or if you prefer, cancel the original award ticket entirely --  airline cancellation fees of $75-150/person may apply.</p>'+
            //     '<p>Please keep in mind this booking strategy has proven effective in ultimately securing round-trip flights over 96% of the time.</p>'+
            //     '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse; font-family:arial; width:100%">'+
            //     '<tbody>'+
            //     '<tr>'+
            //     '<td valign="top" style="width:120px;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"></td>'+
            //     '<td valign="top" style="width:20%;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>OPTION 1</b></td>'+
            //     '<td valign="top" style="width:5px;border-style:solid;background:#ffffff;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="width:20%;background:#dedede;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>OPTION 2</b></td>'+
            //     '<td valign="top" style="width:5px;border-style:solid;background:#ffffff;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="border-style:solid;background:#dedede;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:10.0px 10.0px 10.0px 10.0px"><b>EXPLANATIONS</b></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Award Redemption Amount</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Taxes/Fees</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Separate Airfare</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Transfer/Buy-Up Costs</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '</tr>'+
            //     '<tr>'+
            //     '<td valign="top" style="color:#fff;width:120px;background:#808080;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"><b>Award Booking Service Fee</b></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px"></td>'+
            //     '<td valign="top" style="background:#ffffff;width:5px;border-style:solid;border-width:1.0px 1.0px 0 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;"></td>'+
            //     '<td valign="top" style="background:#f7f7f7;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#b3b3b3 #b3b3b3 #b3b3b3 #b3b3b3;padding:5.0px 10.0px 5.0px 10.0px">$165 per person</td>'+
            //     '</tr>'+
            //     '</tbody>'+
            //     '</table>'+
            //     '<p>Transfers may post to account on a delayed basis despite immediate confirmation, impacting award availability in the interim. This scenario happens less than 2% of the time. So while we will very likely find alternate award space once transfer is complete, our company has no liability for delayed transfers or loss of proposed award space.</p>'+
            //     '<p>I welcome any questions you have regarding the award space presented above.  Award space is notoriously fickle and most airlines don’t allow courtesy holds, so we count on your quick decision-making to ensure we can secure the award we have found here.</p>'+
            //     '<p>I look forward to earning your confidence and business and await your guidance on how best to proceed together.</p>'+
            //     '<p>Cheers, <br />Team BookYourAward</p>'
            // },
            // {
            //     title: 'Admin: Post-Pitch Final Call',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear _________</p> <p>Per our earlier dialogue, we agreed to get back in touch with one another by _____to finalize your decision about the award we presented.</p> <p>Because award space is not generally subject to courtesy holds and ongoing availability can be very volatile, we are at great risk of losing the lowest ‘Saver’ rate award that will save you hundreds of thousands of miles. As well, there is only a small likelihood that we will be able to replicate finding this award again.</p> <p>So, please respond at your earliest convenience by posting if we have your approval to provide you the award redemption instructions to expedite finalizing your award.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'Admin: Decision Commitment Time',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear</p> <p>I understand that it’s not always possible to finalize approval of your award booking during our appointment. Still, award space remains quite volatile and there are no courtesy holds.<br /> So, I appreciate your agreeing to get back in contact with us via this online thread no later than __________ to hopefully provide your approval to finalize and book the award presented.</p> <p>I look forward to re-engaging with you shortly.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'Admin: Hand-Off To Booker',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear</p> <p>Thanks for your award booking approval and sharing the requested account information with our secure site.</p> <p>We have expedited your award to our Award Booking colleague who will redeem your award, prepay taxes and forward you the final itinerary. They will keep you posted of their progress on your behalf.</p> <p>Thanks,<br /> BookYourAward Team</p>'
            // },
            // {
            //     title: 'Admin: No Appointment Made/Cancel',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear [FIRST NAME]</p> <p>We have been unable to contact you to secure an appointment with us. When you are ready to confirm an appointment time, we welcome you back and we will be poised to assist.</p> <p>Best,<br /> The BookYourAward team</p>'
            // },
            // {
            //     title: 'Booking Instructions: Credit Card Transfer',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Thank you ______,</p> <p>We are happy to have you proceed with the credit card points transfer(s) on your own.<br /> Please do your best to complete the transfer(s) as quickly as possible - even a minor delay of a few extra hours could result in us losing the award space we have found.</p> <p>Immediately after you have initiated the transfer, please alert us. At that point we will finalize the award redemption and get your ticket(s) issued.</p> <b>AMEX</b><br/> <ol style="padding-left:0"><li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now</li> <li style="list-style-position: inside;">Click the link below to transfer your xxx,xxx AMEX points in real time</li> <li style="list-style-position: inside;">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</li></ol> <b>CHASE</b> <ol style="padding-left:0"><li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now</li> <li style="list-style-position: inside;">Login to your Ultimate Rewards account or contact Chase at the number on the back of your card to transfer your xxx,xxx Chase points in real time.</li></ol> <b>CITI</b><br/> <ol style="padding-left:0"><li style="list-style-position: inside;">Open a new mileage account at: http://www.xx.com now</li> <li style="list-style-position: inside;">Login to your Citi Thank You points account or contact Citi at the number on the back of your card to transfer your xxx,xxx Citi points. The transfers usually go through in real time, though some have 2-3 day delays.</li></ol>'
            // },
            // {
            //     title: 'Admin: Credit Card Transfer Options',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Hi _____________,</p> <p>Since most airlines don’t offer courtesy holds, it is important that you quickly execute the points transfer to ensure we can book what we have proposed.</p> <p>To move forward with the transfers, you have two options:</p> <p>OPTION 1 is to allow us to access your online credit card rewards, whereby we can take care of the transfer(s) on your behalf.</p> <p>OPTION 2 is to have you go online (or call) the credit cards yourself to initiate the transfer of the points.</p> <p>You should know, we value your mileage and credit card account security tremendously. To ensure the highest levels of cyber security and privacy, we have instituted a four point web-safe protocol to ensure that your accounts will remain protected while working with us. <ol style="padding-left:0"><li style="list-style-position: inside;">Our company has proprietary software that allows you to share access to your account with us, without actually revealing the username(s) or password(s).</li> <li style="list-style-position: inside;">You will be sent a yellow box below indicating the requested programs we’d like you to share with us; to activate the safe-share function, simply simply click the box as it appears.</li> <li style="list-style-position: inside;">If you choose to share account info on this thread, our site itself is password protected.</li> <li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password information at the conclusion of our booking process.</li></ol></p> <p>Please respond by writing either  “OPTION 1” or “OPTION 2” below.</p> <p>Thank you in advance.</p> <p>Best,<br /> The BookYourAward team</p>'
            // },
            // {
            //     title: 'Admin: Immediate Post-Pitch',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear ________</p> <p>I’m hopeful that our appointment established your trust and confidence to proceed with an award booking. At this point, we will communicate with you via this online thread, which ensures your account privacy and security.  It will also allow our team of Award Insiders to assist with the next steps in ticketing.</p> <p>As per our conversation, please respond below with your final booking decision by _________.  As a reminder, this low-cost (“Saver”) award space is incredibly volatile and courtesy holds are often not permitted. As such, your quick decisionmaking and turnaround time will ensure we can secure your proposed award.</p> <p>OPTION 1. AWARD APPROVAL<br /> We are awaiting your approval of the award option(s) presented during today’s appointment.  Where relevant, we have copied the third parties that will be traveling with you, whose approval you require to proceed.</p> <p>To finalize, at your earliest convenience, simply copy and paste the award flights you prefer into the response box below and confirm your approval.  We will send you an account authorization link in a yellow box below which allows us to book tickets on your behalf using your miles.  Our proprietary software encrypts your confidential log-in credentials to protect your information, though you are welcome to change your password after the booking is completed if you would like.</p> <p>OPTION 2. PREFER TO WAIT<br /> While we aspire to meet your ideal travel preferences, the award options we presented include modest trade-offs in order to save hundreds of thousands of miles.  Based on our experience with similar client routings, we find that waiting 1-2 weeks for updated award options typically has the following results:</p> <ul style="padding-left:0"><li style="list-style-position: inside;">only 5% of the time we find “better” award space/routings</li> <li style="list-style-position: inside;">30% of the time we find exactly the same award space as originally proposed</li> <li style="list-style-position: inside;">55% of the time clients end up with less favorable award options</li> <li style="list-style-position: inside;">10% of the time, all award space disappears and no options are available</li></ul> <p>Due to the risk in waiting to book, we strongly recommend booking what we’ve proposed now.  Choosing to wait for something better is certainly your prerogative however; we just want you to have a clear understanding of what is (potentially) at stake. If you do want to wait, please let us know below.</p> <p>We look forward to securing your award tickets and await your final decision by ______.</p> <p>Cheers,<br /> Team BookYourAward</p>'
            // },
            // {
            //     title: 'SEARCHER: Recurring Fixed Appointment',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear ________</p>' +
            //     '<p>We have remained vigilant on your behalf in searching for award space to be released since our last correspondence.  Good news…! We are ready to present you the most current award search results.</p>' +
            //     '<p>Please schedule an appointment at your earliest convenience to ensure we can secure what we have found on your behalf.</p>' +
            //     'Look forward,<br>BookYourAward Team<br>' +
            //     '{{ bookyouraward_schedule }}'
            // },
            {
                title: 'AIR FRANCE/KLM',
                image: 'special-instructions.png',
                description: '',
                html: '<p>Dear XXX:</p>'+
                '<p>AF has an unfortunate unfriendly customer policy of requiring the passenger\'s credit card to pay taxes as a condition of booking award space, which precludes us from offering you the normal convenience of our handling the award redemption seamlessly.</p>'+
                '<p>As this courtesy hold only lasts a scant 24 hrs, please proceed to finalize the award yourself upon receiving this message by contacting Air France directly and paying your taxes.</p>'+
                '<p>If you prefer that we finalize the award in your behalf, you will need to provide us with your credit card information, either via email or over the phone. Please do not your in credit card information on this thread.</p>'+
                '<p>Please advise how you\'d like to proceed.</p>'+
                '<p>Look forward, <br />BookYourAward Team</p>'
            },
            {
                title: 'UNITED',
                image: 'special-instructions.png',
                description: '',
                html: '<p>United has recently updated their Mileage Plus log in and now require the following security questions to be answered when logging in from another computer, please provide our team with this information and we will finalize your award and prepay taxes. We only need the answers to the questions you answered (typically 5).</p>' +
                '<p>You can change the answers to these questions once the award is confirmed. Please post your United account number and password.</p>' +
                '<p>Thanks,<br />The BookYourAward Team</p>' +
                '<ul style="padding-left:0">' +
                '<li style="list-style-position: inside;">What is your favorite type of vacation?</li>' +
                '<li style="list-style-position: inside;">In what month is your best friend’s birthday?</li>' +
                '<li style="list-style-position: inside;">What is your favorite sport?</li>' +
                '<li style="list-style-position: inside;">What is your favorite flavor of ice cream?</li>' +
                '<li style="list-style-position: inside;">During what month did you first meet your spouse/significant other?</li>' +
                '<li style="list-style-position: inside;">When you were young, what did you want to be when you grew up?</li>' +
                '<li style="list-style-position: inside;">What was the make of your first car?</li>' +
                '<li style="list-style-position: inside;">What is your favorite sea animal?</li>' +
                '<li style="list-style-position: inside;">What is your favorite cold-weather activity?</li>' +
                '<li style="list-style-position: inside;">What is your favorite breed of dog?</li>' +
                '<li style="list-style-position: inside;">What was the first major city that you visited?</li>' +
                '<li style="list-style-position: inside;">What was your least favorite fruit or vegetable as a child?</li>' +
                '<li style="list-style-position: inside;">Who is your favorite artist?</li>' +
                '<li style="list-style-position: inside;">What is your favorite type of music?</li>' +
                '<li style="list-style-position: inside;">What is your favorite type of reading?</li>' +
                '<li style="list-style-position: inside;">What is your favorite pizza topping?</li>' +
                '</ul>'
            },
            {
                title: 'ASIA MILES',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>In order to book your award using Asia Miles, please follow the steps below exactly. It is important to begin this process immediately since the Amex transfer typically takes 3 business days</p>'+
                '<p><b>Step 1: Provide Our Team with VALID Passport Information</b></p>'+
                '<p>Valid Passport information is required for ticketing, we will need the following information for each traveler:</p>'+
                '<ol style="padding-left:0">'+
                '<li style="list-style-position: inside;">Passport Numbers</li>'+
                '<li style="list-style-position: inside;">Issuing Country</li>'+
                '<li style="list-style-position: inside;">Expiration Date</li>'+
                '</ol>'+
                '<p>If your passport has expired you will need to have it reissued, once complete you can reengage with our team and we will check for updated award options.</p>'+
                '<p><b>Step 2: Transfer Amex to Asia Miles</b></p>'+
                '<ol style="padding-left:0">'+
                '<li style="list-style-position: inside;">Open a new Asia Miles account at and make sure the name matches your passport EXACTLY: <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.asiamiles.com" target="blank">http://www.asiamiles.com</a></li>'+
                '<li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points: <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">XXXX miles</strong></li>'+
                '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow" target="blank">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li>'+
                '</ol>'+
                '<p><b>Step 3: Add All Passengers to your Asia Miles Redemption Group</b></p>'+
                '<p>Asia Miles requires you to add all travelers to your redemption group before we can book their tickets. <br />Sign in to Asia Miles, under the profile section you can expand on “Your Redemption Group” .</p>'+
                '<ul style="padding-left:0">'+
                '<li style="list-style-position: inside;">Select - “Edit Redemption Group”</li>'+
                '<li style="list-style-position: inside;">For each additional traveler, add their name <strong>EXACTLY</strong> as it is on their passport</li>'+
                '<li style="list-style-position: inside;">Enter <b><i>your</i></b> birthdate and agree to the terms in order to submit</li>'+
                '</ul>'+
                '<p>After you’ve added each passenger, Asia Miles will email you a one-time code that you need to enter in the profile to complete the process.</p>'+
                '<p><b>Step 4: Provide Electronic Authorization of Your Account to Our Team</b></p>'+
                '<pWe will need access and authorization on your Asia Miles account in order to call them and book your tickets. <br />For your privacy and security, we have proprietary software that allows you to provide authorization without revealing your password.</p>'+
                '<ol style="padding-left:0">'+
                '<li style="list-style-position: inside;">You will see a yellow box below requesting your account access. Please click through that box to enter your account number and password + grant access/permission to our team.</li>'+
                '<li style="list-style-position: inside;">If you have any technical difficulties, you can type the account number/password as a text message and we’ll set it up for you.</li>'+
                '<li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
                '</ol>'+
                '<p>Then, we will contact Asia Miles to redeem your award and prepay all applicable taxes. <br />We trust this process is clear and straightforward and look forward to finalizing your booking.</p>'+
                '<p>Much obliged, <br />BookYourAward</p>'
            },
            {
                title: 'SINGAPORE',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>In order to book your award using Singapore Airlines, please follow the steps below exactly. It is important to begin this process immediately since the transfer times vary and are not immediate.</p>'+
                '<p><strong>Step 1: Provide Our Team with VALID Passport Information</strong></p>'+
                '<p>Valid Passport information is required for ticketing, we will need the following information for each traveler:</p>'+
                '<ul style="padding-left:0">'+
                '<li>Passport Numbers</li>'+
                '<li>Issuing Country</li>'+
                '<li>Expiration Date</li>'+
                '</ul>'+
                '<p style="color: #cc3d5e">If your passport has expired you will need to have it reissued, once complete you can reengage with our team and we will check for updated award options.</p>'+
                '<p><strong>Step 2: Transfer Amex to Singapore Airlines</strong></p>'+
                '<ol style="padding-left:0">'+
                '<li>Open a new Singapore Airlines account at <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" target="_blank" href="https://www.singaporeair.com/en_UK/us/home">https://www.singaporeair.com/en_UK/us/home</a> and make sure the name matches your passport EXACTLY</li>'+
                '<li>Click the link below to transfer the following number of Amex points: <span style="color: #cc3d5e">XXX,000 miles</span></li>'+
                '<li><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" target="_blank" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li>'+
                '</ol>'+
                '<p><strong>Step 3: Add All Passengers to your Singapore Airlines Redemption Group</strong></p>'+
                '<p>Singapore Airlines requires you to add all travelers to your redemption nominees before we can book their tickets.</p>'+
                '<p>Sign in to your Singapore Airlines profile and you will see “Redemption Nominees” listed under your profile.</p>'+
                '<ul style="padding-left:0">'+
                '<li>Select - “Add Nominee”</li>'+
                '<li>For each additional traveler, add their name <strong>EXACTLY</strong> as it is on their passport</li>'+
                '<li>Enter birthdate</li>'+
                '<li>Enter passport number and place of issue</li>'+
                '<li>Enter passport expiration date</li>'+
                '<li>Agree to the terms in order to submit</li>'+
                '</ul>'+
                '<p><strong>Step 4: Provide Electronic Authorization of Your Account to Our Team</strong></p>'+
                '<p>We will need access and authorization on your Asia Miles account in order to call them and book your tickets.</p>'+
                '<p>For your privacy and security, we have proprietary software that allows you to provide authorization without revealing your password.</p>'+
                '<ol style="padding-left:0">'+
                '<li>You will see a yellow box below requesting your account access. Please click through that box to enter your account number and password + grant access/permission to our team.</li>'+
                '<li>If you have any technical difficulties, you can type the account number/password as a text message and we’ll set it up for you.</li>'+
                '<li>As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
                '</ol>'+
                '<p>Then, we will contact Asia Miles to redeem your award and prepay all applicable taxes.</p>'+
                '<p>We trust this process is clear and straightforward and look forward to finalizing your booking.</p>'+
                '<p>Much obliged, <br />The BookYourAward Team (Steve, Becky, Ceca, and Ricardo)</p>'
            },
            {
                title: 'KOREAN',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>Hi :,</p>'+
                '<p>The following flights are on hold with Korean Airways for your itinerary and can be referenced under confirmation <b>XXXX</b>.</p>'+
                '<p>Korean Air requires all awards to be redeemed directly by the account holder so to proceed you will need to</p>' +
                '<ul style="padding-left:0">' +
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
                title: 'ANA',
                image: 'special-instructions.png',
                description: '',
                html: '<p>ANA has a two part registration process that you will follow below. Once you have completed both parts, please forward us your new ANA Mileage Club account number and password. Amex transfers to ANA can take up to 48-72 hrs so please check your account daily and alert us when the transfer has actually posted into the ANA account so that we can expedite to booking queue.</p>'+
                '<p><strong>REGISTRATION</strong></p>'+
                '<ol style="padding-left:0">'+
                '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://cam.ana.co.jp/amcmember/amcentry/AMCEntryFacadeEn">https://cam.ana.co.jp/amcmember/amcentry/AMCEntryFacadeEn</a></li>'+
                '<li style="list-style-position: inside;">Mail/Residence Entry, click</li>'+
                '<li style="list-style-position: inside;">Customer Information, Address, Email, Password Information, click (no fill in cell phone, triggers an error)</li>'+
                '<li style="list-style-position: inside;">Confirming Your Information</li>'+
                '</ol>'+
                '<p>After submitting and confirming able info, then.........</p>'+
                '<p><strong>AWARD REDEMPTION GROUP</strong></p>'+
                '<p>For each additional person (who must be related to you somehow!!), there is this separate section that must be completed to authorize booking for them.</p>'+
                '<p><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="https://rps.ana.co.jp/awe_a/afa/registration/us/form_e.html">https://rps.ana.co.jp/awe_a/afa/registration/us/form_e.html</a></p>'+
                '<ol style="padding-left:0">'+
                '<li style="list-style-position: inside;">fill out your account info handsome account number for each registered name/relationship/birthdate of each traveler</li>'+
                '</ol>'+
                '<p>Submit and confirm.</p>'+
                '<p>Because award space is quite volatile, we count on your cooperation to execute this transfer quickly to ensure we can secure the award we have found on your behalf. As we discussed, we’re confirming your agreement to execute these credit card transfers by TIME AND DAY</p>'+
                '<p>Much obliged,<br />BookYourAward Team</p>'
            },
            {
                title: 'BA',
                image:  'special-instructions.png',
                description: '',
                html:
                '<p>Please be well aware of the important provisos of BA Companion cert:</p>' +
                '<ol style="padding-left:0">' +
                '<li style="list-style-position: inside;"><b>Both</b> passengers are responsible for taxes/fuel surcharges of approx. $1300-$1600 per person</li>'+
                '<li style="list-style-position: inside;">Only BA aircraft is eligible, no partners, so if no award space out of origin airport or into destination airport, you will be responsible for purchasing separate airfares from whatever alternate airports we find.</li>'+
                '<li style="list-style-position: inside;">Not bookable online nor bookable by any third party with BA call center, so we will provide you the exact flight info, and you will contact BA direct to redeem award and prepay taxes.</li>'+
                '</ol>' +
                '<p>Our service typically offers a far more seamless service, but the restrictions imposed by the companion cert require more customer involvement.</p>' +
                '<p>If you are amenable to the above, we will be poised to try and assist.</p>' +
                '<p>Look forward<br>Steve</p>'
            },
            // {
            //     title: 'Booking Instructions: United Security Questions',
            //     image: 'special-instructions.png',
            //     description: '',
            //     html: '<p>Dear _______,</p>'+
            //           '<p>United has recently updated their MileagePlus log in and now requires the following security questions to be answered when logging in from another computer. Please provide our team with this information and we will finalize your award and prepay taxes. We only need the answers to the questions you answered (typically 5). You can change the answers to these questions once the award is confirmed if you choose. Please also post your United account number and password. Rest assured, our site is equipped with the highest levels of security; any information you type into our message center is 100% safe and secure.</p>'+
            //           '<p>Thanks,<br />The BookYourAward Team</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">What is your favorite type of vacation?</li>'+
            //           '<li style="list-style-position: inside;">In what month is your best friend’s birthday?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite sport?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite flavor of ice cream?</li>'+
            //           '<li style="list-style-position: inside;">During what month did you first meet your spouse/significant other?</li>'+
            //           '<li style="list-style-position: inside;">When you were young, what did you want to be when you grew up?</li>'+
            //           '<li style="list-style-position: inside;">What was the make of your first car?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite sea animal?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite cold-weather activity?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite breed of dog?</li>'+
            //           '<li style="list-style-position: inside;">What was the first major city that you visited?</li>'+
            //           '<li style="list-style-position: inside;">What was your least favorite fruit or vegetable as a child?</li>'+
            //           '<li style="list-style-position: inside;">Who is your favorite artist?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite type of music?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite type of reading?</li>'+
            //           '<li style="list-style-position: inside;">What is your favorite pizza topping?</li>'+
            //           '<li style="list-style-position: inside;">What musical instrument do you play?</li>'+
            //           '</ul>'
            // },
            // {
            //     title: 'Admin: Account Access Error',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear __________,</p>'+
            //           '<p>Our system is returning an error with the <strong>[insert airline or credit card name]</strong> information you provided. This typically just means there was a typo in either the account number and/or password. Please double-check that both were entered correctly and update as necessary. After you’ve verified the information, you can confirm it’s correct by clicking the "Auto-login" link below.</p>'+
            //           '<p>Alternatively you can type the account number and password in this message thread as text and we can set it up manually for you. Rest assured, our site is equipped with the highest levels of security to ensure all information sent in the message center is safe. </p>'+
            //           '<p>Lastly, please <strong>update this information as quickly as possible</strong>. Remember, award space is quite a fickle beast and we’d hate to lose this award availability. </p>'+
            //           '<p>Thanks,<br />The BookYourAward Team</p>'
            // },
            // {
            //     title: 'Admin: AMEX Transfer By AI',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Thank you ______,</p>'+
            //           '<p>We are happy to proceed with the credit card points transfer(s) on your behalf.</p>'+
            //           '<p><strong>Please click the “share” button on the yellow box</strong>. Remember, this allows us to access your credit card reward points without actually seeing any of your login credentials.</p>'+
            //           '<p>Due to the heightened online security at American Express, they require two additional pieces of information. <strong>Please send us the 4-digit security code on the front of your American Express card as well as the 3-digit security code on the back.</strong></p>'+
            //           '<p>Please note: Time is of the essence - even a minor delay of a few extra hours could result in us losing the award space we have found.</p>'+
            //           '<p>We appreciate your timely cooperation and will keep you posted when ticketing the award is finalized.</p>'+
            //           '<p>Best,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'POST-PITCH: Want to Wait',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear ______,</p>'+
            //           '<p>I’m hopeful that our appointment established your trust and confidence to proceed with this award booking.</p>'+
            //           '<p>That said, your inclination to wait for better award space to be released is understandable.  While I aspire to meet your ideal travel preferences, over 80% of low-cost “Saver” award space includes the modest trade-off(s) you were hesitant about - keep in mind this will save you hundreds of thousands of miles.</p>'+
            //           '<p>Based on BookYourAward’s’ experience with similar award routing requests, we find that waiting 1-2 weeks in hopes of improved award options typically has the following results:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">55% of the time clients end up with less favorable award options</li>'+
            //           '<li style="list-style-position: inside;">30% of the time we find exactly the same award space as originally proposed</li>'+
            //           '<li style="list-style-position: inside;">10% of the time, all award space disappears and no options are available</li>'+
            //           '<li style="list-style-position: inside;"><strong>Only 5% of the time we find “better” award space/routings</strong></li>'+
            //           '</ul>'+
            //           '<p>Due to the risk in waiting to book, I strongly recommend booking the award we previously discussed. I trust we have provided you with helpful context to make an informed decision as to whether or not to approve the proposed award options.</p>'+
            //           '<p>OPTION 1 <br />If you choose to proceed, please post which of the award options you prefer.  I will then provide you with the next steps in redeeming and ticketing your award.</p>'+
            //           '<p>OPTION 2 <br />If you choose to wait, we will now commence an ongoing search on your behalf. Unless we find award space in the interim, we will be back in touch with a status update in 7-10 days.</p>'+
            //           '<p>Please advise which option you prefer to proceed with by the end of business day today.</p>'+
            //           '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'BOOKER: Account Access Error',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear ______,</p>'+
            //           '<p>Our system is returning an error with the <strong>[insert airline or credit card name]</strong> information you provided. This typically just means there was a typo in either the account number and/or password. Please double-check that both were entered correctly and update as necessary. After you’ve verified the information, you can confirm it’s correct by clicking the "Auto-login" link below.</p>'+
            //           '<p>Alternatively you can type the account number and password in this message thread as text and we can set it up manually for you. Rest assured, our site is equipped with the highest levels of security to ensure all information sent in the message center is safe.</p>'+
            //           '<p>Lastly, please <strong>update this information as quickly as possible</strong>. Remember, award space is quite a fickle beast and we’d hate to lose this award availability.</p>'+
            //           '<p>Thanks,<br />The BookYourAward Team</p>'
            // },
            // {
            //     title: 'Post-Pitch: Follow Up Appt',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear _______</p>'+
            //           '<p>I’m hopeful that our appointment established your trust and confidence to proceed with this award booking.</p>'+
            //           '<p>I understand that you need a little extra time to confer with your travelling companion(s) before approving this award.  Per our conversation, we will be in touch with you at _______ to finalize the details of the itinerary.  Since this low-cost (“Saver”) award space is incredibly volatile and courtesy holds are often not permitted, your quick decisionmaking and turnaround time will ensure we can secure the award as proposed. </p>'+
            //           '<p>If you choose to proceed, please post which award options you prefer.  I will then provide you the instructions to expedite redeeming and ticketing your award.</p>'+
            //           '<p>Looking forward to our follow-up contact at _________.</p>'+
            //           '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'POST-PITCH: Approve To Book',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear _______</p>'+
            //           '<p>I’m pleased that our appointment established your trust and confidence to proceed with an award booking.  We’ll be in touch shortly with information on how to finalize this approved award.</p>'+
            //           '<p>Best,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Booking Instructions - Air France',
            //     image: 'special-instructions.png',
            //     description: '',
            //     html: '<p>Air France does not allow third party bookings and requires that the account holder directly redeem awards and prepay taxes. We will assist with your award booking through this straightforward process:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Open a new mileage account at: http://www.airfrance.com now (or use an existing account if you have one)</li>'+
            //           '<li style="list-style-position: inside;">Provide us with the account number and 4 digit pin code</li>'+
            //           '</ul>'+
            //           '<p>AMEX TRANSFER</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points: XXXK</li>'+
            //           '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li>'+
            //           '</ul>'+
            //           '<p>OR CHASE TRANSFER</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Login to your Ultimate Rewards account or contact Chase at the number on the back of your card to transfer your <strong>xxx,xxx</strong> Chase points in real time.</li>'+
            //           '</ul>'+
            //           '<p>OR CITI/ THANKYOU POINTS TRANSFER</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Login to your Citi Card account or contact Citi Card at the number on the back of your card to transfer your <strong>xxx,xxx</strong> Citi points in real time.</li>'+
            //           '<li style="list-style-position: inside;">We will secure a courtesy hold on your flights after this phone appointment and provide the 6-digit reservation code for your hold</li>'+
            //           '<li style="list-style-position: inside;">Once the hold is secure, we will advise your final AmEx transfer instructions and you will have 24 hours to contact Flying Blue at 800-375-8723 to pay taxes and redeem the award</li>'+
            //           '<li style="list-style-position: inside;">Please post on this site your confirmation that award was indeed booked</li>'+
            //           '</ul>'+
            //           '<p>We trust this process is clear and straightforward and look forward to finalizing your booking.</p>'+
            //           '<p>Much obliged,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'ADMIN: Citi/Chase Transfer by AI',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Thank you ______,</p>'+
            //           '<p>We are happy to proceed with the credit card points transfer(s) on your behalf.</p>'+
            //           '<p><strong>Please click the “share” button</strong> on the yellow box. Remember, this allows us to access your credit card reward points without actually seeing any of your login credentials. </p>'+
            //           '<p>Please note: Time is of the essence - even a minor delay of a few extra hours could result in us losing the award space we have found.</p>'+
            //           '<p>As soon as we receive your authorization, we will proceed with the booking itself and will notify you once your award has been finalized.</p>'+
            //           '<p>We appreciate your timely cooperation.</p>'+
            //           '<p>Best,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Objection: Turkish Safety',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>The Istanbul airport is currently one of the safest in all of Europe due to the security measures they`ve recently implemented (in addition to housing the national air force headquarters). Another advantage to transiting IST is its terrific  passenger experience; their award winning Business Class Lounge rivals other top lounges worldwide.</p>'+
            //           '<p>Thanks to their onboard chefs, lie flat Business Class seats and inflight amenities, Turkish Airlines has been voted Europe’s best airline 6 years in a row.   Another benefit of flying this world class airline is convenient connections to more countries than any other in the world.  Furthermore, Turkish is a terrific value proposition- there are very low taxes/fees, and they offer some of the competitive mileage prices in the industry.</p>'+
            //           '<p>None of our clients that have flown Turkish have expressed any negative feedback about either the safety of IST airport or the quality of the in-flight experience. While we can appreciate your reluctance due to the flurry of information from third party media outlets, we trust we have provided you tangible and first hand reassurance here. Please let us know how you want to proceed.</p>'+
            //           '<p>Thank you,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Booking Instructions - Cathay Pacific',
            //     image: 'special-instructions.png',
            //     description: '',
            //     html: '<p>Thank you ______,</p>'+
            //           '<p>Since Asia Miles doesnt offer courtesy holds, it is important that you quickly execute the following transfer to ensure we can book the proposed award.</p>'+
            //           '<p><strong>Step 1: Transfer Amex to Asia Miles</strong></p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Open a new mileage account at: <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.asiamiles.com">http://www.asiamiles.com</a> now</li>'+
            //           '<li style="list-style-position: inside;">Click the link below to transfer the following number of Amex points in real time: XXXK</li>'+
            //           '<li style="list-style-position: inside;"><a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow">http://www.membershiprewards.com/catalog/travel/NewPointsTransfer.aspx?intlink=TravelMRtab2011BasicTransferNow</a></li>'+
            //           '</ul>'+
            //           '<p><strong>Step 2: Verify Travel Companions</strong><br />In order to use your miles to book travel for other people, you will need to add them to your “Redemption Group” in your Asia Miles profile. You will see “Redemption Group” on the right hand side of the main screen.</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Select - Manage Redemption Group</li>'+
            //           '<li style="list-style-position: inside;">Enter Title and Full Name for</li>'+
            //           '<li style="list-style-position: inside;">Enter <strong>your</strong> birthdate</li>'+
            //           '<li style="list-style-position: inside;">Agree to the terms</li>'+
            //           '<li style="list-style-position: inside;">Submit</li>'+
            //           '</ul>'+
            //           '<p>Asia Miles will send a verification email to you with a one time code you will need to enter in order to complete this process.</p>'+
            //           '<p><strong>Step 3: Provide Our Team with Passport Information</strong><br />Passport information is required for ticketing.  We will need the following information for each traveler:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Passport Numbers</li>'+
            //           '<li style="list-style-position: inside;">Issuing Country</li>'+
            //           '<li style="list-style-position: inside;">Expiration Date</li>'+
            //           '</ul>'+
            //           '<p><strong>Step 4: Provide Electronic Authorization of Your Account to Our Team</strong><br />We value your mileage and credit card account security and privacy. So, we have instituted a three point security protocol to ensure that your accounts will remain protected while working with us.</p>'+
            //           '<ol style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Our company has proprietary software that allow you to share access to your account with us, without revealing your private login and password information.</li>'+
            //           '<li style="list-style-position: inside;">You will be sent a yellow box with the requested programs to share and you simply click that box to activate the auto-sharing function.</li>'+
            //           '<li style="list-style-position: inside;">If you choose to share account info on this thread, our site itself is password protected.</li>'+
            //           '<li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
            //           '</ol>'+
            //           '<p>You will see a yellow box with the accounts we need you to click to activate sharing accounts with us in a secure environment. Then, we will expedite to our booking queue to redeem award and prepay taxes.</p>'+
            //           '<p>We trust this process is clear and straightforward and look forward to finalizing your booking.</p>'+
            //           '<p>Much obliged,<br />(Staff)</p>'
            // },
            // {
            //     title: 'Special Instructions - Korean Air',
            //     image: 'special-instructions.png',
            //     description: '',
            //     html: '<p>Hi :,</p>'+
            //           '<p>The following flights are on hold with Korean Airways for your itinerary and can be referenced under confirmation <strong>XXXX</strong>.</p>'+
            //           '<p>Korean Air requires all awards to be redeemed directly by the account holder so to proceed you will need to</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Transfer xxxx points to each account and complete the Skypass Award application.</li>'+
            //           '<li style="list-style-position: inside;">Download and Complete Skypass Award Application at <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="www.koreanair.com">www.koreanair.com</a></li>'+
            //           '<li style="list-style-position: inside;">Email completed form along with copies of passports to enskypass@koreanair.com</li>'+
            //           '<li style="list-style-position: inside;">Once received you can contact Korean Airways at 800.438.5000 to finalize the award and pay taxes</li>'+
            //           '</ul>'+
            //           '<p>Our service typically offers a far more seamless service, but the restrictions imposed by Korean Air require more customer involvement.</p>'+
            //           '<p>Please keep us posted on your progress and advise if you have any questions.</p>'+
            //           '<p>Thank you,<br />(Staff Name)</p>'
            // },
            // {
            //     title: 'Special Instructions - British Air Companion Cert',
            //     image: 'special-instructions.png',
            //     description: '',
            //     html: '<p>Please be well aware of the important provisos of BA Companion cert:</p>'+
            //           '<ol style="padding-left:0">'+
            //           '<li style="list-style-position: inside;"><strong>Both</strong> passengers are responsible for taxes/fuel surcharges of approx. $1000-1400 per person</li>'+
            //           '<li style="list-style-position: inside;">Only BA aircraft is eligible, no partners, so if no award space out of origin airport or into destination airport, you will be responsible for purchasing separate airfares from whatever alternate airports we find.</li>'+
            //           '<li style="list-style-position: inside;">Not bookable online nor bookable by any third party with BA call center, so we will provide you the exact flight info, and you will contact BA direct to redeem award and prepay taxes.</li>'+
            //           '</ol>'+
            //           '<p>Our service typically offers a far more seamless service, but the restrictions imposed by the companion cert require more customer involvement.</p>'+
            //           '<p>If you are amenable to the above, we will be poised to try and assist.</p>'+
            //           '<p>Look forward,<br />Steve</p>'
            // },
            // {
            //     title: 'Request Fixed Appointment',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear [Name],</p>'+
            //     '<p>Thanks for your patience while we were vigilant in monitoring award space to complete your booking. <br />Please scroll up to the calendar in the first post and choose an appointment time today or tomorrow to discuss the award options available now.</p>'+
            //     '<p>Thanks, <br />Team BookYourAward</p>'
            // },
            // {
            //     title: 'BOOKER: Award Space Changed In Interim',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear</p>'+
            //           '<p>Thanks for your timely approval of the award proposed during our phone session. Despite our equal timeliness in contacting the airline to book your award, inventory changed in the brief interval between our session and contacting the airline. We are dependent on reaching most airlines by phone and wait times are unpredictable from circumstances like severe weather, air traffic control, or mechanical issues impacting flying passengers. As well, most airlines don’t allow courtesy holds to protect the award space that we find.</p>'+
            //           '<p>So, award space will hopefully be refreshed to our advantage in the next 3-4 days, so please schedule a follow-up phone session below in that time window and we hope to have good news by that time. We appreciate your patience and understanding about this issue that unfortunately is beyond our control.  More soon.</p>'+
            //           '<p>Cheers, <br />Book Your Award Team</p>'
            // },
            // {
            //     title: 'Objection: Routing/Journey Length',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>Dear __________,</p>'+
            //           '<p>Airlines typically only re lease the lowest-level "Saver" awards on routings that leave seats unsold. Therefore, popular non-stops and other streamlined routings have minimal (if any) award space released.</p>'+
            //           '<p>While your proposed “Saver” award routing includes some extra travel time, you are benefitting with incredible mileage savings. Remember, the alternatives are going to cost hundreds of thousands of more miles.</p>'+
            //           '<p>Choosing to wait for something better is certainly your prerogative, and you are welcome to have us re-engage our search. That said, please understand the risks associated with having us go back to the drawing board.</p>'+
            //           '<p>Based on our experience with similar situations, we find that waiting for 1-2 weeks yields the following results:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;"><strong>50% of the time clients end up with less favorable routings</strong></li>'+
            //           '<li style="list-style-position: inside;"><strong>35% of the time we find exactly the same award space as originally proposed</strong></li>'+
            //           '<li style="list-style-position: inside;"><strong>10% of the time we are unable to find any award space at all</strong></li>'+
            //           '<li style="list-style-position: inside;"><strong>5% of the time a better award becomes available</strong></li>'+
            //           '</ul>'+
            //           '<p>As you can tell, our suggestion is to book what we we’ve proposed now. This will provide you the certainty and peace of mind of having a confirmed award locked in.</p>'+
            //           '<p>Thank you for your continued trust.</p>'+
            //           '<p>Sincerely, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Objection: Poor Reviews',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>Dear</p>'+
            //           '<p>Our company principals personally fly and vet every airline that we recommend to folks like you. We have also booked hundreds of awards with <strong>(this airline)</strong>, and have only received positive feedback.</p>'+
            //           '<p>Third party "review" sites are statistically insignificant; not to mention, many reviewers make unrealistic comparisons between airlines.</p>'+
            //           '<p>These sites INHERENTLY skew towards those more apt to complain, as compared those who have a satisfying experience. Remember, those who have poor experiences are much more likely to write a review (and “vent”) online than those who didn’t. </p>'+
            //           '<p>We hope our first-hand analysis trumps the anonymity of a third party site.</p>'+
            //           '<p>Thank you, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Objection: Crappy Airline',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>Dear</p>'+
            //           '<p>In the past several years, the airline industry has enjoyed an unprecedented influx of new aircraft like the A380, A350 and Boeing 787 Dreamliner. Premium-class cabins have also seen huge improvements recently, with lie-flat seats, in-flight chefs, larger TVs, upgraded amenities and so much more. As a result, many of the negative perceptions about some airlines’ reputations are now inaccurate.</p>'+
            //           '<p><strong>(airline name)</strong> is a great example of this. Our company principals and their families have flown and vetted this airline as part of recent personal trips. Without a doubt, they both agree <strong>( airline name)</strong>  meets all the safety standards for us to recommend to our clients, not to mention the in-flight experience was terrific.</p>'+
            //           '<p>Beyond their personal experiences, we have booked awards on this airline for hundreds of other clients and we are pleased to report only positive feedback has been received. This airline has clearly stepped up its game over the past several years and we hope that we’ve provided you the tangible reassurance to merit moving forward with your booking.</p>'+
            //           '<p>Cheers, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Objection: Purchase Separate Economy Airfare',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>Dear _______,</p>'+
            //           '<p>In order to find award space at the low cost “Saver” level, over 85% of our bookings are subject to modest workarounds. In your particular case, this includes purchasing a reasonably priced airfare for short-haul flight(s).</p>'+
            //           '<p>With airlines flying at over 90% capacity, they have little if any incentive to release award seats in Economy Class, let alone Business Class, when flights are projected to sell out.</p>'+
            //           '<p>We hope you can agree that this modest workaround will be well worth the benefit of saving hundreds of thousands of miles.</p>'+
            //           '<p>Thank you, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Objection: Luggage Recheck (Outbound ex USA)',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>Dear _______,</p>'+
            //           '<p>In order to find award space at the low cost “Saver” level, over 85% of our bookings are subject to modest workarounds. In your particular case, this includes claiming and rechecking your luggage at the connecting domestic airport.  This is only an issue for the outbound portion of your trip.</p>'+
            //           '<p>Your first flight will be on an airline that does not have a baggage-transfer agreement with the airline(s) that will fly you onward to your destination (baggage fees may apply on the paid ticket). This happens in many cases where a purchased, domestic ticket is combined with an international award ticket. </p>'+
            //           '<p>We have ensured that there is at least 2.5 hours of connection time for you to retrieve your luggage at the baggage claim carousel and then recheck them at the airline ticket counter for the next flight.  We recognize that these logistics are not ideal, but it’s the airlines that have instituted these customer-unfriendly policies and we simply have to play by their rules.</p>'+
            //           '<p>We hope you can agree that, despite this modest workaround, saving hundreds of thousands of miles is still a compelling value proposition.</p>'+
            //           '<p>Thank you, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Objection: Taxes/Fuel Surcharges',
            //     image: 'objections.png',
            //     description: '',
            //     html: '<p>Dear _______,</p>'+
            //           '<p>You should know, each airport and country around the world have varying government-imposed taxes they levy on passengers, regardless of whether it’s a paid or mileage ticket. In the case of award  redemptions, because there is no charge for the ticket itself, they have to collect these fees separately. Furthermore, airlines have what they call “fuel surcharges,” which are meant to cover the cost of fuel on a given flight. These fuel surcharges vary a great deal from airline to airline.</p>'+
            //           '<p>We understand your frustration with these unfortunately excessive charges. Considering this should be a “free” ticket, these airlines have some nerve to tack on fees like this. We are absolutely on your side, and hate the concept of these fees (almost) as much as you do. </p>'+
            //           '<p>Here’s our dilemma: We sought alternative flights to both meet your travel needs and mitigate these excessive fees. Unfortunately, there was nothing we could do. The options we have shown you represent the absolute best and least expensive award flights to fit within your parameters.</p>'+
            //           '<p>Hopefully your ability to save hundreds of thousands of miles with this award, will allow you to move forward despite these airline and government-imposed fees.</p>'+
            //           '<p>Looking forward to getting the “green light” from you to proceed.</p>'+
            //           '<p>Best, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Post Pitch: Share Account Access',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Since most airlines do not offer courtesy holds, it is important that you quickly share your mileage account with us to ensure we can book what we have proposed. <br />We value your mileage and credit card account security and privacy. So, we have instituted a three point security protocol to ensure that your accounts will remain protected while working with us.</p>'+
            //           '<ol style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Our company has proprietary software that allow you to share access to your account with us, without revealing your private login and password information. You will be sent a yellow box with the requested programs to share and you simply click that box to activate the auto-sharing function.</li>'+
            //           '<li style="list-style-position: inside;">If you choose to share account info on this thread, our site itself is password protected.</li>'+
            //           '<li style="list-style-position: inside;">As an added layer of protection, we welcome you to change your login and password at the conclusion of our booking process.</li>'+
            //           '</ol>'
            // },
            // {
            //     title: 'Post-Pitch: Last Chance To Respond',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear _________,</p>'+
            //           '<p>Per our previous conversation, I was hoping to hear back from you by _______ to finalize the award itinerary we presented.</p>'+
            //           '<p>Because award space is not eligible for courtesy holds, together with the fact that ongoing availability can be volatile, we are at great risk of losing the lowest-rate “Saver” award that will save you hundreds of thousands of miles.  Furthermore, the chances are very slim that we will be able to replicate this award again. </p>'+
            //           '<p>So, please respond at your earliest convenience below and let us know if we have your approval to move forward. Once you agree to the itinerary we presented, we’ll be ready to provide you with the next steps to finalizing your award.</p>'+
            //           '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Post Pitch: OK Recurring Search',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear _________,</p>'+
            //           '<p>We’re sorry we were unable to move forward with our proposed itinerary. What we presented was the only option available at this time. We understand you’d like us to continue searching for space that may become available, and we’d be happy to assist.</p>'+
            //           '<p>Beginning today, we will commence an ongoing, customized search on your behalf spanning over 60 routing options and 25 airline partners. </p>'+
            //           '<p>We will contact you either:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">As soon as award space opens up, or...</li>'+
            //           '<li style="list-style-position: inside;">In two weeks, regardless of whether or not new award space has opened up.</li>'+
            //           '</ul>'+
            //           '<p>Thank you for your continued patience. </p>'+
            //           '<p>Sincerely,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Advice to Book One Way Now',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Dear XXX:</p>'+
            //           '<p>We strongly suggest to lock in the available one-way award NOW. Waiting for both directions of travel to be released simultaneously risks losing this attractive one-way flight in the interim.</p>'+
            //           '<p>Due to the volatility in released award space, we book flights one direction at a time with over 65% of our clients. Rest assured, this strategy has proven effective in ultimately securing round-trip flights 99% of the time. We will continue to look for flights for the other half of your award and will report back on a bi-weekly basis.</p>'+
            //           '<p>On the very, very off-chance the other half of your award is not released within 4 weeks of your date window, we will split the cost of any mileage redeposit/cancellation fees, so that you know we are vested in a successful outcome. We are confident in this one way booking strategy.</p>'+
            //           '<p>Regards, <br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'ADMIN:  No Payment Received Yet',
            //     image: 'admin.png',
            //     description: '',
            //     html: '<p>Thank you for entrusting Book Your Award with your flight needs and we appreciated the opportunity to earn your confidence and business.</p>'+
            //           '<p>According to our records, we have not yet received payment for our services as well as the airline tax reimbursements we prepaid on your behalf during the booking process.  We understand that oversights happen and look forward to receiving your payment from the invoice above within the next 7 days.  If you have already mailed your payment, please accept our thanks and apologies for any inconvenience this may have caused.</p>'+
            //           '<p>Thank you for your attention to this matter and for your continued business</p>'+
            //           '<p>Thank you,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Likelihood oneworld Europe',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>We are excited to get your award request from <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">DEP</strong> to <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">ARR</strong> underway.  Based on similar requests we’ve booked over the past 10 years, we expect the MOST LIKELY award flights to be as follows:</p>'+
            //           '<p><strong>Number of Connections</strong> <br />Popular routings sell out to customers paying full-fare, so adding a connection allows us to confirm business class award flights.</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">75% chance you’ll make 1 connection</li>'+
            //           '<li style="list-style-position: inside;">15% chance you’ll get a nonstop flight</li>'+
            //           '<li style="list-style-position: inside;">10% chance you’ll make 2 connections</li>'+
            //           '</ul>'+
            //           '<p><strong>Airlines Flown</strong> <br />All airlines we book and recommend are high quality, reputable airlines that our company members have personally flown and vetted, as well as receiving positive feedback from other clients like yourself.</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">75% chance you’ll fly on British Airways</li>'+
            //           '<li style="list-style-position: inside;">15% chance you’ll fly on Iberia Airlines</li>'+
            //           '<li style="list-style-position: inside;">10% chance you’ll fly on American Airlines, Jet Airways, or Finnair</li>'+
            //           '</ul>'+
            //           '<p><strong>Taxes and fees</strong> <br />Award tickets include government-mandated taxes and security fees, and some airlines also collect fuel surcharges.  Your expected costs:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">75% $900-1200 per person</li>'+
            //           '<li style="list-style-position: inside;">15% $200-400 per person</li>'+
            //           '<li style="list-style-position: inside;">10% $100-200 per person</li>'+
            //           '</ul>'+
            //           '<p><strong>Separately purchased airfare</strong> <br />More often than not, there is no award space from your home airport to an international gateway hub.  We expect you’ll need to buy flights within the U.S. separately, at an estimated cost of <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">$200</strong>.</p>'+
            //           '<p><strong>Your personalized appointment</strong> <br />We don’t want you to be surprised by this context or the realities of award travel.<br /><strong>If these scenarios do NOT meet your threshold of acceptability, please cancel your upcoming appointment and use this message board to communicate any concerns or questions you may have.</strong></p>'+
            //           '<p>Otherwise, we look forward to your phone appointment in order to:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Provide you with specific flight details, including routings, airlines, and costs</li>'+
            //           '<li style="list-style-position: inside;">Collect account information in order to book the award with your approval</li>'+
            //           '<li style="list-style-position: inside;">Prepay any applicable award taxes/fees during booking and include them on your final invoice with our service fee of $199 per passenger</li>'+
            //           '</ul>'+
            //           '<p>Since award inventory changes quickly and most airlines do not allow courtesy holds, please be prepared to assess flight options and provide approval within 6 hours of your appointment.</p>'+
            //           '<p>Thanks,<br />BookYourAward Team</p>'
            // },
            // {
            //     title: 'Likelihood Aust/NZ',
            //     image: 'likelihood.png',
            //     description: '',
            //     html: '<p>We are excited to get your award request from <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">NYC</strong> to <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">Auckland</strong> underway.</p>'+
            //           '<p>Based on other requests we\'ve booked to Australia/New Zealand over the past 10 years, we want to share with you the MOST LIKELY parameters of your award flights in advance of your appointment:</p>'+
            //           '<p><strong>Routing</strong> <br />Popular nonstops from the USA to Australia/New Zealand almost always sell out to paying customers. <br />Because of this, your routing will most likely be</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">85% chance of routing through Asia</li>'+
            //           '<li style="list-style-position: inside;">10% chance of routing through the Middle East (Dubai, etc.), South America, or Europe</li>'+
            //           '<li style="list-style-position: inside;">4% chance of routing through Hawaii, Tahiti, Fiji</li>'+
            //           '<li style="list-style-position: inside;">1% chance of a nonstop from the west coast to Australia/NZ</li>'+
            //           '</ul>'+
            //           '<p>Some airlines allow stopovers at major Asian airports (e.g. Hong Kong).  If this is of interest to you, please let us know.</p>'+
            //           '<p><strong>Airlines Flown</strong> <br />All airlines we book and recommend are high quality, reputable airlines that our company members have personally flown and vetted, as well as receiving positive feedback from other clients like yourself.</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">75% chance you’ll fly on China Eastern, China Southern, Air China, China Airlines, Asiana Airlines, Cathay Pacific</li>'+
            //           '<li style="list-style-position: inside;">15% chance you’ll fly on Eva Airways, Thai Airways, Japan Airlines, Korean Airlines, All Nippon Airways, Singapore Airlines, Qatar Airways, Emirates, Etihad</li>'+
            //           '<li style="list-style-position: inside;">5% chance you’ll fly on LATAM Airlines or any European-based airline</li>'+
            //           '<li style="list-style-position: inside;">4% chance you’ll fly on Fiji Airways, Air Tahiti Nui, or Hawaiian Airlines</li>'+
            //           '<li style="list-style-position: inside;">1% chance you’ll fly on American Airlines, Delta Airlines, United Airlines, Qantas, Virgin Australia, or Air New Zealand</li>'+
            //           '</ul>'+
            //           '<p><strong>Taxes and fees</strong> <br />Award tickets include government-mandated taxes and security fees, and some airlines also collect fuel surcharges.  Your expected costs:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">25% less than $200 per person</li>'+
            //           '<li style="list-style-position: inside;">50% $200-500 per person</li>'+
            //           '<li style="list-style-position: inside;">25% more than $500 per person</li>'+
            //           '</ul>'+
            //           '<p><strong>Separately purchased airfare</strong> <br />More often than not, there is no award space from your home airport to an international gateway hub.  We expect you’ll need to buy flights within the U.S. separately, at an estimated cost of <strong style="color: #fff !important; background: #ca3f5f; display: inline-block; height: 20px; line-height: 20px; padding: 0 5px;">$200</strong>.</p>'+
            //           '<p><strong>Your personalized appointment</strong> <br />We don’t want you to be surprised by this context or the realities of award travel.  If these scenarios do NOT meet your threshold of acceptability, please cancel your upcoming appointment and use this message board to communicate any concerns or questions you may have. </p>'+
            //           '<p>Otherwise, we look forward to your phone appointment in order to:</p>'+
            //           '<ul style="padding-left:0">'+
            //           '<li style="list-style-position: inside;">Provide you with specific flight details, including routings, airlines, and costs</li>'+
            //           '<li style="list-style-position: inside;">Collect account information in order to book the award with your approval</li>'+
            //           '<li style="list-style-position: inside;">Prepay any applicable award taxes/fees during booking and include them on your final invoice with our service fee of $199 per passenger</li>'+
            //           '</ul>'+
            //           '<p>Since award inventory changes quickly and most airlines do not allow courtesy holds, please be prepared to assess flight options and provide approval within 6 hours of your appointment.</p>'+
            //           '<p>Thanks, <br />BookYourAward Team</p>'
            // },
            //{
            //    title: 'L2. Pitch Client',
            //    image:  'workflow2.png',
            //    description: 'Booking proposal table for new clients.',
            //    html:
            //        '    <div> <div><font face="Arial" size="2">Dear :</font></div> <div><font face="Arial" size="2"><br>We have customized your award search spanning up to 12 airlines and over 60 routing options.&nbsp; As your ally and advocate, please note that the chart below describes your BEST award options and key provisos, so you can make an informed decision to proceed with us.&nbsp; Rest assured, we only recommend established and personally vetted airlines with 170-180 degree seat reclines.</font></div> <div><font face="Arial" size="2"><br>Please review the flight options below and let us know which you prefer. &nbsp;<b>Then post your approval below with any questions and we will provide the specific flight details</b>, as well as&nbsp;<span>any mileage/credit card transfer or purchase instructions.&nbsp;</span><br> <font color="#5856d6"><br></font></font> <div class="gmail_extra"> <div class="gmail_quote"> <div class="gmail_extra"> <div class="gmail_quote"> <div style="word-wrap:break-word"> <div> <div><font face="Arial" size="2"><span>Award space is volatile, so please be prepared to share your account information and expedite approvals within 6-12 hours so we&nbsp;</span><span>can secure what we have found on your behalf.</span></font> </div> </div> </div> </div> </div> </div> </div> </div> <div><br></div> <div> <table cellspacing="0" cellpadding="0" style="border-collapse:collapse"> <tbody> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td colspan="2" valign="top" style="width:201.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px"><u><b>OPTION #1</b></u></font></div> </td> <td rowspan="16" valign="top" style="width:13.0px;height:351.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td colspan="2" valign="top" style="width:201.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px"><u><b>OPTION #2</b></u></font></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:right"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>ROUTING</b></font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">outbound</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">inbound</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">outbound</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">inbound</font></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">date</font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">routing</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">class of service</font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"># stops</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">connection times</font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">comments</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:right"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>COSTS</b></font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">mileage&nbsp;</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">estimated taxes/fuel surcharges</font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">airfare (segments without award space)*</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">mileage/credit card top off purchase</font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">mileage transfer b/w accounts</font></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">projected transfer time**</font></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">Book Your Award ServiceFees</font></div> </td> <td colspan="2" valign="top" style="width:201.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">$150pp round-trip</font></div> </td> <td colspan="2" valign="top" style="width:201.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">$150pp round-trip</font></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> <td colspan="5" valign="top" style="width:666.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;font-size:12px;min-height:14px"><br></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px;text-align:right"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>AWARD CONDITIONS</b></font></div> </td> <td colspan="5" valign="top" style="width:666.0px;height:13.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px"><b>Below are important provisos that our company can advise about, but not be liable for:</b></font></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>**TRANSFERS</b></font></div> </td> <td colspan="5" valign="top" style="width:666.0px;height:14.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px">Transfers may post to account on a delayed basis despite immediate confirmation, impacting award availability in the interim.</font></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:41.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>*SEPARATE TICKETS</b></font></div> </td> <td colspan="5" valign="top" style="width:666.0px;height:41.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px">Airlines are not required to re-accomodate passengers who mis-connect from a separately booked flight (ie- Purchase airfare on Airline A connecting to award ticket on Airline B).&nbsp; All flights we recommend meet a minimum connecting time for these purposes.</font></div> </td> </tr> <tr> <td valign="top" style="width:215.0px;height:28.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>LUGGAGE TRANSFERS</b></font></div> </td> <td colspan="5" valign="top" style="width:666.0px;height:28.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"> <div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px">Some airlines do not have reciprocal baggage services, so you might be subject to baggage fees on non-award flights and have to claim your baggage and recheck for award flight.</font></div> </td> </tr> </tbody> </table> </div> <div> <div> <div class="gmail_extra"> <div class="gmail_quote"> <div class="gmail_extra"> <div class="gmail_quote"> <div style="word-wrap:break-word"> <div> <div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;border-top-width:5px;border-top-style:solid;border-top-color:rgb(172,172,172);margin-top:25px;color:rgb(54,54,54)"> <div style="font-family:Helvetica;font-size:12px"><br></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> <div> <span style="color:rgb(76,119,182);font-family:Arial,Helvetica,sans-serif;font-size:20px;letter-spacing:0.08em">NEXT STEP</span><br> <font face="Arial" size="2"><font color="#363636"><br></font><b style="color:rgb(54,54,54);outline:0px"> <span style="color:rgb(34,34,34)">Let us know your preferred flight option and we will provide more information on airlines, schedules, and routings for that particular combination of flights</span>.</b> <span style="color:rgb(54,54,54)">&nbsp;</span></font> </div> <div> <font face="Arial" size="2"><span style="color:rgb(54,54,54)">Most airline programs do not allow courtesy holds, so p</span> <span style="color:rgb(54,54,54)">lease be prepared to provide account information and/or execute transfers&nbsp;</span> <u style="color:rgb(54,54,54)">as quickly as possible</u><span style="color:rgb(54,54,54)">&nbsp;to ensure we try to secure what we have proposed.</span></font> </div></div>'
            //},
            //{
            //    title: 'L2. Pitch Client One Way',
            //    image:  'workflow2.png',
            //    description: 'Booking proposal table for new clients.',
            //    html:
            //        '<div><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">Dear :</span><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">We have customized your award search spanning up to 12 airlines and over 60 routing options. &nbsp;</span><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt"><b>Right now, award space is currently only available in one direction of your travel.</b></span><span class="im"><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">As your ally and advocate, please note that the chart below describes your BEST award options and shares key provisos where applicable so you can make an informed decision to proceed with us.&nbsp; Rest assured, we only recommend established and personally vetted airlines with 170-180 degree seat reclines.</span><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"></span><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">We strongly suggest to lock in the available one-way award NOW.&nbsp; Waiting for both directions of travel to be released simultaneously&nbsp;</span><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">risks losing the existing one-way flight in the interim. &nbsp;</span><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">Due to the volatility in released award space, we book flights one direction at a time for many of our clients and this strategy has proveneffective in ultimately securing round-trip flights 99% of the time.</span><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">We will continue to look for flights for the other half of your travel and will report back on a regular basis.&nbsp; Hopefully it will bereleased soon!</span><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><span style="font-family:Arial,Helvetica,sans-serif;font-size:10pt">On the off-chance the other half of your award is never released,&nbsp;we will give you plenty of notice to expand your search flexibilityto additional dates or economy seats so you aren’t left stranded.&nbsp; Alternatively, you’ll have time to either&nbsp;purchase a one-way ticket or cancel the trip entirely (airline cancellationfees of $75-150pp may apply).&nbsp; Cancellation fees are rarely required and it is a quite modest&nbsp;“insurance policy”&nbsp;to ensure we can lock in a solid award that gets your award halfwaydone.</span></div><div><br style="font-family:Arial,Helvetica,sans-serif;font-size:13px"><bstyle="font-family:Arial,Helvetica,sans-serif;font-size:10pt"><u>We are confident in this strategy so please let us&nbsp;know if you agree and we will provide specific flight details and information onhow to proceed.</u></b></div><div><br></div><div><table cellspacing="0" cellpadding="0" style="border-collapse:collapse"><tbody><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td colspan="2" valign="top"style="width:201.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px"><u><b>OPTION #1</b></u></font></div></td><td rowspan="16" valign="top"style="width:13.0px;height:351.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td colspan="2" valign="top"style="width:201.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px"><u><b>OPTION #2</b></u></font></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:right"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>ROUTING</b></font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">outbound</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">inbound</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">outbound</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">inbound</font></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">date</font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">routing</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">class of service</font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"># stops</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">connection times</font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">comments</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:right"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>COSTS</b></font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">mileage&nbsp;</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">estimated taxes/fuel surcharges</font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">airfare (segments without award space)*</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">mileage/credit card top off purchase</font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">mileage transfer b/w accounts</font></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top"style="width:96.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">projected transfer time**</font></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td valign="top" style="width:96.0px;height:13.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)">Book Your Award Service Fees</font></div></td><td colspan="2" valign="top"style="width:201.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">$75pp(one-way) / $150pp round-trip</font></div></td><td colspan="2" valign="top"style="width:201.0px;height:14.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:center"><font face="Helvetica" color="#000000" style="font-size:12px">$75pp(one-way) / $150pp round-trip</font></div></td></tr><tr><td valign="top"style="width:215.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td><td colspan="5" valign="top" style="width:666.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;font-size:12px;min-height:14px"><br></div></td></tr><tr><td valign="top"style="width:215.0px;height:13.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px;text-align:right"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>AWARD CONDITIONS</b></font></div></td><td colspan="5" valign="top"style="width:666.0px;height:13.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px"><b>Below are important provisos that our company can advise about, but not be liablefor:</b></font></div></td></tr><tr><td valign="top"style="width:215.0px;height:14.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>**TRANSFERS</b></font></div></td><td colspan="5" valign="top"style="width:666.0px;height:14.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px">Transfers may post to account on a delayed basis despite immediate confirmation, impacting awardavailability in the interim.</font></div></td></tr><tr><td valign="top"style="width:215.0px;height:41.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>*SEPARATE TICKETS</b></font></div></td><td colspan="5" valign="top"style="width:666.0px;height:41.0px;background-color:#ececec;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px">Airlines are not required to re-accomodate passengers who mis-connect from a separately bookedflight (ie- Purchase airfare on Airline A connecting to award ticket on Airline B).&nbsp; All flights we recommend meet a minimum connecting time for these purposes.</font></div></td></tr><tr><td valign="top"style="width:215.0px;height:28.0px;background-color:#7f7f7f;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#ffffff" style="font-size:12px;color:rgb(255,255,255)"><b>LUGGAGE TRANSFERS</b></font></div></td><td colspan="5" valign="top"style="width:666.0px;height:28.0px;border-style:solid;border-width:1.0px 1.0px 1.0px 1.0px;border-color:#000000 #000000 #000000 #000000;padding:4.0px 4.0px 4.0px 4.0px"><div style="margin:0px"><font face="Helvetica" color="#000000" style="font-size:12px">Some airlines do not have reciprocal baggage services, so you might be subject to baggage fees onnon-award flights and have to claim your baggage and recheck for award flight.</font></div></td></tr></tbody></table></div><div style="font-family:Arial,Helvetica,sans-serif;font-size:10pt;border-top-width:5px;border-top-style:solid;border-top-color:rgb(172,172,172);margin-top:25px;color:rgb(54,54,54)"><h3style="font-family:Arial,Helvetica,sans-serif; font-size:20px;color:rgb(76,119,182);letter-spacing:0.08em;margin:9px 0px 0px">NEXT STEP</h3><div><br></div><div style="font-size: 10pt; font-family:Arial,Helvetica,sans-serif"><bstyle="font-family:arial;font-size:13px;outline:0px"><span style="font-family:arial,sans-serif;color:rgb(34,34,34)">Let us know your preferred flight option and we will provide more information on airlines, schedules, and routings for that particularcombination of flights</span>.</b><span>&nbsp;</span></div><div><span><br></span></div><div style="font-size: 10pt; font-family:Arial,Helvetica,sans-serif"><span>Most airline programs do not allow courtesy holds and award space is volatile. &nbsp;</span><span>Please be prepared to provide account information and/or execute transfers </span><u>as quicklyas possible</u><span>&nbsp;to ensure we try to secure what we have proposed.</span></div></div></div>'
            //},
            //{
            //    title: 'One Way Award Proposed',
            //    image:  'workflow2.png',
            //    description: '',
            //    html: '<p>Dear :' +
            //    '<p>We have begun your customized mileage award search, spanning a dozen airlines to generate up to 65 routing options, hunting for the best value awards. Right now, award space is currently only available in one direction of your travel.</p>'+
            //    '<p>We strongly suggest to lock in the available one-way award NOW. Doing so allows you to confirm premium travel in one direction and gives us maximum flexibility to continue looking for the remaining pieces of your award. Waiting for both awards to be released simultaneously could risk the existing one-way award being snatched away in the interim.</p>'+
            //    '<p>We book flights on a rolling basis for many of our clients and this strategy has proven effective in securing round-trip awards 99% of the time. In the meantime, we will stay vigilant in seeking your other award and report back regularly. Hopefully it will be released soon!</p>'+
            //    '<p>On the off chance that no award space is released, we will give you plenty of notice to either purchase a one-way ticket, expand your search to economy award seats, or cancel the trip entirely (please note airlines typically charge $150 per award to cancel and redeposit the miles into your account).</p>'+
            //    '<p><b>Please let us know if you agree with our strategy and we will provide your routing details and information on how to proceed.</b></p>'+
            //    '<p>Thanks,<br>(Staff Name)</p>'
            //},

            //{
            //    title: 'Visa',
            //    image:  'clientResponces.png',
            //    description: '',
            //    html:
            //    '<p>If you need a visa for your trip you can contact <a style="text-decoration: none; font-weight: bold; color: #4684c4; word-break: break-all;" href="http://alliedpassport.com/awardwallet">http://alliedpassport.com/awardwallet</a> to get help. Please, mention "AwardWallet" on the order form to get a $5 discount.</p>' +
            //    '<p>Thanks,<br>-Steve</p>'
            //},
            //{
            //    title: 'Client Question / Objection Staff',
            //    image:  'clientResponces.png',
            //    description: '',
            //    html:
            //    '<p>Thanks for your inquiry. We appreciate your patience while we assess your situation and can provide the most helpful and thorough response within one business day.</p>'
            //},
            //{
            //    title: 'Client Question / Objection Steve',
            //    image:  'clientResponces.png',
            //    description: '',
            //    html:
            //    '<p>Thanks for your inquiry. We appreciate your patience while we assess your situation and can provide the most helpful and thorough response within one business day.</p>'
            //},
            //{
            //    title: 'Partial award Space — Recurring Search',
            //    image:  'clientResponces.png',
            //    description: '',
            //    html:
            //    '<p>We are pleased to have found award space on the (outbound/inbound) and will stay vigilant in seeking award space on the (outbound/inbound).</p>'
            //}

        ]
    }
);