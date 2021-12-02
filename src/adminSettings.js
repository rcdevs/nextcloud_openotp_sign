import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import AppAdmin from './AppAdmin'

Vue.prototype.$t = translate

const adminRootElement = document.getElementById('openotp_sign-admin-root')
// eslint-disable-next-line
new Vue({
	el: '#openotp_sign-admin-root',
	data: () => Object.assign({}, adminRootElement.dataset),
	render: h => h(AppAdmin),
})
