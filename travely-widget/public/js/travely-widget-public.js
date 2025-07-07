(function( $ ) {
    $( window ).load(function() {
        if (window.travelyWidgetInitialized) return;
        if (Array.isArray(window.travelyWidgetInstances) && window.travelyWidgetInstances.length > 0) {
            const settings = [];

            window.travelyWidgetInstances.forEach(function(cfg) {
                if (cfg.mode === 'result') {
                    window.TravelySearch.initIframe(cfg.containerId, cfg.key, cfg.path);
                    return;
                }
                if (cfg.mode === 'search') {
                    window.TravelySearch.initSearch(cfg.containerId, cfg.path, ['search']);
                    return;
                }
                settings.push({
                    containerId: cfg.containerId,
                    mode: cfg.mode,
                    pathToSearch: cfg.path,
                    key: cfg.key,
                });
            });

            window.TravelySearch.initSearchSeparate(settings);
        }
        window.travelyWidgetInitialized = true;
    });
})( jQuery );
