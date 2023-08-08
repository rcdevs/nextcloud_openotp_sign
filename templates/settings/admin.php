<?php
/**
 *
 * @copyright Copyright (c) 2023, RCDevs (info@rcdevs.com)
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
OCP\Util::addscript($appId, 'openotp_sign-adminSettings');
/** @var array $_ */
/** @var OCP\IL10N $l */
?>
<div
  id="openotp_sign-admin-root"
  data-server-urls        = "<?php echo htmlspecialchars($_['server_urls']) ?>"
  data-client-id          = "<?php echo $_['client_id'] ?>"
  data-api-key            = "<?php echo $_['api_key'] ?>"
  data-use-proxy          = "<?php echo $_['use_proxy'] ?>"
  data-proxy-host         = "<?php echo $_['proxy_host'] ?>"
  data-proxy-port         = "<?php echo $_['proxy_port'] ?>"
  data-proxy-username     = "<?php echo $_['proxy_username'] ?>"
  data-proxy-password     = "<?php echo $_['proxy_password'] ?>"
  data-enable-otp-sign    = "<?php echo $_['enable_otp_sign'] ?>"
  data-enable-otp-seal    = "<?php echo $_['enable_otp_seal'] ?>"
  data-sign-type-standard = "<?php echo $_['sign_type_standard'] ?>"
  data-sign-type-advanced = "<?php echo $_['sign_type_advanced'] ?>"
  data-signed-file        = "<?php echo $_['signed_file'] ?>"
  data-sync-timeout       = "<?php echo $_['sync_timeout'] ?>"
  data-async-timeout      = "<?php echo $_['async_timeout'] ?>"
  data-cron-interval      = "<?php echo $_['cron_interval'] ?>"
  data-enable-demo-mode   = "<?php echo $_['enable_demo_mode'] ?>"
  data-watermark-text     = "<?php echo $_['watermark_text'] ?>"
></div>
