<?php

class AbRequestFormPage
{
    public static $router = 'aw_booking_add_index';

    public static $selector_Step1 = '#tab1';
    public static $selector_Step2 = '#tab2';
    public static $selector_Step3 = '#tab3';
    public static $selector_Step4 = '#tab4';
    public static $selector_autocomplete = '.ui-autocomplete';
    public static $selector_autocompleteSelect = '//ul[contains(@class, "ui-autocomplete")]//a[contains(string(), "%selector%")]';
    public static $selector_formErrorRow = 'div.row.error';

    public static $selector_NextStepButton = '#nextButton';
    public static $selector_PrevStepButton = '#previousButton';
    public static $selector_SubmitButton = '#submitButton';
    public static $selector_AddDestinationButton = '#add-destination';
    public static $selector_DeleteDestinationButton = '.del-block';
    public static $selector_AddCustomProgramButton = '#add-custom-button';
    public static $selector_DeleteCustomProgramButton = '//*[@id="booking_request_CustomPrograms"]/div/div[@class="row"][%num%+1]//*[@class="delete"]';
    public static $selector_AddBookerAccount = '#add-account';

    public static $selector_Select2Input = '#select2-drop input';
    public static $selector_Select2Result = '//*[@role="listbox"]//*[contains(string(), "%selector%")]';

    public static $selector_CabinFirst = 'label[for="booking_request_CabinFirst"]';
    public static $selector_NumberPassengers = '#booking_request_NumberPassengers';

    public static $selector_PassengerSearchSelect2 = '(//*[@id="passenger-list"]//*[contains(@id, "Useragent")]/a)[%num%+1]';
    public static $selector_PassengerNewUser = '(//*[@id="passenger-list"]//label[contains(@for, "_new_member")])[%num%+1]';

    public static $selector_PassengerSelect = '(//*[@id="passenger-list"]//*[contains(@id, "_templates")])[%num%+1]';
    public static $selector_Passenger = '//*[@id="passenger-list"]//fieldset[%num%+1]';
    public static $selector_PassengerFirstName = '(//*[@id="passenger-list"]//*[contains(@id, "FirstName")])[%num%+1]';
    public static $selector_PassengerLastName = '(//*[@id="passenger-list"]//*[contains(@id, "LastName")])[%num%+1]';
    public static $selector_PassengerMiddleName = '(//*[@id="passenger-list"]//*[contains(@id, "MiddleName")])[%num%+1]';
    public static $selector_PassengerBirthday = '(//*[@id="passenger-list"]//*[contains(@id, "Birthday_datepicker")])[%num%+1]';
    public static $selector_PassengerCitizenUS = '(//*[@id="passenger-list"]//label[contains(@for, "Nationality_choice_0")])[%num%+1]';
    public static $selector_PassengerCitizenNotUS = '(//*[@id="passenger-list"]//label[contains(@for, "Nationality_choice_1")])[%num%+1]';
    public static $selector_PassengerCitizen = '(//*[@id="passenger-list"]//*[contains(@id, "Nationality_text")])[%num%+1]';
    public static $selector_PassengerGenderMale = 'label[for=booking_request_Passengers_%num%_Gender_0]';
    public static $selector_PassengerGenderFemale = 'label[for=booking_request_Passengers_%num%_Gender_1]';

    public static $selector_SegmentType = '(//*[@id="segment-list"]//*[@class="trip-details"])[%num%+1]';
    public static $selector_SegmentOneWay = '(//*[@id="segment-list"]//label[contains(@for, "RoundTrip_0")])[1]';
    public static $selector_SegmentRoundTrip = '(//*[@id="segment-list"]//label[contains(@for, "RoundTrip_1")])[1]';
    public static $selector_SegmentMulti = '(//*[@id="segment-list"]//label[contains(@for, "RoundTrip_2")])[1]';
    public static $selector_Segment = '//*[@id="segment-list"]//fieldset[%num%+1]';
    public static $selector_SegmentFrom = '(//*[@id="segment-list"]//*["_Dep" = substring(@id, string-length(@id) - 3)])[%num%+1]';
    public static $selector_SegmentTo = '(//*[@id="segment-list"]//*["_Arr" = substring(@id, string-length(@id) - 3)])[%num%+1]';
    public static $selector_SegmentDepIdeal = '(//*[@id="segment-list"]//*["_DepDateIdeal_datepicker" = substring(@id, string-length(@id) - 23)])[%num%+1]';
    public static $selector_SegmentDepFlex = '(//*[@id="segment-list"]//*["_DepDateFlex_0" = substring(@id, string-length(@id) - 13)])[%num%+1]/following-sibling::span';
    public static $selector_SegmentRetFlex = '(//*[@id="segment-list"]//*["_ReturnDateFlex_0" = substring(@id, string-length(@id) - 16)])[%num%+1]/following-sibling::span';
    public static $selector_SegmentDepFrom = '(//*[@id="segment-list"]//*["_DepDateFrom_datepicker" = substring(@id, string-length(@id) - 22)])[%num%+1]';
    public static $selector_SegmentDepTo = '(//*[@id="segment-list"]//*["_DepDateTo_datepicker" = substring(@id, string-length(@id) - 20)])[%num%+1]';
    public static $selector_SegmentReturnIdeal = '(//*[@id="segment-list"]//*["_ReturnDateIdeal_datepicker" = substring(@id, string-length(@id) - 26)])[%num%+1]';
    public static $selector_SegmentReturnFrom = '(//*[@id="segment-list"]//*["_ReturnDateFrom_datepicker" = substring(@id, string-length(@id) - 25)])[%num%+1]';
    public static $selector_SegmentReturnTo = '(//*[@id="segment-list"]//*["_ReturnDateTo_datepicker" = substring(@id, string-length(@id) - 23)])[%num%+1]';

    public static $selector_paymentCash = 'label[for=booking_request_paymentCash]';
    public static $selector_Accounts = '#account-selector';
    public static $selector_CustomPrograms = '#booking-custom-programs';
    public static $selector_CustomProgram = '//*[@id="booking_request_CustomPrograms"]/div/div[@class="row"][%num%+1]';
    public static $selector_CustomProgramName = '(//*[@id="booking_request_CustomPrograms"]//*["[Name]" = substring(@name, string-length(@name) - 5)])[%num%+1]';
    public static $selector_CustomProgramOwner = '(//*[@id="booking_request_CustomPrograms"]//*["[Owner]" = substring(@name, string-length(@name) - 6)])[%num%+1]';
    public static $selector_CustomProgramStatus = '(//*[@id="booking_request_CustomPrograms"]//*["[EliteStatus]" = substring(@name, string-length(@name) - 12)])[%num%+1]';
    public static $selector_CustomProgramBalance = '(//*[@id="booking_request_CustomPrograms"]//*["[Balance]" = substring(@name, string-length(@name) - 8)])[%num%+1]';
    public static $selector_CustomProgramStatusInput = '(//*[@id="booking_request_CustomPrograms"]//input["[EliteStatus]" = substring(@name, string-length(@name) - 12)])[%num%+1]';
    public static $selector_CustomProgramStatusSelect = '(//*[@id="booking_request_CustomPrograms"]//select["[EliteStatus]" = substring(@name, string-length(@name) - 12)])[%num%+1]';

    public static $selector_BookerAccounts = '#booking-account-list';
    public static $selector_BookerAccount = '(//*[@id="booking-account-list"]//tr[not(./th)])[%num%+1]';
    public static $selector_BookerAccountHolder = '(//*[@id="booking-account-list"]//tr[not(./th)]//*["_UserAgentID" = substring(@id, string-length(@id) - 11)]/a)[%num%+1]';
    public static $selector_BookerAccountChoice = '(//*[@id="booking-account-list"]//tr[not(./th)]//*["_AccountID" = substring(@id, string-length(@id) - 9)]/a)[%num%+1]';

    public static $selector_User = '//*[@id="tab1"]//*[@data-key="User"]//div[contains(@id, "_User")]/a';
    public static $selector_FullName = 'booking_request[ContactName]';
    public static $selector_FirstName = 'booking_request[User][firstname]';
    public static $selector_LastName = 'booking_request[User][lastname]';
    public static $selector_Email = 'booking_request[User][email][Email]';
    public static $selector_ConfirmEmail = 'booking_request[User][email][ConfirmEmail]';
    public static $selector_ContactEmail = 'booking_request[ContactEmail]';
    public static $selector_Phone = 'booking_request[User][phone1]';
    public static $selector_ContactPhone = 'booking_request[ContactPhone]';
    public static $selector_PriorNo = 'label[for="booking_request_PriorSearchResults_choice_0"]';
    public static $selector_PriorYes = 'label[for="booking_request_PriorSearchResults_choice_1"]';
    public static $selector_PriorText = 'booking_request[PriorSearchResults][text]';
    public static $selector_Notes = 'booking_request[Notes]';
    public static $selector_SendEmails = '//label[@for="booking_request_SendMailUser"]';

    public static $selector_Login = 'booking_request[User][login]';
    public static $selector_Password = 'booking_request[User][pass][Password]';
    public static $selector_ConfirmPassword = 'booking_request[User][pass][ConfirmPassword]';
    public static $selector_Agree = 'label[for="booking_request_Terms"]';

    public static $selector_showLoginPopup = '#show_login_popup';
    public static $selector_loginPopup = '#loginPopup';
    public static $selector_loginInput = '//*[@id="loginPopup"]//input[@name="login"]';
    public static $selector_passwordInput = '//*[@id="loginPopup"]//input[@name="password"]';
    public static $selector_loginSubmitButton = '#booking-login-button';

    public static $selector_restorePopup = '#restorePopup';
    public static $selector_restoreYes = 'Recover';
}
