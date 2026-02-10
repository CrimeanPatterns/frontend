// deprecated
export function listenAddNewPersonPopup(): void {
    $(document).on('click', '.js-add-new-person, #add-person-btn, .js-persons-menu a[href="/user/connections"].add', function (e) {
        e.preventDefault();

        // eslint-disable-next-line @typescript-eslint/no-unsafe-call, @typescript-eslint/no-var-requires
        require('../../../../web/assets/awardwalletnewdesign/js/pages/agent/addDialog')();
    });
}