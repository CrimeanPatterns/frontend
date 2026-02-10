import searchBox from 'pages/businessMembers/searchBox';

const dataRoute = window.searchBoxDataRoute;
const add = window.searchBoxAdd;
const onGetUrl = window.searchBoxOnGetUrl;
const autofocusNeeded = window.searchBoxAutoFocus;

searchBox(dataRoute, add, onGetUrl);

if (autofocusNeeded === 'true') {
    setTimeout(function () {
        $('.search-form input.search-input').focus();
    }, 500);
}
