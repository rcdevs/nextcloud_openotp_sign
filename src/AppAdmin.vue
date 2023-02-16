<template>
	<!--
	*
	* @copyright Copyright (c) 2021, RCDevs (info@rcdevs.com)
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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU Affero General Public License for more details.
	*
	* You should have received a copy of the GNU Affero General Public License
	* along with this program.  If not, see <https://www.gnu.org/licenses/>.
	*
	-->
	<div>
		<div id="openotp_sign" class="section">
			<h2>{{ $t('openotp_sign', 'OpenOTP Sign Settings') }}</h2>
			<p>
				{{ $t('openotp_sign', 'Enter your OpenOTP server settings in the fields below.') }}
			</p>
			<div v-for="(statusRequesting, index) in statusesRequesting" :key="index">
				<p>
					<label :for="'ootp_server_url' + index">{{ $t('openotp_sign', 'OpenOTP server URL #' + (parseInt(index) + 1)) }}</label>
					<input :id="'ootp_server_url' + index"
						v-model="serverUrls[index]"
						type="text"
						:name="'ootp_server_url' + index"
						maxlength="300"
						:placeholder="'https://myserver' + (parseInt(index) + 1) + ':8443/openotp/'">
					<button @click="testConnection(index, serverUrls[index])">
						{{ $t('openotp_sign', 'Test') }}
					</button>
					<transition name="fade">
						<span v-if="!statusRequesting" class="message_status" :class="[serverMessages[index].length ? 'success' : 'error']" />
					</transition>
					<img v-if="statusRequesting" class="status_loader" :src="loadingImg">
				</p>
				<transition name="fade">
					<pre v-if="serverMessages[index].length" class="server_message">{{ serverMessages[index] }}</pre>
				</transition>
			</div>
			<p>
				<label for="ootp_client_id">{{ $t('openotp_sign', 'OpenOTP client id') }}</label>
				<input id="ootp_client_id"
					v-model="clientId"
					type="text"
					name="ootp_client_id"
					maxlength="256"
					placeholder="Nextcloud">
			</p>
			<p>
				<label for="ootp_default_domain">{{ $t('openotp_sign', 'OpenOTP Default Domain') }}</label>
				<input id="ootp_default_domain"
					v-model="defaultDomain"
					type="text"
					name="ootp_default_domain"
					maxlength="64">
			</p>
			<p>
				<label for="ootp_user_settings">{{ $t('openotp_sign', 'OpenOTP User settings') }}</label>
				<input id="ootp_user_settings"
					v-model="userSettings"
					type="text"
					name="ootp_user_settings">
			</p>
		</div>
		<div id="proxy" class="section">
			<h2>{{ $t('openotp_sign', 'Proxy Settings') }}</h2>
			<p>
				<CheckboxRadioSwitch :checked.sync="useProxy">
					{{ $t('openotp_sign', 'Use a proxy') }}
				</CheckboxRadioSwitch>
			</p>
			<p>
				<label for="proxy_host">{{ $t('openotp_sign', 'Proxy Host') }}</label>
				<input id="proxy_host"
					v-model="proxyHost"
					type="text"
					name="proxy_host"
					maxlength="255"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_port">{{ $t('openotp_sign', 'Proxy Port') }}</label>
				<input id="proxy_port"
					v-model="proxyPort"
					type="number"
					name="proxy_port"
					min="1"
					max="65535"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_username">{{ $t('openotp_sign', 'Proxy Username') }}</label>
				<input id="proxy_username"
					v-model="proxyUsername"
					type="text"
					name="proxy_username"
					maxlength="255"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_password">{{ $t('openotp_sign', 'Proxy Password') }}</label>
				<input id="proxy_password"
					v-model="proxyPassword"
					type="text"
					name="proxy_password"
					maxlength="255"
					:disabled="!useProxy">
			</p>
		</div>
		<div id="sign_scope" class="section">
			<h2>{{ $t('openotp_sign', 'Signature scope') }}</h2>
			<CheckboxRadioSwitch
				:checked.sync="signScope"
				value="Local"
				name="sign_scope_radio"
				type="radio">
				{{ $t('openotp_sign', 'Local: Advanced signature with user certificates issued by internal WebADM CA') }}
			</CheckboxRadioSwitch>
			<CheckboxRadioSwitch
				:checked.sync="signScope"
				value="Global"
				name="sign_scope_radio"
				type="radio">
				{{ $t('openotp_sign', 'Global: Advanced signature with user certificates issued by RCDevs Root CA') }}
			</CheckboxRadioSwitch>
			<CheckboxRadioSwitch
				:checked.sync="signScope"
				value="eIDAS"
				name="sign_scope_radio"
				type="radio">
				{{ $t('openotp_sign', 'eIDAS: Qualified signature with external eIDAS signing devices (ex. eID Cards)') }}
			</CheckboxRadioSwitch>
		</div>
		<div id="signed_file" class="section">
			<h2>{{ $t('openotp_sign', 'Signed / sealed PDF File') }}</h2>
			<CheckboxRadioSwitch
				:checked.sync="signedFile"
				value="copy"
				name="signed_file_radio"
				type="radio">
				{{ $t('openotp_sign', 'Make a signed / sealed copy of the original PDF file') }}
			</CheckboxRadioSwitch>
			<CheckboxRadioSwitch
				:checked.sync="signedFile"
				value="overwrite"
				name="signed_file_radio"
				type="radio">
				{{ $t('openotp_sign', 'Overwrite the original PDF file with its signed / sealed copy') }}
			</CheckboxRadioSwitch>
		</div>
		<div id="timeout" class="section">
			<h2>{{ $t('openotp_sign', 'Signature requests time out') }}</h2>
			<p>
				<label for="sync_timeout">{{ $t('openotp_sign', 'Self-signature ({min} - {max} minutes)', {min: MIN_TIMEOUT, max: MAX_SYNC_TIMEOUT}) }}</label>
				<input id="sync_timeout"
					v-model="syncTimeout"
					type="number"
					name="sync_timeout"
					:min="MIN_TIMEOUT"
					:max="MAX_SYNC_TIMEOUT">
			</p>
			<p>
				<label for="async_timeout">{{ $t('openotp_sign', 'Nextcloud / YumiSign user signature ({min} - {max} days)', {min: MIN_TIMEOUT, max: MAX_ASYNC_TIMEOUT}) }}</label>
				<input id="async_timeout"
					v-model="asyncTimeout"
					type="number"
					name="async_timeout"
					:min="MIN_TIMEOUT"
					:max="MAX_ASYNC_TIMEOUT">
			</p>
		</div>
		<div id="crontab" class="section">
			<h2>{{ $t('openotp_sign', 'Completion check of pending asynchronous signatures') }}</h2>
			<p style="white-space: pre;">
				{{ $t('openotp_sign', 'Define the execution periodicity of the background job that checks for completed signature requests.\n'
					+ 'Please note that for this periodicity to be honored, it is necessary to configure NextCloud background\njobs setting with \'Cron\' value and to define the crontab periodicity accordingly.') }}
			</p>
			<p>
				<label for="cron_interval">{{ $t('openotp_sign', 'Background job periodicity ({min} - {max} minutes)', {min: MIN_CRON_INTERVAL, max: MAX_CRON_INTERVAL}) }}</label>
				<input id="cron_interval"
					v-model="cronInterval"
					type="number"
					name="cron_interval"
					:min="MIN_CRON_INTERVAL"
					:max="MAX_CRON_INTERVAL">
			</p>
		</div>
		<div id="demo" class="section">
			<h2>{{ $t('openotp_sign', 'Demo mode') }}</h2>
			<p>
				{{ $t('openotp_sign', 'In demo mode, it is only possible to sign or seal PDF files, on which a watermark will be added.') }}
			</p>
			<p>
				<CheckboxRadioSwitch :checked.sync="enableDemoMode">
					{{ $t('openotp_sign', 'Enable demo mode') }}
				</CheckboxRadioSwitch>
			</p>
			<p>
				<label for="watermark_text">{{ $t('openotp_sign', 'Watermark text') }}</label>
				<input id="watermark_text"
					v-model="watermarkText"
					type="text"
					name="watermark_text"
					maxlength="255"
					:disabled="!enableDemoMode">
			</p>
		</div>
		<div id="save" class="section">
			<p>
				<button @click="saveSettings">
					{{ $t('openotp_sign', 'Save') }}
				</button>
			</p>
			<transition name="fade">
				<p v-if="success" id="save_success">
					{{ $t('openotp_sign', 'Your settings have been saved succesfully') }}
				</p>
				<p v-if="failure" id="save_failure">
					{{ $t('openotp_sign', 'There was an error saving settings') }}
				</p>
			</transition>
		</div>
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'

const NB_SERVERS = 2
const statusesRequesting = {}
const serverMessages = {}

for (let i = 0; i < NB_SERVERS; ++i) {
	statusesRequesting[i] = false
	serverMessages[i] = ''
}

export default {
	name: 'AppAdmin',
	components: {
		CheckboxRadioSwitch,
	},
	data() {
		const serverUrls = JSON.parse(this.$parent.serverUrls)

		return {
			serverUrls,
			statusesRequesting,
			serverMessages,
			clientId: this.$parent.clientId,
			defaultDomain: this.$parent.defaultDomain,
			userSettings: this.$parent.userSettings,
			useProxy: !!this.$parent.useProxy,
			proxyHost: this.$parent.proxyHost,
			proxyPort: this.$parent.proxyPort,
			proxyUsername: this.$parent.proxyUsername,
			proxyPassword: this.$parent.proxyPassword,
			signScope: this.$parent.signScope,
			signedFile: this.$parent.signedFile,
			syncTimeout: this.$parent.syncTimeout,
			asyncTimeout: this.$parent.asyncTimeout,
			cronInterval: this.$parent.cronInterval,
			enableDemoMode: !!this.$parent.enableDemoMode,
			watermarkText: this.$parent.watermarkText,
			success: false,
			failure: false,
			MIN_TIMEOUT: 1,
			MAX_SYNC_TIMEOUT: 5,
			MAX_ASYNC_TIMEOUT: 30,
			MIN_CRON_INTERVAL: 1,
			MAX_CRON_INTERVAL: 15,
		}
	},
	mounted() {
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'

		for (let i = 0; i < this.serverUrls.length; ++i) {
			this.testConnection(i, this.serverUrls[i])
		}
	},
	methods: {
		saveSettings() {
			this.success = false
			this.failure = false

			if (this.syncTimeout < this.MIN_TIMEOUT
				|| this.syncTimeout > this.MAX_SYNC_TIMEOUT
				|| this.asyncTimeout < this.MIN_TIMEOUT
				|| this.asyncTimeout > this.MAX_ASYNC_TIMEOUT
				|| this.cronInterval < this.MIN_CRON_INTERVAL
				|| this.cronInterval > this.MAX_CRON_INTERVAL) {
				this.failure = true
				return
			}

			const baseUrl = generateUrl('/apps/openotp_sign')

			axios.post(baseUrl + '/settings', {
				server_urls: this.serverUrls,
				client_id: this.clientId,
				default_domain: this.defaultDomain,
				user_settings: this.userSettings,
				use_proxy: this.useProxy,
				proxy_host: this.proxyHost,
				proxy_port: this.proxyPort,
				proxy_username: this.proxyUsername,
				proxy_password: this.proxyPassword,
				sign_scope: this.signScope,
				signed_file: this.signedFile,
				sync_timeout: this.syncTimeout,
				async_timeout: this.asyncTimeout,
				cron_interval: this.cronInterval,
				enable_demo_mode: this.enableDemoMode,
				watermark_text: this.watermarkText,
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
		testConnection(serverNum, serverUrl) {
			this.statusesRequesting[serverNum] = true
			this.serverMessages[serverNum] = ''
			const baseUrl = generateUrl('/apps/openotp_sign')

			axios.post(baseUrl + '/check_server_url', {
				server_url: serverUrl,
				use_proxy: this.useProxy,
				proxy_host: this.proxyHost,
				proxy_port: this.proxyPort,
				proxy_username: this.proxyUsername,
				proxy_password: this.proxyPassword,
			})
				.then(response => {
					this.statusesRequesting[serverNum] = false
					if (response.data.status === 'true') {
						this.serverMessages[serverNum] = response.data.message
					} else {
						this.serverMessages[serverNum] = ''
					}
				})
				.catch(error => {
					this.statusesRequesting[serverNum] = false
					this.serverMessages[serverNum] = ''
					// eslint-disable-next-line
					console.log(error)
				})
		},
	},
}
</script>
<style scoped>
label,
input {
	display: inline-block;
}

label {
	width: 230px;
}

input {
	width: 320px;
}

.message_status {
	padding: 6px 15px;
	border-radius: 3px;
}

.error {
	background: var(--color-error);
}

.success {
	background: var(--color-success);
}

.server_message {
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

.status_loader {
	margin-bottom: -12px;
	margin-left: -4px;
}

.fade-enter-active {
	transition: opacity .9s;
}

.fade-enter /* .fade-leave-active below version 2.1.8 */ {
	opacity: 0;
}

#ootp_server_url0 {
	margin-top: 30px;
}
</style>
