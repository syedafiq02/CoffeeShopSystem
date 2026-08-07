function updateCartBadge(count) {
    var badge = document.getElementById('cartCountBadge');
    if (badge) {
        badge.textContent = count;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // --- Add to Cart (Menu / Home pages) ---
    document.querySelectorAll('.add-to-cart-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.product-card');
            var qtyInput = card.querySelector('.qty-input');
            var quantity = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

            var formData = new URLSearchParams();
            formData.set('csrf_token', CSRF_TOKEN);
            formData.set('product_id', btn.getAttribute('data-product-id'));
            formData.set('quantity', quantity);

            btn.disabled = true;
            var originalText = btn.textContent;

            fetch(BASE_URL + 'ajax/cart_add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        updateCartBadge(data.cart_count);
                        btn.textContent = 'Added ✓';
                        setTimeout(function () {
                            btn.textContent = originalText;
                            btn.disabled = false;
                        }, 1200);
                    } else {
                        alert(data.message || 'Could not add item to cart.');
                        btn.disabled = false;
                    }
                })
                .catch(function () {
                    alert('Something went wrong. Please try again.');
                    btn.disabled = false;
                });
        });
    });

    // --- Cart page: quantity update ---
    document.querySelectorAll('.cart-qty-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var cartId = input.getAttribute('data-cart-id');
            var quantity = parseInt(input.value, 10) || 1;

            var formData = new URLSearchParams();
            formData.set('csrf_token', CSRF_TOKEN);
            formData.set('cart_id', cartId);
            formData.set('quantity', quantity);

            fetch(BASE_URL + 'ajax/cart_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('lineTotal' + cartId).textContent = data.line_total;
                        document.getElementById('cartGrandTotal').textContent = data.cart_total;
                        updateCartBadge(data.cart_count);
                    } else {
                        alert(data.message || 'Could not update quantity.');
                    }
                })
                .catch(function () {
                    alert('Something went wrong. Please try again.');
                });
        });
    });

    // --- Cart page: remove item ---
    document.querySelectorAll('.cart-remove-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Remove this item from your cart?')) {
                return;
            }
            var cartId = btn.getAttribute('data-cart-id');

            var formData = new URLSearchParams();
            formData.set('csrf_token', CSRF_TOKEN);
            formData.set('cart_id', cartId);

            fetch(BASE_URL + 'ajax/cart_remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('cartRow' + cartId).remove();
                        document.getElementById('cartGrandTotal').textContent = data.cart_total;
                        updateCartBadge(data.cart_count);
                        if (data.cart_count === 0) {
                            location.reload();
                        }
                    } else {
                        alert(data.message || 'Could not remove item.');
                    }
                })
                .catch(function () {
                    alert('Something went wrong. Please try again.');
                });
        });
    });
});
