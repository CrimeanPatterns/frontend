curl -v --cacert rootCerts/all.pem --cert aa-stage.pem:password \
--capath /www/awardwallet/util/aaTest/rootCerts --key key.pem \
-H "SOAPAction: http://www.CustomerPNRV3.com/RetrieveCustomerPNRDetails" \
-H "Content-Type: text/xml; charset=utf-8" \
-H "userid: AWRDWLT" \
-H "functionalid: Z1031544" \
--data '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cus="http://custhub.ct.aa.com/CustomerPNRV3">
   <soapenv:Header/>
  <soapenv:Body>
      <cus:RetrieveCustomerPNRDetailsRequest>
         <RetrieveCustomerPNRDetailsRequestItem>
            <PNRID>GQXNEQ</PNRID>
         </RetrieveCustomerPNRDetailsRequestItem>
      </cus:RetrieveCustomerPNRDetailsRequest>
   </soapenv:Body>
</soapenv:Envelope>
' https://pmdppartner-s.stage.esoa.aa.com:443/CustHubDataServices/CustomerPNRV3_AwardWallet

#GQGRWB FHLQXG , GQXNEQ, GQXNEQ
