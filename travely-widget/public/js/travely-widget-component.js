class TravelyWidgetElement extends HTMLElement {
    constructor() {
        super();
        const shadow = this.attachShadow({mode: 'open'});
        // Load external CSS inside shadow DOM
        const link = document.createElement('link');
        link.setAttribute('rel', 'stylesheet');
        link.setAttribute('href', 'https://devwidget.travely.ee/est/static/css/main.css');
        shadow.appendChild(link);

        // Create container inside shadow
        const container = document.createElement('div');
        const tag = this.tagName.toLowerCase();
        if (tag === 'travely-widget-results') {
            container.classList.add('travely-widget-results');
        } else {
            container.classList.add('travely-widget-search');
            if (tag === 'travely-widget-search') {
                container.classList.add('travely-widget-search-global');
            } else if (tag === 'travely-widget-country') {
                container.classList.add('travely-widget-search-country');
            } else if (tag === 'travely-widget-best') {
                container.classList.add('travely-widget-search-best-tours');
            }
        }
        // Use element's id as container id if provided
        const containerId = this.getAttribute('id');
        if (containerId) {
            container.setAttribute('id', containerId);
            // Remove id from custom element to avoid duplicate
            this.removeAttribute('id');
        }
        shadow.appendChild(container);
        this._container = container;
    }

    connectedCallback() {
        const tag = this.tagName;
        // Retrieve data from attributes
        const key = this.getAttribute('data-key');
        const path = this.getAttribute('data-path');
        // Function to initialize TravelySearch
        const runInit = () => {
            if (tag === 'TRAVELY-WIDGET-RESULTS') {
                // Initialize results iframe
                window.TravelySearch.initIframe(this._container.id, key, path);
            } else if (tag === 'TRAVELY-WIDGET-SEARCH') {
                // Initialize search widget
                window.TravelySearch.initSearch(this._container.id, path, ['search']);
            } else {
                // Initialize country or best tours widget
                const mode = (tag === 'TRAVELY-WIDGET-COUNTRY') ? 'country' : (tag === 'TRAVELY-WIDGET-BEST') ? 'best' : '';
                const settings = {
                    containerId: this._container.id,
                    mode: mode,
                    pathToSearch: path,
                    key: key
                };
                window.TravelySearch.initSearchSeparate([settings]);
            }
        };
        // Wait for TravelySearch library to load
        if (window.TravelySearch) {
            runInit();
        } else {
            const interval = setInterval(() => {
                if (window.TravelySearch) {
                    clearInterval(interval);
                    runInit();
                }
            }, 50);
        }
    }
}

customElements.define('travely-widget-search', TravelyWidgetElement);
customElements.define('travely-widget-country', TravelyWidgetElement);
customElements.define('travely-widget-best', TravelyWidgetElement);
customElements.define('travely-widget-results', TravelyWidgetElement);
