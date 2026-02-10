if (response.headers.valueOf('X-XSRF-FAILED') == 'true') {
    const token = response.headers.valueOf('X-XSRF-TOKEN');
    client.log("CSRF token: " + token + ' saved');
    client.global.set("csrfToken", token);
} else {
    client.log("No CSRF token found");
}
