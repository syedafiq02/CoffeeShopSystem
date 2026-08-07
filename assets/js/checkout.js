document.addEventListener('DOMContentLoaded', function () {
    var pickupRadio = document.getElementById('orderTypePickup');
    var deliveryRadio = document.getElementById('orderTypeDelivery');
    var deliveryGroup = document.getElementById('deliveryAddressGroup');
    var deliveryAddressInput = document.getElementById('deliveryAddressInput');
    var deliveryFeeRow = document.getElementById('deliveryFeeRow');
    var grandTotalEl = document.getElementById('grandTotalDisplay');

    if (!pickupRadio || !deliveryRadio) {
        return;
    }

    function updateSummary() {
        var isDelivery = deliveryRadio.checked;
        deliveryGroup.style.display = isDelivery ? '' : 'none';
        deliveryAddressInput.required = isDelivery;
        deliveryFeeRow.style.display = isDelivery ? '' : 'none';

        var total = CART_SUBTOTAL + (isDelivery ? DELIVERY_FEE_JS : 0);
        grandTotalEl.textContent = total.toFixed(2);
    }

    pickupRadio.addEventListener('change', updateSummary);
    deliveryRadio.addEventListener('change', updateSummary);
    updateSummary();
});
