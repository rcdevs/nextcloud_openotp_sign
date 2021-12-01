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
        return new TemplateResponse('openotpsign', 'settings/admin', [
            'server_urls' => $this->config->getAppValue('openotpsign', 'server_urls', '[]'),
            'ignore_ssl_errors' => $this->config->getAppValue('openotpsign', 'ignore_ssl_errors'),
            'client_id' => $this->config->getAppValue('openotpsign', 'client_id'),
            'default_domain' => $this->config->getAppValue('openotpsign', 'default_domain'),
            'user_settings' => $this->config->getAppValue('openotpsign', 'user_settings'),
            'use_proxy' => $this->config->getAppValue('openotpsign', 'use_proxy'),
            'proxy_host' => $this->config->getAppValue('openotpsign', 'proxy_host'),
            'proxy_port' => $this->config->getAppValue('openotpsign', 'proxy_port'),
            'proxy_username' => $this->config->getAppValue('openotpsign', 'proxy_username'),
            'proxy_password' => $this->config->getAppValue('openotpsign', 'proxy_password'),
            'signed_file' => $this->config->getAppValue('openotpsign', 'signed_file', 'copy'),
            'async_timeout' => $this->config->getAppValue('openotpsign', 'async_timeout', 1),
        ], 'blank');
    }

    /**
     * @return string the section ID, e.g. 'sharing'
     */
    public function getSection() {
        return 'openotpsign';
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
