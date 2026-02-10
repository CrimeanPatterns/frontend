# ssh -D 192.168.10.1:5555 test.awardwallet.com
# export CURL_OPTIONS="--socks5 192.168.10.1:5555"
curl $CURL_OPTIONS -v --cacert rootCerts/all-stage.pem  \
-H "SOAPAction: http://www.LoyaltyMemberSecurityV3.com/ValidateLoyaltyCredentials" \
-H "Content-Type: text/xml; charset=utf-8" \
-H "userid: AWRDWLT" \
-H "functionalid: Z1031544" \
--data '
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loy="http://custhub.ct.aa.com/LoyaltyMemberSecurityV3">
   <soapenv:Header>
      <oas:Security xmlns:oas="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
         <UsernameToken wsu:Id="UsernameToken-24" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <Username>Z1031544</Username>
            <Password>IX3S*LIVHQdWE</Password>
          </UsernameToken>
      </oas:Security>
   </soapenv:Header>
   <soapenv:Body>
      <loy:ValidateLoyaltyCredentialsRequest>
         <ClientID>SBLSOA</ClientID>
         <WsdlVersion>3.0</WsdlVersion>
         <ServiceType>LoyaltyMemberSecurity</ServiceType>
         <ApplicationID>SBLSOA</ApplicationID>
         <ValidateLoyaltyCredentialsRequestItem>
            <LoginPassword>sffp2015!</LoginPassword>
            <!--Zero or more repetitions:-->
            <ExtraIdData>
               <IdType>LASTNAME</IdType>
               <IdValue>TORRES</IdValue>
            </ExtraIdData>
            <!--Optional:-->
            <LoginId>09000013095</LoginId>
            <!--Optional:-->

         </ValidateLoyaltyCredentialsRequestItem>
         <AuditID>SBLSOA</AuditID>
      </loy:ValidateLoyaltyCredentialsRequest>
   </soapenv:Body>
</soapenv:Envelope>
' https://partner.stage.xsoa.aa.com/CustHubServices/LoyaltyMemberSecurityV3/ValidateLoyaltyCredentials
