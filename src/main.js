import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import EventBus from './EventBus'
import OpenOTPSignModal from './OpenOTPSignModal'
import OpenOTPSealModal from './OpenOTPSealModal'

Vue.prototype.$t = translate

const signModalHolderId = 'ootpsignModalHolder'
const signModalHolder = document.createElement('div')
signModalHolder.id = signModalHolderId
document.body.append(signModalHolder)

const sealModalHolderId = 'ootpsealModalHolder'
const sealModalHolder = document.createElement('div')
sealModalHolder.id = sealModalHolderId
document.body.append(sealModalHolder)

// eslint-disable-next-line
new Vue({
	el: signModalHolder,
	render: h => {
		return h(OpenOTPSignModal)
	},
})

// eslint-disable-next-line
new Vue({
	el: sealModalHolder,
	render: h => {
		return h(OpenOTPSealModal)
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

OCA.Files.fileActions.registerAction({
	mime: 'file',
	name: 'OpenOTPSeal',
	permissions: OC.PERMISSION_READ,
	iconClass: 'custom-icon-signature',
	actionHandler: (filename, context) => {
		EventBus.$emit('ootp-seal-click', { filename })
	},
	displayName: t('openotpsign', 'Seal with OpenOTP'),
	order: -99,
})
