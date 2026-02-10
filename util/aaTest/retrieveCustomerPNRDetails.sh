# --proxy 192.168.10.1:3129 \
curl -v --cacert /usr/keys/aa-root-prod.pem --cert /usr/keys/aa-client-prod.pem:password \
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
            <PNRID>ECFWDR</PNRID>
         </RetrieveCustomerPNRDetailsRequestItem>
      </cus:RetrieveCustomerPNRDetailsRequest>
   </soapenv:Body>
</soapenv:Envelope>
' https://pmdppartner-s.esoa.aa.com/CustHubDataServices/CustomerPNRV3_AwardWallet
