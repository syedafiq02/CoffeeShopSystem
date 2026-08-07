document.addEventListener('DOMContentLoaded', function () {
    // Lightbox modal
    var modal = document.getElementById('galleryModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            document.getElementById('galleryModalImage').src = trigger.getAttribute('data-image');
            document.getElementById('galleryModalTitle').textContent = trigger.getAttribute('data-title');
            document.getElementById('galleryModalDescription').textContent = trigger.getAttribute('data-description');
        });
    }

    // Type filter tabs. Buttons carry data-type="all" or a specific type.
    var filterButtons = document.querySelectorAll('.gallery-filter-btn');
    var galleryItems = document.querySelectorAll('.gallery-item');

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            var selected = btn.getAttribute('data-type');
            galleryItems.forEach(function (item) {
                var show = selected === 'all' || item.getAttribute('data-type') === selected;
                item.style.display = show ? '' : 'none';
            });
        });
    });
});
