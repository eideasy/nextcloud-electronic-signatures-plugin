<?php
//script('activity', 'admin');
//style('activity', 'settings');
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<div
  id="electronic-signatures-admin-root"
  data-client-id="<?php echo $_['client_id_placeholder'] ?>"
  data-secret="<?php echo $_['secret_placeholder'] ?>"
  data-enable-otp="<?php echo $_['enable_otp'] ?>"
  data-enable-local-signing="<?php echo $_['enable_local_signing'] ?>"
></div>

