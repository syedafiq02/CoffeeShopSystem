document.addEventListener('DOMContentLoaded', function () {
    var pickupRadio = document.getElementById('orderTypePickup');
    var deliveryRadio = document.getElementById('orderTypeDelivery');
    var deliveryGroup = document.getElementById('deliveryAddressGroup');
    var deliveryAddressInput = document.getElementById('deliveryAddressInput');
    var deliveryFeeRow = document.getElementById('deliveryFeeRow');
    var grandTotalEl = document.getElementById('grandTotalDisplay');
    var discountRow = document.getElementById('discountRow');
    var discountAmountEl = document.getElementById('discountAmountDisplay');
    var promoCodeInput = document.getElementById('promoCodeInput');
    var promoApplyBtn = document.getElementById('promoApplyBtn');
    var promoMessageEl = document.getElementById('promoMessage');

    var appliedDiscount = 0;

    if (!pickupRadio || !deliveryRadio) {
        return;
    }

    function updateSummary() {
        var isDelivery = deliveryRadio.checked;
        deliveryGroup.style.display = isDelivery ? '' : 'none';
        deliveryAddressInput.required = isDelivery;
        deliveryFeeRow.style.display = isDelivery ? '' : 'none';

        var total = CART_SUBTOTAL - appliedDiscount + (isDelivery ? DELIVERY_FEE_JS : 0);
        grandTotalEl.textContent = total.toFixed(2);
    }

    pickupRadio.addEventListener('change', updateSummary);
    deliveryRadio.addEventListener('change', updateSummary);
    updateSummary();

    if (promoApplyBtn) {
        promoApplyBtn.addEventListener('click', function () {
            var code = promoCodeInput.value.trim();
            if (!code) {
                return;
            }

            var formData = new URLSearchParams();
            formData.set('csrf_token', CSRF_TOKEN);
            formData.set('code', code);

            promoApplyBtn.disabled = true;

            fetch(BASE_URL + 'ajax/apply_promo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    promoApplyBtn.disabled = false;

                    if (data.success) {
                        appliedDiscount = parseFloat(data.discount_amount);
                        discountAmountEl.textContent = appliedDiscount.toFixed(2);
                        discountRow.style.display = '';
                        promoMessageEl.textContent = data.message;
                        promoMessageEl.className = 'form-text text-success';
                    } else {
                        appliedDiscount = 0;
                        discountRow.style.display = 'none';
                        promoMessageEl.textContent = data.message || 'Could not apply promo code.';
                        promoMessageEl.className = 'form-text text-danger';
                    }

                    updateSummary();
                })
                .catch(function () {
                    promoApplyBtn.disabled = false;
                    promoMessageEl.textContent = 'Something went wrong. Please try again.';
                    promoMessageEl.className = 'form-text text-danger';
                });
        });
    }
});
