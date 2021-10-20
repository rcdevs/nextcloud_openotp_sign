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
			clientId: this.$parent.clientId,
			defaultDomain: this.$parent.defaultDomain,
			userSettings: this.$parent.userSettings,
			useProxy: !!this.$parent.useProxy,
			proxyHost: this.$parent.proxyHost,
			proxyPort: this.$parent.proxyPort,
			proxyUsername: this.$parent.proxyUsername,
			proxyPassword: this.$parent.proxyPassword,
			messageStatusClass: 'error',
			serverMessage: '',
			success: false,
			failure: false,
		}
	},
	methods: {
		saveSettings() {
			this.success = false
			this.failure = false
			const baseUrl = generateUrl('/apps/openotpsign')

			axios.post(baseUrl + '/settings', {
				server_url: this.serverUrl,
				ignore_ssl_errors: this.ignoreSslErrors,
				client_id: this.clientId,
				default_domain: this.defaultDomain,
				user_settings: this.userSettings,
				use_proxy: this.useProxy,
				proxy_host: this.proxyHost,
				proxy_port: this.proxyPort,
				proxy_username: this.proxyUsername,
				proxy_password: this.proxyPassword,
			})
				.then(response => {
					this.success = true
				})
				.catch(error => {
					this.failure = true
					// eslint-disable-next-line
					console.log(error)
				})
		},
		enableSslSetting() {
			this.sslSettingEnabled = this.serverUrl.startsWith('https://')
		},
		testConnection() {
			const baseUrl = generateUrl('/apps/openotpsign')

			axios.post(baseUrl + '/check_server_url', {
				server_url: this.serverUrl,
				ignore_ssl_errors: this.ignoreSslErrors,
				use_proxy: this.useProxy,
				proxy_host: this.proxyHost,
				proxy_port: this.proxyPort,
				proxy_username: this.proxyUsername,
				proxy_password: this.proxyPassword,
			})
				.then(response => {
					if (response.data.status === true) {
						this.messageStatusClass = 'success'
						this.serverMessage = response.data.message
					} else {
						this.messageStatusClass = 'error'
						this.serverMessage = ''
					}
				})
				.catch(error => {
					this.messageStatusClass = 'error'
					this.serverMessage = ''
					// eslint-disable-next-line
					console.log(error)
				})
		},
	},
}
</script>

<template>
	<div>
		<div id="openotpsign" class="section">
			<h2>{{ $t('openotpsign', 'OpenOTP Sign Settings') }}</h2>
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
				<button @click="testConnection">
					Test
				</button>
				<span id="message_status" :class="messageStatusClass" />
			</p>
			<pre v-if="serverMessage.length" id="server_message">{{ serverMessage }}</pre>
			<p>
				<CheckboxRadioSwitch :checked.sync="ignoreSslErrors" :disabled="!sslSettingEnabled">
					Ignore SSL/TLS certificate errors
				</CheckboxRadioSwitch>
			</p>
			<p>
				<label for="ootp_client_id">{{ $t('openotpsign', 'OpenOTP client id') }}</label>
				<input id="ootp_client_id"
					v-model="clientId"
					type="text"
					name="ootp_client_id"
					placeholder="Nextcloud">
			</p>
			<p>
				<label for="ootp_default_domain">{{ $t('openotpsign', 'OpenOTP Default Domain') }}</label>
				<input id="ootp_default_domain"
					v-model="defaultDomain"
					type="text"
					name="ootp_default_domain">
			</p>
			<p>
				<label for="ootp_user_settings">{{ $t('openotpsign', 'OpenOTP User settings') }}</label>
				<input id="ootp_user_settings"
					v-model="userSettings"
					type="text"
					name="ootp_user_settings">
			</p>
		</div>
		<div id="proxy" class="section">
			<h2>{{ $t('openotpsign', 'Proxy Settings') }}</h2>
			<p>
				<CheckboxRadioSwitch :checked.sync="useProxy">
					Use a proxy
				</CheckboxRadioSwitch>
			</p>
			<p>
				<label for="proxy_host">{{ $t('openotpsign', 'Proxy Host') }}</label>
				<input id="proxy_host"
					v-model="proxyHost"
					type="text"
					name="proxy_host"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_port">{{ $t('openotpsign', 'Proxy Port') }}</label>
				<input id="proxy_port"
					v-model="proxyPort"
					type="text"
					name="proxy_port"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_username">{{ $t('openotpsign', 'Proxy Username') }}</label>
				<input id="proxy_username"
					v-model="proxyUsername"
					type="text"
					name="proxy_username"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_password">{{ $t('openotpsign', 'Proxy Password') }}</label>
				<input id="proxy_password"
					v-model="proxyPassword"
					type="text"
					name="proxy_password"
					:disabled="!useProxy">
			</p>
			<p>
				<button @click="saveSettings">
					Save
				</button>
			</p>
			<p v-if="success" id="save_success">
				{{ $t('openotpsign', 'Your settings have been saved succesfully') }}
			</p>
			<p v-if="failure" id="save_failure">
				{{ $t('openotpsign', 'There was an error saving settings') }}
			</p>
		</div>
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
	width: 320px;
}

#message_status {
	padding: 6px 15px;
	border-radius: 3px;
}

.error {
	background: var(--color-error);
}

.success {
	background: var(--color-success);
}

#server_message {
	border: solid var(--color-success) 1px;
	display: inline-block;
	padding: 5px;
}

#save_success {
	color: green;
}

#save_failure {
	color: red;
}
</style>
