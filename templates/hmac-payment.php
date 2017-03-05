<?php _e('Connecting to paybox...', 'wp-paybox') ?>
<noscript><?php _e('If you can read this line, activate javascript and reload the page to proceed with the payment', 'wp-paybox') ?></noscript>
<div style="display:none">
  <form name="PAYBOX" onload="this.submit()" action="<?= $PBX_PAYBOX ?>" method="POST" class="hidden">
    <?php /* we want consistent and identical order between <form> fields and HMAC encrypted parameters */ ?>
    <?php foreach ($pbx_parameters as $name => $value): ?>
    <input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
    <?php endforeach; ?>
    <input type="hidden" name="PBX_HMAC" value="<?= $hmac ?>">
  </form>
</div>
<script>document.PAYBOX.submit();</script>
