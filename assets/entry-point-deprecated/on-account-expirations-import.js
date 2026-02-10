import handler from 'pages/accounts/calendarImport';

const link = document.getElementById('account-expirations-import-link');

link.addEventListener('click', () => {
    handler();
});
