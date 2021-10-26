import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import EventBus from './EventBus'
import OpenOTPSignModal from './OpenOTPSignModal'

Vue.prototype.$t = translate

const modalHolderId = 'ootpsignModalHolder'
const modalHolder = document.createElement('div')
modalHolder.id = modalHolderId
document.body.append(modalHolder)

// eslint-disable-next-line
new Vue({
	el: modalHolder,
	render: h => {
		return h(OpenOTPSignModal)
	},
})

OCA.Files.fileActions.registerAction({
	mime: 'file',
	name: 'OpenOTPSign',
	permissions: OC.PERMISSION_READ,
	iconClass: 'custom-icon-signature',
	actionHandler: (filename, context) => {
		EventBus.$emit('ootp-sign-click', { filename })
	},
	displayName: t('openotpsign', 'Sign with OpenOTP'),
	order: -100,
})
