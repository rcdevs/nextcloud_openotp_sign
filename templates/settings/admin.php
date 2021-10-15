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
  data-client-id="<?php echo $_['client_id'] ?>"
  data-default-domain="<?php echo $_['default_domain'] ?>"
  data-user-settings="<?php echo $_['user_settings'] ?>"
></div>
