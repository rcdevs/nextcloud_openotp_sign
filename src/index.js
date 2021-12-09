import Vue from 'vue'
import { translate } from '@nextcloud/l10n'

import App from './App'
import router from './router'

Vue.prototype.$t = translate

// eslint-disable-next-line
new Vue({
	router,
	el: '#app',
	render: h => h(App),
})
