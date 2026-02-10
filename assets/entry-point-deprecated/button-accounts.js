import 'jquery-boot';

$(window).on('totalAccounts.update', function (event, data) {
  $('#account-btn-counter').text(data);
});
