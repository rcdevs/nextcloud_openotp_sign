import Vue from 'vue'
import EventBus from './EventBus'
import OpenOTPSignModal from './OpenOTPSignModal'

const modalHolderId = 'ootpsignModalHolder'
const modalHolder = document.createElement('div')
modalHolder.id = modalHolderId
document.body.append(modalHolder)

// eslint-disable-next-line
const vm = new Vue({
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
