/**
 * Booking form helpers (M15.4): quantity stepper and live estimate. Plain
 * external script — the CSP allows same-origin scripts only. The stepper is
 * progressive enhancement; the number input still works without it.
 */
(function () {
  var form = document.querySelector('.booking-form');

  if (!form) {
    return;
  }

  var quantity = form.querySelector('#booking-qty');
  var total = form.querySelector('[data-total]');
  var unitPrice = parseFloat(form.getAttribute('data-unit-price') || '');

  function clamp(value) {
    var min = parseInt(quantity.getAttribute('min') || '1', 10);
    var max = quantity.getAttribute('max')
      ? parseInt(quantity.getAttribute('max'), 10)
      : Number.MAX_SAFE_INTEGER;

    return Math.min(max, Math.max(min, value));
  }

  form.querySelectorAll('[data-qty-step]').forEach(function (button) {
    button.addEventListener('click', function () {
      var step = parseInt(button.getAttribute('data-qty-step'), 10);
      var current = parseInt(quantity.value, 10);

      quantity.value = clamp((isNaN(current) ? 0 : current) + step);
      quantity.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  function updateTotal() {
    if (!total || isNaN(unitPrice)) {
      return;
    }

    var quantityValue = parseInt(quantity.value, 10) || 0;

    if (quantityValue < 1) {
      return;
    }

    total.textContent =
      '\u20b9' +
      new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(unitPrice * quantityValue);
  }

  quantity.addEventListener('input', updateTotal);
  updateTotal();
})();
