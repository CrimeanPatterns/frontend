define('routing', ['router', 'json!routes'], function (router, routes) {
    router.setRoutingData(routes);
    window.Routing = router;
});

