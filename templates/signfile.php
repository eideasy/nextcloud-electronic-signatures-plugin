<?php
$appId = OCA\ElectronicSignatures\AppInfo\Application::APP_ID;
OCP\Util::addscript($appId, 'electronic-signatures-signFile');
OCP\Util::addStyle($appId, 'icons');
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<div
        id="electronic-signatures-sign-root"
        data-client-id="<?php echo $_['client_id'] ?>"
        data-doc-id="<?php echo $_['doc_id'] ?>"
        data-mime-type="<?php echo $_['file_mime_type'] ?>"
        data-file-content="<?php echo $_['file_content'] ?>"
        data-file-url="<?php echo $_['file_url'] ?>"
        data-file-name="<?php echo $_['file_name'] ?>"
        data-api-url="<?php echo $_['api_url'] ?>"
        data-enable-sandbox="<?php echo $_['enable_sandbox'] ?>"
></div>
