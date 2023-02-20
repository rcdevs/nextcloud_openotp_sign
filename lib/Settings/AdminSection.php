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

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

    /** @var IL10N */
    private $l;

    /** @var IURLGenerator */
    private $url;

    /**
     * @param IURLGenerator $url
     * @param IL10N $l
     */
    public function __construct(IURLGenerator $url, IL10N $l) {
        $this->url = $url;
        $this->l = $l;
    }

    /**
     * returns the relative path to an 16*16 icon describing the section.
     * e.g. '/core/img/places/files.svg'
     *
     * @returns string
     * @since 12
     */
    public function getIcon() {
        return $this->url->imagePath('openotp_sign', 'signature.svg');
    }

    /**
     * returns the ID of the section. It is supposed to be a lower case string,
     * e.g. 'ldap'
     *
     * @returns string
     * @since 9.1
     */
    public function getID() {
        return 'openotp_sign';
    }

    /**
     * returns the translated name as it should be displayed, e.g. 'LDAP / AD
     * integration'. Use the L10N service to translate it.
     *
     * @return string
     * @since 9.1
     */
    public function getName() {
        return $this->l->t('OpenOTP Sign');
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the settings navigation. The sections are arranged in ascending order of
     * the priority values. It is required to return a value between 0 and 99.
     *
     * E.g.: 70
     * @since 9.1
     */
    public function getPriority() {
        return 55;
    }
}
