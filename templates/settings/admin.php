<?php
/**
 *
 * @copyright Copyright (c) 2021, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

$appId = OCA\OpenOTPSign\AppInfo\Application::APP_ID;
OCP\Util::addscript($appId, 'openotpsign-adminSettings');
/** @var array $_ */
/** @var OCP\IL10N $l */
?>
<div
  id="openotpsign-admin-root"
  data-server-urls="<?php echo htmlspecialchars($_['server_urls']) ?>"
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
  data-async-timeout="<?php echo $_['async_timeout'] ?>"
></div>
