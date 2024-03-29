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

namespace OCA\OpenOTPSign\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {

    /** @var IConfig */
    protected $config;

    /**
     * @param IConfig $config
     */
    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm() {
        return new TemplateResponse('openotp_sign', 'settings/admin', [
            'server_urls'           => $this->config->getAppValue('openotp_sign', 'server_urls', '[]'),
            'client_id'             => $this->config->getAppValue('openotp_sign', 'client_id', 'Nextcloud'),
            'api_key'               => $this->config->getAppValue('openotp_sign', 'api_key'),
            'use_proxy'             => $this->config->getAppValue('openotp_sign', 'use_proxy'),
            'proxy_host'            => $this->config->getAppValue('openotp_sign', 'proxy_host'),
            'proxy_port'            => $this->config->getAppValue('openotp_sign', 'proxy_port'),
            'proxy_username'        => $this->config->getAppValue('openotp_sign', 'proxy_username'),
            'proxy_password'        => $this->config->getAppValue('openotp_sign', 'proxy_password'),
            'enable_otp_sign'       => $this->config->getAppValue('openotp_sign', 'enable_otp_sign'),
            'enable_otp_seal'       => $this->config->getAppValue('openotp_sign', 'enable_otp_seal'),
            'sign_type_standard'    => $this->config->getAppValue('openotp_sign', 'sign_type_standard'),
            'sign_type_advanced'    => $this->config->getAppValue('openotp_sign', 'sign_type_advanced'),
            'signed_file'           => $this->config->getAppValue('openotp_sign', 'signed_file', 'copy'),
            'sync_timeout'          => $this->config->getAppValue('openotp_sign', 'sync_timeout', 2),
            'async_timeout'         => $this->config->getAppValue('openotp_sign', 'async_timeout', 1),
            'cron_interval'         => $this->config->getAppValue('openotp_sign', 'cron_interval', 5),
            'enable_demo_mode'      => $this->config->getAppValue('openotp_sign', 'enable_demo_mode'),
            'watermark_text'        => $this->config->getAppValue('openotp_sign', 'watermark_text', 'RCDEVS - SPECIMEN - OPENOTP'),
        ], 'blank');
    }

    /**
     * @return string the section ID, e.g. 'sharing'
     */
    public function getSection() {
        return 'openotp_sign';
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     *
     * E.g.: 70
     */
    public function getPriority() {
        return 55;
    }
}
