<?php
$appId = OCA\ElectronicSignatures\AppInfo\Application::APP_ID;
OCP\Util::addInitScript($appId, 'electronic-signatures-adminSettings');
/** @var array $_ */
/** @var OCP\IL10N $l */
?>

<div
  id="electronic-signatures-admin-root"
  data-client-id="<?php echo $_['client_id_placeholder'] ?>"
  data-secret="<?php echo $_['secret_placeholder'] ?>"
  data-enable-otp="<?php echo $_['enable_otp'] ?>"
  data-pades-url="<?php echo $_['pades_url'] ?>"
  data-enable-sandbox="<?php echo $_['enable_sandbox'] ?>"
  data-container-type="<?php echo $_['container_type'] ?>"
  data-api-language="<?php echo $_['api_language'] ?>"
  data-remote-signing-queue-status-webhook="<?php echo $_['remote_signing_queue_status_webhook'] ?>"
  data-default-remote-signing-queue-status-webhook="<?php echo $_['default_remote_signing_queue_status_webhook'] ?>"
  data-signing-mode="<?php echo $_['signing_mode'] ?>"
></div>

