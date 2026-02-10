curl -v -H "SOAPAction: CheckAccount" -H "Content-Type: text/xml; charset=utf-8" --data '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ns1="https://service.awardwallet.com/wsdl/" xmlns:ns2="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
	<SOAP-ENV:Header>
		<ns2:Security>
			<ns2:UsernameToken>
				<ns2:Username>test</ns2:Username>
				<ns2:Password>vdgFAVt6FWCqRTLk6CGcjkf</ns2:Password>
			</ns2:UsernameToken>
		</ns2:Security>
	</SOAP-ENV:Header>
	<SOAP-ENV:Body>
		<ns1:CheckAccountRequest>
			<APIVersion>4</APIVersion>
			<Provider>marriott</Provider>
			<Login>john1000@yahoo.com</Login>
			<Login2 />
			<Login3 />
			<Password>points</Password>
			<CheckNow>true</CheckNow>
			<Timeout>60</Timeout>
			<Priority>5</Priority>
			<CallbackURL />
			<Retries>0</Retries>
			<ParseItineraries>true</ParseItineraries>
			<UserID>123</UserID>
			<AccountID>456</AccountID>
			<MarkUsedCoupons xsi:nil="true" />
			<BrowserState xsi:nil="true" />
			<ParseHistory>false</ParseHistory>
			<HistoryVersion xsi:nil="true" />
			<HistoryLastDate xsi:nil="true" />
			<ParseFiles>false</ParseFiles>
			<FilesVersion xsi:nil="true" />
			<FilesLastDate xsi:nil="true" />
			<Options xsi:nil="true" />
		</ns1:CheckAccountRequest>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
' https://service.awardwallet.com/wsdl/v4/
