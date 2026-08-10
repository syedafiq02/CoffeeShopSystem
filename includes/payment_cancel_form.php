<?php
// Shared "Cancel Payment" button used across payment_fpx.php and
// payment_card.php's intermediate steps. Requires $orderId to already be
// set by the caller. Submits to the current page (action="" targets
// whichever file included this partial).
?>
<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
    <input type="hidden" name="form_step" value="cancel">
    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Cancel Payment</button>
</form>
