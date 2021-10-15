<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'

export default {
	name: 'AppAdmin',
	components: {
		CheckboxRadioSwitch,
	},
	data() {
		return {
			serverUrl: this.$parent.serverUrl,
			ignoreSslErrors: !!this.$parent.ignoreSslErrors,
			sslSettingEnabled: this.$parent.serverUrl.startsWith('https://'),

		}
	},
	methods: {
		saveSettings() {
			const baseUrl = generateUrl('/apps/openotpsign')

			axios.post(baseUrl + '/settings', {
				server_url: this.serverUrl,
				ignore_ssl_errors: this.ignoreSslErrors,
			})
				.then(function(response) {
					// eslint-disable-next-line
					console.log(response)
				})
				.catch(function(error) {
					// eslint-disable-next-line
					console.log(error)
				})
		},
		enableSslSetting() {
			this.sslSettingEnabled = this.serverUrl.startsWith('https://')
		},
	},
}
</script>

<template>
	<div id="openotpsign" class="section">
		<h2>{{ $t('openotpsign', 'OpenOTP Digital Signature') }}</h2>
		<p>
			{{ $t('openotpsign', 'Enter your OpenOTP server settings in the fields below.') }}
		</p>
		<p>
			<label for="ootp_server_url">{{ $t('openotpsign', 'OpenOTP server URL') }}</label>
			<input id="ootp_server_url"
				v-model="serverUrl"
				type="text"
				name="ootp_server_url"
				placeholder="https://myserver:8443/openotp/"
				@input="enableSslSetting">
		</p>
		<p>
			<CheckboxRadioSwitch :checked.sync="ignoreSslErrors" :disabled="!sslSettingEnabled">
				Ignore SSL/TLS certificate errors
			</CheckboxRadioSwitch>
		</p>
		<p>
			<button @click="saveSettings">
				Save
			</button>
		</p>
	</div>
</template>
<style scoped>
label,
input {
	display: inline-block;
}

label {
	width: 200px;
}

input {
	width: 400px;
}
</style>
