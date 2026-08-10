// Cosmetic input formatting for the mock card payment form only.
// Purely client-side display formatting — nothing here transmits data
// anywhere; the actual validation happens server-side in payment_card.php.
document.addEventListener('DOMContentLoaded', function () {
    var cardNumberInput = document.getElementById('cardNumberInput');
    var cardExpiryInput = document.getElementById('cardExpiryInput');
    var cardCvvInput = document.getElementById('cardCvvInput');

    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function () {
            var digits = cardNumberInput.value.replace(/\D/g, '').slice(0, 19);
            var groups = digits.match(/.{1,4}/g) || [];
            cardNumberInput.value = groups.join(' ');
        });
    }

    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function () {
            var digits = cardExpiryInput.value.replace(/\D/g, '').slice(0, 4);
            if (digits.length >= 3) {
                cardExpiryInput.value = digits.slice(0, 2) + '/' + digits.slice(2);
            } else {
                cardExpiryInput.value = digits;
            }
        });
    }

    if (cardCvvInput) {
        cardCvvInput.addEventListener('input', function () {
            cardCvvInput.value = cardCvvInput.value.replace(/\D/g, '').slice(0, 3);
        });
    }
});
