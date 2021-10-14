import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import AppAdmin from './AppAdmin'

Vue.prototype.$t = translate

const adminRootElement = document.getElementById('openotpsign-admin-root')
// eslint-disable-next-line
new Vue({
	el: '#openotpsign-admin-root',
	data: () => Object.assign({}, adminRootElement.dataset),
	render: h => h(AppAdmin),
})
