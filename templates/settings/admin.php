<?php
$appId = OCA\OpenOTPSign\AppInfo\Application::APP_ID;
OCP\Util::addscript($appId, 'openotpsign-adminSettings');
/** @var array $_ */
/** @var OCP\IL10N $l */
?>
<div
  id="openotpsign-admin-root"
  data-server-url="<?php echo $_['server_url'] ?>"
  data-ignore-ssl-errors="<?php echo $_['ignore_ssl_errors'] ?>"
></div>
