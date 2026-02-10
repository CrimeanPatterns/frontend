curl -v --cacert rootCerts/all.pem --cert aa-prod.pem:password \
--capath /www/awardwallet/util/aaTest/rootCerts --key key.pem \
-H "SOAPAction: RetrieveCustomerPNRDetails" \
-H "Content-Type: text/xml; charset=utf-8" \
-H "userid: AWRDWLT" \
-H "functionalid: Z1031544" \
--data '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:oas="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:cus="http://custhub.ct.aa.com/CustomerPNRV2">
   <soapenv:Body>
      <cus:RetrieveCustomerPNRDetailsRequest>
         <RetrieveCustomerPNRDetailsRequestItem>
            <PNRID>LDDMCS</PNRID>
         </RetrieveCustomerPNRDetailsRequestItem>
      </cus:RetrieveCustomerPNRDetailsRequest>
   </soapenv:Body>
</soapenv:Envelope>
' https://pmdppartner-s.qa.esoa.aa.com/CustHubDataServices/CustomerPNRV3_AwardWallet
