# Travely Widget WordPress Plugin

- [English](#english)
- [Eesti](#eesti)
- [Русский](#russian)

---

<a id="english"></a>
## English

### Installation

1. Download the `travely-widget-v*.zip` plugin archive from the [GitHub releases](https://github.com/Travely-ee/travely-widget-wordpress/releases).
2. Log in to the WordPress admin panel.
3. Go to **Plugins -> Add New -> Upload Plugin**.
4. Upload the archive and activate the plugin.

### Configuration

1. Go to **Settings -> Travely Widget** to configure the widget.
2. Enter your API key and the search results page path.
3. Create a new page with the `[travely-widget-results]` shortcode and the slug/path that you specified in the settings. This page will display the search results.

`Default language` is the fallback widget language used when the language cannot be detected automatically.

`Force default language` always uses the selected default language and ignores URL language, Polylang/WPML language and WordPress locale.

When `Force default language` is disabled, the plugin resolves the page language in this order:

1. URL `?language=`
2. URL `?lang=`
3. Polylang current language
4. WPML current language
5. WordPress locale
6. Default language

The language is selected at page level. Shortcodes on the same page use the same resolved language.

The `language` shortcode attribute overrides automatic language detection unless `Force default language` is enabled. Use `language="auto"` to keep the automatic detection order.

Shortcode examples:

```text
[travely-widget-search language="auto"]
[travely-widget-search language="rus"]
[travely-widget-search language="eng"]
[travely-widget-search language="est"]
[travely-widget-search language="lav"]

[travely-widget-search-country language="eng"]
[travely-widget-country language="rus"]
[travely-widget-results language="est"]
```

Do not mix different Travely Widget languages on the same page. The widget is loaded as a UMD bundle and exposes a global `window.TravelySearch` object, so only one language build can be safely used per page.

Search results path mode:

- `Single path for all languages` uses the common `Path to Search` setting for every resolved language.
- `Separate path for each language` uses `Estonian search path`, `English search path`, `Russian search path` and `Latvian search path`.

`Path mode` also controls which path fields are shown in the settings page. In `Single path for all languages`, only the common `Path to Search` field is shown. In `Separate path for each language`, only the `Language-specific paths` section is shown.

When a language-specific path is empty, the plugin falls back to the common `Path to Search`. If the common path is empty, it falls back to `/tour-search`.

The path is selected after the final language is resolved. For example, `[travely-widget-search language="rus"]` uses the Russian search path, `[travely-widget-search language="est"]` uses the Estonian search path, and `language="auto"` first resolves the page language and then chooses the matching path. When `Force default language` is enabled, the path is selected for the forced default language.

`Results background` sets the background colour of the embedded results iframe. Leave empty for transparent. Supported values: `transparent`, HEX (`#fff`, `#ffffff`), `rgb()`, `rgba()`, `hsl()`, `hsla()`.

The `background` shortcode attribute on `[travely-widget-results]` overrides the global setting for that individual shortcode:

```text
[travely-widget-results background="transparent"]
[travely-widget-results background="#ffffff"]
[travely-widget-results background="rgba(255,255,255,0.9)"]
```

Supported widget languages:

- `est` - Estonian
- `eng` - English
- `rus` - Russian
- `lav` - Latvian

URL examples:

- `?language=est`
- `?language=eng`
- `?language=rus`
- `?language=lav`
- `?lang=et`
- `?lang=en`
- `?lang=ru`
- `?lang=lv`

For advanced multilingual path customization, use the `travely_widget_path_to_search` filter. The filter runs after the final path is selected:

```php
add_filter( 'travely_widget_path_to_search', function ( $path, $language ) {
    switch ( $language ) {
        case 'eng':
            return '/en/tour-search';
        case 'rus':
            return '/ru/tour-search';
        case 'lav':
            return '/lv/tour-search';
        case 'est':
        default:
            return '/tour-search';
    }
}, 10, 2 );
```

### Usage

Use the `[travely-widget-search]`, `[travely-widget-country]`, and `[travely-widget-search-country]` shortcodes in any post or page to display the Travely widgets.

---

<a id="eesti"></a>
## Eesti

### Paigaldamine

1. Laadige alla plugina arhiiv `travely-widget-v*.zip` [GitHubi väljalasete lehelt](https://github.com/Travely-ee/travely-widget-wordpress/releases).
2. Logige sisse WordPressi halduspaneeli.
3. Avage **Pluginad -> Lisa uus -> Laadi plugin üles**.
4. Laadige arhiiv üles ja aktiveerige plugin.

### Seadistamine

1. Avage **Seaded -> Travely Widget**, et vidinat seadistada.
2. Sisestage oma API-võti ja otsingutulemuste lehe tee.
3. Looge uus leht lühikoodiga `[travely-widget-results]` ning kasutage sama slug'i/lehe teed, mille määrasite seadetes. Sellel lehel kuvatakse otsingutulemused.

`Default language` ehk vaikimisi keel kasutatakse siis, kui keelt ei õnnestu automaatselt tuvastada.

`Force default language` ehk alati vaikimisi keele kasutamine tähendab, et plugin kasutab alati valitud vaikimisi keelt ning eirab URL-i keelt, Polylang/WPML-i keelt ja WordPressi lokaati.

Kui `Force default language` ei ole sisse lülitatud, määrab plugin lehe keele selles järjekorras:

1. URL `?language=`
2. URL `?lang=`
3. Polylangi praegune keel
4. WPML-i praegune keel
5. WordPressi lokaat
6. Default language

Keel valitakse lehe tasemel. Kõik sama lehe lühikoodid kasutavad sama tuvastatud keelt.

Shortcode'i atribuut `language` alistab automaatse keele tuvastamise, välja arvatud juhul, kui `Force default language` on lubatud. Kasutage `language="auto"`, et säilitada automaatne tuvastamise järjekord.

Lühikoodi näited:

```text
[travely-widget-search language="auto"]
[travely-widget-search language="rus"]
[travely-widget-search language="eng"]
[travely-widget-search language="est"]
[travely-widget-search language="lav"]

[travely-widget-search-country language="eng"]
[travely-widget-country language="rus"]
[travely-widget-results language="est"]
```

Ärge kasutage samal lehel Travely Widgeti erinevaid keeli. Vidin laaditakse UMD-bundle'ina ja loob globaalse objekti `window.TravelySearch`, seega saab ühel lehel turvaliselt kasutada ainult ühte keelebuild'i.

Otsingutulemuste tee režiim:

- `Single path for all languages` kasutab kõigi tuvastatud keelte jaoks ühist `Path to Search` seadistust.
- `Separate path for each language` kasutab välju `Estonian search path`, `English search path`, `Russian search path` ja `Latvian search path`.

`Path mode` määrab ka, millised tee väljad seadistuste lehel kuvatakse. Režiimis `Single path for all languages` kuvatakse ainult üldine `Path to Search` väli. Režiimis `Separate path for each language` kuvatakse ainult `Language-specific paths` sektsioon.

Kui keelepõhine tee on tühi, kasutab plugin ühist `Path to Search` väärtust. Kui ka ühine tee on tühi, kasutatakse `/tour-search`.

Tee valitakse pärast lõpliku keele tuvastamist. Näiteks `[travely-widget-search language="rus"]` kasutab vene otsinguteed, `[travely-widget-search language="est"]` kasutab eesti otsinguteed ja `language="auto"` tuvastab esmalt lehe keele ning valib seejärel sobiva tee. Kui `Force default language` on lubatud, valitakse tee sunnitud vaikimisi keele järgi.

`Results background` ehk tulemuste taustavärv määrab manustatud tulemuste iframe'i taustavärvi. Jätke tühjaks läbipaistvuse jaoks. Toetatud väärtused: `transparent`, HEX (`#fff`, `#ffffff`), `rgb()`, `rgba()`, `hsl()`, `hsla()`.

Atribuut `background` shortcode'is `[travely-widget-results]` alistab globaalse seadistuse selle konkreetse shortcode'i jaoks:

```text
[travely-widget-results background="transparent"]
[travely-widget-results background="#ffffff"]
[travely-widget-results background="rgba(255,255,255,0.9)"]
```

Toetatud vidina keeled:

- `est` - eesti
- `eng` - inglise
- `rus` - vene
- `lav` - läti

URL-i näited:

- `?language=est`
- `?language=eng`
- `?language=rus`
- `?language=lav`
- `?lang=et`
- `?lang=en`
- `?lang=ru`
- `?lang=lv`

Täpsemaks mitmekeelse tee kohandamiseks kasutage filtrit `travely_widget_path_to_search`. Filter käivitatakse pärast lõpliku tee valimist:

```php
add_filter( 'travely_widget_path_to_search', function ( $path, $language ) {
    switch ( $language ) {
        case 'eng':
            return '/en/tour-search';
        case 'rus':
            return '/ru/tour-search';
        case 'lav':
            return '/lv/tour-search';
        case 'est':
        default:
            return '/tour-search';
    }
}, 10, 2 );
```

### Kasutamine

Kasutage lühikoode `[travely-widget-search]`, `[travely-widget-country]` ja `[travely-widget-search-country]` ükskõik millises postituses või lehel, et kuvada Travely vidinaid.

---

<a id="russian"></a>
## Русский

### Установка

1. Скачайте архив `travely-widget-v*.zip` с плагином из [релизов GitHub](https://github.com/Travely-ee/travely-widget-wordpress/releases).
2. Войдите в админ-панель WordPress.
3. Перейдите в раздел **Плагины -> Добавить новый -> Загрузить плагин**.
4. Загрузите архив и активируйте плагин.

### Настройка

1. Перейдите в раздел **Настройки -> Travely Widget** для настройки виджета.
2. Введите API-ключ и путь к странице результатов поиска.
3. Создайте новую страницу с шорткодом `[travely-widget-results]` и slug/путём, который указан в настройках. На этой странице будут отображаться результаты поиска.

`Default language` или язык по умолчанию используется, если язык не удалось определить автоматически.

`Force default language` или принудительное использование языка по умолчанию означает, что плагин всегда использует выбранный язык и игнорирует язык из URL, Polylang/WPML и локаль WordPress.

Если `Force default language` выключен, плагин определяет язык страницы в таком порядке:

1. URL `?language=`
2. URL `?lang=`
3. Текущий язык Polylang
4. Текущий язык WPML
5. Локаль WordPress
6. Default language

Язык выбирается на уровне страницы. Все шорткоды на одной странице используют один и тот же определённый язык.

Атрибут shortcode `language` переопределяет автоматическое определение языка, если не включён режим `Force default language`. Используйте `language="auto"`, чтобы сохранить автоматический порядок определения.

Примеры shortcode:

```text
[travely-widget-search language="auto"]
[travely-widget-search language="rus"]
[travely-widget-search language="eng"]
[travely-widget-search language="est"]
[travely-widget-search language="lav"]

[travely-widget-search-country language="eng"]
[travely-widget-country language="rus"]
[travely-widget-results language="est"]
```

Не смешивайте разные языки Travely Widget на одной странице. Виджет загружается как UMD-бандл и создаёт глобальный объект `window.TravelySearch`, поэтому на одной странице безопасно использовать только один языковой билд.

Режим пути к странице результатов поиска:

- `Single path for all languages` использует общий `Path to Search` для всех определённых языков.
- `Separate path for each language` использует отдельные поля `Estonian search path`, `English search path`, `Russian search path` и `Latvian search path`.

`Path mode` также определяет, какие поля path отображаются на странице настроек. В режиме `Single path for all languages` показывается только общее поле `Path to Search`. В режиме `Separate path for each language` показывается только секция `Language-specific paths`.

Если языковой path пустой, плагин использует общий `Path to Search`. Если общий path тоже пустой, используется `/tour-search`.

Path выбирается после итогового определения языка. Например, `[travely-widget-search language="rus"]` использует русский path, `[travely-widget-search language="est"]` использует эстонский path, а `language="auto"` сначала определяет язык страницы и затем выбирает соответствующий path. Если включён `Force default language`, path выбирается для принудительного языка по умолчанию.

`Results background` задаёт цвет фона встраиваемого iframe с результатами. Оставьте пустым для прозрачного фона. Поддерживаемые значения: `transparent`, HEX (`#fff`, `#ffffff`), `rgb()`, `rgba()`, `hsl()`, `hsla()`.

Атрибут `background` в шорткоде `[travely-widget-results]` переопределяет глобальную настройку для конкретного шорткода:

```text
[travely-widget-results background="transparent"]
[travely-widget-results background="#ffffff"]
[travely-widget-results background="rgba(255,255,255,0.9)"]
```

Поддерживаемые языки виджета:

- `est` - эстонский
- `eng` - английский
- `rus` - русский
- `lav` - латышский

Примеры URL:

- `?language=est`
- `?language=eng`
- `?language=rus`
- `?language=lav`
- `?lang=et`
- `?lang=en`
- `?lang=ru`
- `?lang=lv`

Для тонкой настройки многоязычных URL используйте filter `travely_widget_path_to_search`. Filter применяется после выбора итогового path:

```php
add_filter( 'travely_widget_path_to_search', function ( $path, $language ) {
    switch ( $language ) {
        case 'eng':
            return '/en/tour-search';
        case 'rus':
            return '/ru/tour-search';
        case 'lav':
            return '/lv/tour-search';
        case 'est':
        default:
            return '/tour-search';
    }
}, 10, 2 );
```

### Использование

Используйте шорткоды `[travely-widget-search]`, `[travely-widget-country]` и `[travely-widget-search-country]` в любой записи или странице, чтобы отобразить виджеты Travely.
