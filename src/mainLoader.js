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

import Vue from 'vue'
import VueObserveVisibility from 'vue-observe-visibility'
import { Tooltip } from '@nextcloud/vue'
import { FileAction, Permission, registerFileAction } from '@nextcloud/files'

import '@nextcloud/dialogs/style.css'

import OpenOTPSignModal from './views/OpenOTPSignModal.vue'
import Logo from '../img/OpenOtpSign.svg?raw'
import './styles/loader.scss'
import {getT} from './javascript/utility.js';
import {sealAction, signAction} from './javascript/config.js';

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA
Vue.prototype.OCP = OCP

Vue.directive('tooltip', Tooltip)

Vue.use(VueObserveVisibility)

const el = document.createElement('div')
document.body.appendChild(el)

// Seal menu
const appSeal = new Vue({
	el,
	data: {
		action: null,
		chosenFile: null
	},
	render: h => h(OpenOTPSignModal),
})

appSeal.$on('dialog:open', (model) => {
	appSeal.$data.action = sealAction,
	appSeal.$data.chosenFile = model
})

appSeal.$on('dialog:closed', () => {
	appSeal.$data.action = null,
	appSeal.$data.chosenFile = null
})

// Sign menu
const appSign = new Vue({
	el,
	data: {
		action: null,
		chosenFile: null
	},
	render: h => h(OpenOTPSignModal),
})

appSign.$on('dialog:open', (model) => {
	appSign.$data.action = signAction,
	appSign.$data.chosenFile = model
})

appSign.$on('dialog:closed', () => {
	appSign.$data.action = null,
	appSign.$data.chosenFile = null
})

registerFileAction(new FileAction({
	id: `${appName}_sign`,
	displayName: () => getT('Sign with OpenOTP'),
	iconSvgInline: () => Logo,
	enabled: (files, view) => {
		return (files.length === 1
				// && files[0].mime === 'application/pdf'
				&& files[0].type === 'file'
				&& (files[0].permissions & (Permission.READ | Permission.WRITE)) === (Permission.READ | Permission.WRITE))
	},
	exec: (file, view, dir) => {
		appSign.$emit('dialog:open', file)
	},
}))

registerFileAction(new FileAction({
	id: `${appName}_seal`,
	displayName: () => getT('Seal with OpenOTP'),
	iconSvgInline: () => Logo,
	enabled: (files, view) => {
		return (files.length === 1
				// && files[0].mime === 'application/pdf'
				&& files[0].type === 'file'
				&& (files[0].permissions & (Permission.READ | Permission.WRITE)) === (Permission.READ | Permission.WRITE))
	},
	exec: (file, view, dir) => {
		appSeal.$emit('dialog:open', file)
	},
}))

