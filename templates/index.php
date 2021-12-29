<?php
$appId = OCA\ElectronicSignatures\AppInfo\Application::APP_ID;
OCP\Util::addscript($appId, 'electronic-signatures-main');
OCP\Util::addStyle($appId, 'icons');
?>

<div id="electronic-signatures-root"></div>
