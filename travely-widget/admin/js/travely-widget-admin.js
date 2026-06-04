(function () {
  function closestTableRow(element) {
    return element ? element.closest('tr') : null;
  }

  function setDisplay(element, value) {
    if (element) {
      element.style.display = value;
    }
  }

  function setLanguagePathsSectionDisplay(sectionMarker, value) {
    if (!sectionMarker) {
      return;
    }

    setDisplay(sectionMarker, value);

    if (sectionMarker.previousElementSibling && sectionMarker.previousElementSibling.tagName === 'H2') {
      setDisplay(sectionMarker.previousElementSibling, value);
    }

    if (sectionMarker.nextElementSibling && sectionMarker.nextElementSibling.tagName === 'TABLE') {
      setDisplay(sectionMarker.nextElementSibling, value);
    }
  }

  function updatePathModeVisibility() {
    var modeSelect = document.getElementById('travely_widget_path_mode');
    var singlePathInput = document.getElementById('travely_widget_path_to_search');
    var singlePathRow = closestTableRow(singlePathInput);
    var languagePathsSectionMarker = document.getElementById('travely-widget-language-paths-section');

    if (!modeSelect) {
      return;
    }

    var isLanguageMode = modeSelect.value === 'language';

    if (singlePathRow) {
      singlePathRow.style.display = isLanguageMode ? 'none' : '';
    }

    setLanguagePathsSectionDisplay(languagePathsSectionMarker, isLanguageMode ? '' : 'none');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var modeSelect = document.getElementById('travely_widget_path_mode');

    updatePathModeVisibility();

    if (modeSelect) {
      modeSelect.addEventListener('change', updatePathModeVisibility);
    }
  });
})();
