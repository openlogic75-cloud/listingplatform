/**
 * Driver base picker (M15.2). Narrows the locality check options to the
 * chosen district, clears selections that no longer apply, and keeps the
 * five-locality maximum visible. Plain external script (CSP same-origin);
 * the server re-validates everything.
 */
(function () {
  var district = document.getElementById('base-district');

  if (!district) {
    return;
  }

  var MAX_LOCALITIES = 5;
  var options = Array.prototype.slice.call(
    document.querySelectorAll('[data-locality-district]')
  );
  var emptyNote = document.querySelector('[data-locality-empty]');

  function boxes(scope) {
    return scope
      ? scope.querySelectorAll('input[type="checkbox"]')
      : document.querySelectorAll('[data-locality-district] input[type="checkbox"]');
  }

  function visibleOptions() {
    return options.filter(function (option) {
      return !option.hidden;
    });
  }

  function enforceMaximum() {
    var checked = visibleOptions().filter(function (option) {
      var box = option.querySelector('input');
      return box && box.checked;
    }).length;

    visibleOptions().forEach(function (option) {
      var box = option.querySelector('input');

      if (!box) {
        return;
      }

      box.disabled = !box.checked && checked >= MAX_LOCALITIES;
    });
  }

  function applyFilter() {
    var current = district.value;
    var shown = 0;

    options.forEach(function (option) {
      var matches = option.getAttribute('data-locality-district') === current;
      option.hidden = !matches;

      if (matches) {
        shown++;
      } else {
        var box = option.querySelector('input');

        if (box) {
          box.checked = false;
        }
      }
    });

    if (emptyNote) {
      emptyNote.hidden = shown > 0;
    }

    enforceMaximum();
  }

  district.addEventListener('change', applyFilter);

  boxes().forEach(function (box) {
    box.addEventListener('change', enforceMaximum);
  });

  applyFilter();
})();
