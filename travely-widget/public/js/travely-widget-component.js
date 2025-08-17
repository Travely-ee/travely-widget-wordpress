(() => {
  // Защита от двойной инициализации файла
  if (window.__TravelyWidgetComponentLoaded__) return;
  window.__TravelyWidgetComponentLoaded__ = true;

  class TravelyWidgetBase extends HTMLElement {
    constructor() {
      super();
      this._shadow = this.attachShadow({ mode: 'open' });

      // Подключаем CSS внутрь Shadow DOM
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://devwidget.travely.ee/est/static/css/main.css';
      this._shadow.appendChild(link);

      // Создаем контейнер
      const container = document.createElement('div');
      if (this.mode === 'results') {
        container.classList.add('travely-widget-results');
      } else {
        container.classList.add('travely-widget-search');
        if (this.mode === 'search') {
          container.classList.add('travely-widget-search-global');
        } else if (this.mode === 'country') {
          container.classList.add('travely-widget-search-country');
        } else if (this.mode === 'best') {
          container.classList.add('travely-widget-search-best-tours');
        }
      }

      // Пробрасываем id кастомного элемента в контейнер
      const containerId = this.getAttribute('id') || this._generateId();
      container.id = containerId;
      // Убираем id у host-элемента, чтобы не было дублирования в основном DOM
      if (this.hasAttribute('id')) this.removeAttribute('id');

      this._shadow.appendChild(container);
      this._container = container;
    }

    get mode() {
      // У подклассов переопределено статическое свойство
      return this.constructor.__mode || 'search';
    }

    _generateId() {
      return `travely-widget-${this.mode}-${Math.random().toString(36).slice(2, 9)}`;
    }

    connectedCallback() {
      const key = this.getAttribute('data-key') || '';
      const path = this.getAttribute('data-path') || '';

      const runInit = () => {
        try {
          if (!window.TravelySearch) return;

          switch (this.mode) {
            case 'results':
              if (typeof window.TravelySearch.initIframe === 'function') {
                window.TravelySearch.initIframe(this._container.id, key, path);
              }
              break;
            case 'search':
              if (typeof window.TravelySearch.initSearch === 'function') {
                window.TravelySearch.initSearch(this._container.id, path, ['search']);
              }
              break;
            case 'country':
            case 'best': {
              if (typeof window.TravelySearch.initSearchSeparate === 'function') {
                const settings = [{
                  containerId: this._container.id,
                  mode: this.mode,
                  pathToSearch: path,
                  key: key
                }];
                window.TravelySearch.initSearchSeparate(settings);
              }
              break;
            }
          }
        } catch (e) {
          // не ломаем страницу, просто логируем
          console.error('Travely Widget init error:', e);
        }
      };

      // Ждем, пока загрузится библиотека TravelySearch
      if (window.TravelySearch) {
        runInit();
      } else {
        const t0 = Date.now();
        const interval = setInterval(() => {
          if (window.TravelySearch) {
            clearInterval(interval);
            runInit();
          } else if (Date.now() - t0 > 10000) {
            // фейл-сейф: перестаем ждать через 10с
            clearInterval(interval);
          }
        }, 50);
      }
    }
  }

  // Подклассы с уникальными конструкторами
  class TravelyWidgetSearch extends TravelyWidgetBase {}
  TravelyWidgetSearch.__mode = 'search';

  class TravelyWidgetCountry extends TravelyWidgetBase {}
  TravelyWidgetCountry.__mode = 'country';

  class TravelyWidgetBest extends TravelyWidgetBase {}
  TravelyWidgetBest.__mode = 'best';

  class TravelyWidgetResults extends TravelyWidgetBase {}
  TravelyWidgetResults.__mode = 'results';

  // Регистрируем, если еще не зарегистрированы
  if (!customElements.get('travely-widget-search')) {
    customElements.define('travely-widget-search', TravelyWidgetSearch);
  }
  if (!customElements.get('travely-widget-country')) {
    customElements.define('travely-widget-country', TravelyWidgetCountry);
  }
  if (!customElements.get('travely-widget-best')) {
    customElements.define('travely-widget-best', TravelyWidgetBest);
  }
  if (!customElements.get('travely-widget-results')) {
    customElements.define('travely-widget-results', TravelyWidgetResults);
  }
})();
