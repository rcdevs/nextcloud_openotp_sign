/**
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */


// TODO   will be replaced by API call
// const appInfo = require('../appinfo/info.xml');
// const appName = appInfo.info.id[0];



// const appInfo = require('../../appinfo/info.xml');
// export const appName = appInfo.info.id[0];
export const apiv1   = '/api/v1';
export const appName = 'openotp_sign';
export const baseUrl = `apps/${appName}`;
export const sealAction = 'sealAction';
export const signAction = 'signAction';