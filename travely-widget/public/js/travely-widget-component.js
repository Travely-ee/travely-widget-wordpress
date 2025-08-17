(() => {
  // Защита от двойной инициализации файла
  if (window.__TravelyWidgetComponentLoaded__) return;
  window.__TravelyWidgetComponentLoaded__ = true;

  // --- Глобальный реестр контейнеров в Shadow DOM (только для наших id) ---
  window.__TravelyShadowContainers__ = window.__TravelyShadowContainers__ || new Map();

  // Патч для document.getElementById и querySelector только на наши id
  // (если уже пропатчено — пропускаем)
  if (!window.__TravelyWidgetDomPatched__) {
    window.__TravelyWidgetDomPatched__ = true;
    const OG_getElementById = document.getElementById.bind(document);
    const OG_querySelector = document.querySelector.bind(document);

    document.getElementById = function (id) {
      if (window.__TravelyShadowContainers__ && window.__TravelyShadowContainers__.has(id)) {
        return window.__TravelyShadowContainers__.get(id);
      }
      return OG_getElementById(id);
    };

    document.querySelector = function (selector) {
      // минимальная поддержка случая "#id"
      if (typeof selector === 'string' && selector.startsWith('#')) {
        const id = selector.slice(1);
        if (window.__TravelyShadowContainers__ && window.__TravelyShadowContainers__.has(id)) {
          return window.__TravelyShadowContainers__.get(id);
        }
      }
      return OG_querySelector(selector);
    };
  }

  // Хелпер: регистрирует элемент-контейнер под его id в глобальной карте
  function registerShadowContainer(id, el) {
    if (id && el) window.__TravelyShadowContainers__.set(id, el);
  }

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
      // ВАЖНО: id, по которому библиотека ищет контейнер, должен быть на самом контейнере.
      // Он находится в Shadow DOM, поэтому регистрируем его в глобальной карте.
      const hostId = this.getAttribute('id'); // мог быть задан на custom-element
      const containerId = hostId || this._generateId();
      container.id = containerId;
      // id оставляем ТОЛЬКО на контейнере в shadow; на host можно убрать, чтобы не было дубликата
      if (hostId) this.removeAttribute('id');

      this._shadow.appendChild(container);
      this._container = container;
      // Регистрируем контейнер, чтобы document.getElementById('#id') вернул его
      registerShadowContainer(containerId, this._container);
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
