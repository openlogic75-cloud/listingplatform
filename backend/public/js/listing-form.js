/**
 * Listing form helpers (M12.1/M15.1). Rentals sell a date window; other
 * categories sell stock and a minimum order. Plain external script because
 * the CSP allows same-origin scripts only. No motion involved.
 */
(function () {
  var category = document.getElementById('listing-category');

  if (category) {
    var rentalFields = document.querySelectorAll('[data-listing-rental]');
    var stockFields = document.querySelectorAll('[data-listing-stock]');

    var apply = function () {
      var isRental = category.value === 'rental_homestay';

      rentalFields.forEach(function (element) {
        element.hidden = !isRental;
      });

      stockFields.forEach(function (element) {
        element.hidden = isRental;
      });
    };

    category.addEventListener('change', apply);
    apply();
  }

  var photoInput = document.getElementById('listing-photos');
  var photoCount = document.querySelector('[data-photo-count]');

  if (photoInput && photoCount) {
    photoInput.addEventListener('change', function () {
      var count = photoInput.files ? photoInput.files.length : 0;

      if (count === 0) {
        photoCount.textContent = 'No photos selected yet';
      } else if (count === 1) {
        photoCount.textContent = '1 photo selected';
      } else if (count > 4) {
        photoCount.textContent = count + ' photos selected - only the first 4 are kept';
      } else {
        photoCount.textContent = count + ' photos selected';
      }
    });
  }
})();
