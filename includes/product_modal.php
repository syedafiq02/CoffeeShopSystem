<div class="modal fade" id="productDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalName"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="productModalImage" src="" alt="" class="img-fluid rounded mb-3">
                <span class="badge bg-coffee-cream text-dark mb-2" id="productModalCategory"></span>
                <p id="productModalDescription" class="text-muted"></p>
                <p class="price-tag mb-0" style="font-size: 1.4rem;"><?= CURRENCY_SYMBOL ?> <span id="productModalPrice"></span></p>
            </div>
        </div>
    </div>
</div>
