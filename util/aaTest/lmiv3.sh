curl -v --cacert rootCerts/all.pem --cert z1031544_prod.pem:password \
-H "SOAPAction: http://www.LoyaltyMemberReservationInfoServiceV2.com/RetrieveCustomerReservationList" \
-H "Content-Type: text/xml; charset=utf-8" \
-H "userid: AWRDWLT" \
-H "functionalid: Z1031544" \
--data '
<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
   <Body>
      <RetrieveCustomerReservationList xmlns="http://cxfws.jee.customerservices.ecdb.americanairlines.com/">
         <request xmlns="">
            <asyncProcessMaxThreadCount>0</asyncProcessMaxThreadCount>
            <asyncTimeoutValue>0</asyncTimeoutValue>
            <maxDurationOfThreadCountAtMax>0</maxDurationOfThreadCountAtMax>
            <performAsyncProcess>false</performAsyncProcess>
            <bookedAacomOrAasegmentOnly>false</bookedAacomOrAasegmentOnly>
            <clientCode>AACOM</clientCode>
            <loyaltyCompany>AA</loyaltyCompany>
            <loyaltyNumber>L750N08</loyaltyNumber>
            <retrieveLimitedChangePnr>true</retrieveLimitedChangePnr>
            <requestedListCount>0</requestedListCount>
         </request>
      </RetrieveCustomerReservationList>
   </Body>
</Envelope>
' https://pmdppartner-s.esoa.aa.com/CustHubDataServices/LoyaltyMemberReservationInfoV2_AwardWallet 
