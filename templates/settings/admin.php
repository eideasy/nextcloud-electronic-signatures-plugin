<?php
$appId = OCA\ElectronicSignatures\AppInfo\Application::APP_ID;
OCP\Util::addscript($appId, 'electronic-signatures-adminSettings');
/** @var array $_ */
/** @var OCP\IL10N $l */
?>

<div
  id="electronic-signatures-admin-root"
  data-client-id="<?php echo $_['client_id_placeholder'] ?>"
  data-secret="<?php echo $_['secret_placeholder'] ?>"
  data-enable-otp="<?php echo $_['enable_otp'] ?>"
  data-enable-local-signing="<?php echo $_['enable_local_signing'] ?>"
  data-pades-url="<?php echo $_['pades_url'] ?>"
  data-enable-sandbox="<?php echo $_['enable_sandbox'] ?>"
></div>

