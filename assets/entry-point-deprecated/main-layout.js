import 'jquery-boot';

var google_conversion_id = 983305737;
var google_custom_params = window.google_tag_params;
var google_remarketing_only = true;

$(function () {
  $.ajax({
      dataType: "script",
      cache: true,
      url: '//connect.facebook.net/en_US/sdk.js'
  });
  window.fbAsyncInit = function() {
      FB.init({
          appId: '75330755697',
          cookie: true,
          xfbml: true,
          version: 'v2.1' // or v2.0, v2.1, v2.0
      });
  };       
});
        
        