curl $CURL_OPTIONS -v --cacert rootCerts/all.pem --cert clientCerts/z1031544_prod.pem:password \
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
            <PNRID>RBTPUN</PNRID>
         </RetrieveCustomerPNRDetailsRequestItem>
      </cus:RetrieveCustomerPNRDetailsRequest>
   </soapenv:Body>
</soapenv:Envelope>
' https://pmdppartner-s.esoa.aa.com/CustHubDataServices/CustomerPNRV3_AwardWallet
