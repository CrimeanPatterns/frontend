curl -v --cacert rootCerts/all.pem --cert aa-stage.pem:password \
--capath /www/awardwallet/util/aaTest/rootCerts --key key.pem \
-H "SOAPAction: http://www.LoyaltyMemberReservationInfoServiceV2.com/RetrieveCustomerReservationList" \
-H "Content-Type: text/xml; charset=utf-8" \
-H "userid: AWRDWLT" \
-H "functionalid: Z1031544" \
--data '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <cxf:RetrieveCustomerReservationList xmlns:cxf="http://cxfws.jee.customerservices.ecdb.americanairlines.com/">
            <request>
                <asyncProcessMaxThreadCount>1</asyncProcessMaxThreadCount>
                <asyncTimeoutValue>1</asyncTimeoutValue>
                <maxDurationOfThreadCountAtMax>1</maxDurationOfThreadCountAtMax>
                <performAsyncProcess>false</performAsyncProcess>
                <loyaltyCompany>AA</loyaltyCompany>
                <loyaltyNumber>836VWC8</loyaltyNumber>
                <PNRCreationDate>2015-03-02T16:07:55.949-06:00</PNRCreationDate>
                <pnrFromDate>0001-02-01T16:07:55.949-06:00</pnrFromDate>
                <pnrToDate>9001-01-31T16:07:55.949-06:00</pnrToDate>
            </request>
        </cxf:RetrieveCustomerReservationList>
    </soapenv:Body>
</soapenv:Envelope>
' https://pmdppartner-s.stage.esoa.aa.com:443/CustHubDataServices/LoyaltyMemberReservationInfoV2_AwardWallet 

#F172M02, 0A01XE8, 1Y06RJ6
