(function( $ ) {
    $( window ).load(function() {
        if (window.travelyWidgetInitialized) return;
        if (Array.isArray(window.travelyWidgetInstances) && window.travelyWidgetInstances.length > 0) {
            const settings = [];
            let path = '';
            let key = '';
            window.travelyWidgetInstances.forEach(function(cfg) {
                if (cfg.path) {
                    path = cfg.path;
                }
                if (cfg.key) {
                    key = cfg.key;
                }
                if (cfg.mode === 'result') {
                    window.TravelySearch.initIframe(cfg.containerId, key, path);
                    return;
                }
                settings.push({
                    ...cfg
                });
            });
            window.TravelySearch.initSearchSeparate(settings);
        }
        window.travelyWidgetInitialized = true;
    });
})( jQuery );
