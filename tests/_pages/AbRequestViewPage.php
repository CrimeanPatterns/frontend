<?php

class AbRequestViewPage
{
    public static $route = 'aw_booking_view_index';

    public static $selector_page = '#requestView';
    //    static public $selector_requestId = '/data-id=[\'\"]([\d]+)/ims';
    public static $selector_requestId = '.booker-title strong';
    public static $selector_successSaveRequestPopup = '#not_verified_popup';
    //    static public $selector_successSaveRequestPopupClose = '//*[@id="after_save_popup"]/..//*[contains(@class, "ui-dialog-buttonset")]//button';
    public static $selector_emailConfirmationPopup = 'Send Another Confirmation Email';
    public static $selector_status = '//*[@data-status="%status%"]';
    public static $selector_createMessage = '#create-message';
    public static $selector_cancelLink = 'Cancel';
    public static $selector_editLink = 'Edit';
    public static $selector_passengers = '//*[@id="table-travelers"]/tbody/tr';
    public static $selector_destinations = '//*[@id="table-destinations"]/tbody/tr';
    public static $selector_miles = '//*[@id="lp-table"]/tbody/tr';
    public static $selector_contacts = '#contact-table';
    public static $selector_commonMessages = '#commonMessages';
    public static $selector_commonMessageEdit = '//*[@id="commonMessages"]/div/table[@id][%num%]//*[@class="js-message-edit"]';
    public static $selector_commonMessageDelete = '//*[@id="commonMessages"]/div/table[@id][%num%]//*[@class="js-message-delete"]';
    public static $selector_internalMessages = '#internalMessages';
    public static $selector_internalMessageEdit = '//*[@id="internalMessages"]/div/table[@id][%num%]//*[@class="js-message-edit"]';
    public static $selector_internalMessageDelete = '//*[@id="internalMessages"]/div/table[@id][%num%]//*[@class="js-message-delete"]';

    public static $selector_userPostInput = 'booking_request_message[Post]';
    public static $selector_userPostSendButton = '#messageFormSubmitButton';
    public static $selector_bookerPostInput = 'booking_request_message_Post';
    public static $selector_bookerPostInternal = 'label[for="booking_request_message_Internal"]';

    public static $selector_userEditInput = '#booking_request_edit_message_Post';
    public static $selector_bookerEdit = '#editMessageForm';
    public static $selector_bookerEditInput = 'booking_request_edit_message_Post';
    public static $selector_userEditSendButton = '#messageEditFormSubmitButton';

    public static $selector_deleteMessagePopup = '#delete_message_popup';
    public static $selector_deleteMessagePopupYes = 'Yes, Delete';
    public static $selector_deleteMessagePopupNo = 'No';

    public static $selector_invoiceCalcTotals = '#calc-totals';
    public static $selector_invoiceButton = '//*[@id="create-invoice-btn"]';
    public static $selector_invoiceForm = '#create-invoice';
    public static $selector_invoiceDesc = '(//*["_description" = substring(@id, string-length(@id) - 11)])[%num%]';
    public static $selector_invoiceQuantity = '(//*["_quantity" = substring(@id, string-length(@id) - 8)])[%num%]';
    public static $selector_invoicePrice = '(//*["_price" = substring(@id, string-length(@id) - 5)])[%num%]';
    public static $selector_invoiceDiscount = '(//*["_discount" = substring(@id, string-length(@id) - 8)])[%num%]';
    public static $selector_invoicePriceTotal = '(//*[contains(@class, "price-item")])[%num%]';
    public static $selector_invoiceDiscountTotal = '(//*[contains(@class, "total-item")])[%num%]';
    public static $selector_invoiceTotal = '//*[@id="calc-totals"]/div';
    public static $selector_invoiceRow = '(//*[@id="items-list"]//tr[@data-key])[%num%]';
    public static $selector_invoiceAddItemButton = '//a[@id="add-item-link"]';

    public static $selector_invoiceAddProgramButton = '//*[@id="add-miles-block"]/a';
    public static $selector_invoiceDeleteProgramButton = '(//*[@id="miles-list"]//a[contains(@class, "delete")])[%num%]';
    public static $selector_invoiceProgram = '(//*[@id="miles-list"]//tr)[%num%]';
    public static $selector_invoiceProgramName = '(//*["_CustomName" = substring(@id, string-length(@id) - 10)])[%num%]';
    public static $selector_invoiceAccountHolder = '(//*["_Owner" = substring(@id, string-length(@id) - 5)])[%num%]';
    public static $selector_invoiceMiles = '(//*["_Balance" = substring(@id, string-length(@id) - 7)])[%num%]';
    public static $selector_invoiceSendButton = '//*[@id="create-invoice"]//*[contains(@class, "submitButton")]';
    public static $selector_invoiceHasError = '//*[@id = "create-invoice"]//*[contains(@class, "error") and contains(@class, "message")]';
    public static $selector_invoiceMessage = '(//*[@id="commonMessages"]//*[@class="invoice_view_container"])[%num%]';
    public static $selector_invoiceMessageUnpaid = '(//*[@id="commonMessages"]//*[@class="invoice_status_unpaid"])[%num%]';

    public static $selector_seatsButton = '//*[@data-target="seat-assignments"]';
    public static $selector_seatsForm = '//*[@id="seat-assignments"]';
    public static $selector_seatsProgram = '(//*[@id="seat-assignments-list"]//tr)[%num%]';
    public static $selector_seatsAirline = '(//*["_Provider" = substring(@id, string-length(@id) - 8)])[%num%]';
    public static $selector_seatsPhoneSelect = '//*[@id="seat-assignments"]//table//tr[%num%]//*[@class="CustomProgram_Phone"]/select';
    public static $selector_seatsPhoneInput = '//*[@id="seat-assignments"]//table//tr[%num%]//*[@class="CustomProgram_Phone"]/input';
    public static $selector_seatsAddButton = '//*[@id="seat-assignments"]//*[@class="add-block"]/a';
    public static $selector_seatsSendButton = '//*[@id="seat-assignments"]//*[contains(@class, "submitButton")]';
}
