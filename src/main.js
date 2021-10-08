'use strict'

OCA.Files.fileActions.registerAction({
	mime: 'file',
	name: 'OpenOTPSign',
	permissions: OC.PERMISSION_READ,
	iconClass: 'custom-icon-signature',
	actionHandler: (filename, context) => {
		alert('TODO')
	},
	displayName: t('openotpsign', 'Sign with OpenOTP'),
	order: -100,
})
