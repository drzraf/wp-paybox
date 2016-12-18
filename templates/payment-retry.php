<p class="alert"><?php _e('The last payment attempt was refused by Paybox.', 'wp-paybox') ?></p>
<form action="wp-paybox/redirect" method="POST" id="paybox_form">
  <input type="hidden" name="id_payment" value="<?= $id_payment ?>" />
  <input type="submit" value="<?php _e('Make a new payment attempt', 'wp-paybox') ?>" />
</form>
