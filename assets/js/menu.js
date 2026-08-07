// Populate the shared product details modal from the clicked button's data attributes.
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('productDetailsModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            document.getElementById('productModalName').textContent = btn.getAttribute('data-name');
            document.getElementById('productModalDescription').textContent = btn.getAttribute('data-description');
            document.getElementById('productModalPrice').textContent = btn.getAttribute('data-price');
            document.getElementById('productModalCategory').textContent = btn.getAttribute('data-category');
            document.getElementById('productModalImage').src = btn.getAttribute('data-image');
        });
    }

    // Category filter tabs (Menu page). Buttons carry data-category-id="0" for "All".
    var filterButtons = document.querySelectorAll('.category-filter-btn');
    var productCards = document.querySelectorAll('.product-card');

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            var selected = btn.getAttribute('data-category-id');
            productCards.forEach(function (card) {
                var show = selected === '0' || card.getAttribute('data-category-id') === selected;
                card.style.display = show ? '' : 'none';
            });
        });
    });
});
