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
  data-use-proxy="<?php echo $_['use_proxy'] ?>"
  data-proxy-host="<?php echo $_['proxy_host'] ?>"
  data-proxy-port="<?php echo $_['proxy_port'] ?>"
  data-proxy-username="<?php echo $_['proxy_username'] ?>"
  data-proxy-password="<?php echo $_['proxy_password'] ?>"
  data-signed-file="<?php echo $_['signed_file'] ?>"
></div>
