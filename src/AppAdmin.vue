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
		<div id="openotpsign" class="section">
			<h2>{{ $t('openotpsign', 'OpenOTP Sign Settings') }}</h2>
			<p>
				{{ $t('openotpsign', 'Enter your OpenOTP server settings in the fields below.') }}
			</p>
			<div v-for="(item, index) in statusesRequesting" :key="index">
				<p>
					<label :for="'ootp_server_url' + index">{{ $t('openotpsign', 'OpenOTP server URL #' + (parseInt(index) + 1)) }}</label>
					<input :id="'ootp_server_url' + index"
						v-model="serverUrls[index]"
						type="text"
						:name="'ootp_server_url' + index"
						maxlength="300"
						:placeholder="'https://myserver' + (parseInt(index) + 1) + ':8443/openotp/'"
						@input="enableSslSetting">
					<button @click="testConnection(index, serverUrls[index])">
						{{ $t('openotpsign', 'Test') }}
					</button>
					<transition name="fade">
						<span v-if="!statusesRequesting[index]" class="message_status" :class="messageStatusClasses[index]" />
					</transition>
					<img v-if="statusesRequesting[index]" class="status_loader" :src="loadingImg">
				</p>
				<transition name="fade">
					<pre v-if="serverMessages[index].length" class="server_message">{{ serverMessages[index] }}</pre>
				</transition>
			</div>
			<p>
				<CheckboxRadioSwitch :checked.sync="ignoreSslErrors" :disabled="!sslSettingEnabled">
					{{ $t('openotpsign', 'Ignore SSL/TLS certificate errors') }}
				</CheckboxRadioSwitch>
			</p>
			<p>
				<label for="ootp_client_id">{{ $t('openotpsign', 'OpenOTP client id') }}</label>
				<input id="ootp_client_id"
					v-model="clientId"
					type="text"
					name="ootp_client_id"
					maxlength="256"
					placeholder="Nextcloud">
			</p>
			<p>
				<label for="ootp_default_domain">{{ $t('openotpsign', 'OpenOTP Default Domain') }}</label>
				<input id="ootp_default_domain"
					v-model="defaultDomain"
					type="text"
					name="ootp_default_domain"
					maxlength="64">
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
					{{ $t('openotpsign', 'Use a proxy') }}
				</CheckboxRadioSwitch>
			</p>
			<p>
				<label for="proxy_host">{{ $t('openotpsign', 'Proxy Host') }}</label>
				<input id="proxy_host"
					v-model="proxyHost"
					type="text"
					name="proxy_host"
					maxlength="255"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_port">{{ $t('openotpsign', 'Proxy Port') }}</label>
				<input id="proxy_port"
					v-model="proxyPort"
					type="number"
					name="proxy_port"
					min="1"
					max="65535"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_username">{{ $t('openotpsign', 'Proxy Username') }}</label>
				<input id="proxy_username"
					v-model="proxyUsername"
					type="text"
					name="proxy_username"
					maxlength="255"
					:disabled="!useProxy">
			</p>
			<p>
				<label for="proxy_password">{{ $t('openotpsign', 'Proxy Password') }}</label>
				<input id="proxy_password"
					v-model="proxyPassword"
					type="text"
					name="proxy_password"
					maxlength="255"
					:disabled="!useProxy">
			</p>
		</div>
		<div id="signed_file" class="section">
			<h2>{{ $t('openotpsign', 'Signed / sealed PDF File') }}</h2>
			<CheckboxRadioSwitch
				:checked.sync="signedFile"
				value="copy"
				name="signed_file_radio"
				type="radio">
				{{ $t('openotpsign', 'Make a signed / sealed copy of the original PDF file') }}
			</CheckboxRadioSwitch>
			<CheckboxRadioSwitch
				:checked.sync="signedFile"
				value="overwrite"
				name="signed_file_radio"
				type="radio">
				{{ $t('openotpsign', 'Overwrite the original PDF file with its signed / sealed copy') }}
			</CheckboxRadioSwitch>
		</div>
		<div id="save" class="section">
			<p>
				<button @click="saveSettings">
					{{ $t('openotpsign', 'Save') }}
				</button>
			</p>
			<transition name="fade">
				<p v-if="success" id="save_success">
					{{ $t('openotpsign', 'Your settings have been saved succesfully') }}
				</p>
				<p v-if="failure" id="save_failure">
					{{ $t('openotpsign', 'There was an error saving settings') }}
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
const messageStatusClasses = {}
const serverMessages = {}

for (let i = 0; i < NB_SERVERS; ++i) {
	statusesRequesting[i] = false
	messageStatusClasses[i] = 'error'
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
			messageStatusClasses,
			serverMessages,
			ignoreSslErrors: !!this.$parent.ignoreSslErrors,
			sslSettingEnabled: (function(serverUrls) {
				for (let i = 0; i < serverUrls.length; ++i) {
					if (serverUrls[i].startsWith('https://')) {
						return true
					}
				}

				return false
			}(serverUrls)),
			clientId: this.$parent.clientId,
			defaultDomain: this.$parent.defaultDomain,
			userSettings: this.$parent.userSettings,
			useProxy: !!this.$parent.useProxy,
			proxyHost: this.$parent.proxyHost,
			proxyPort: this.$parent.proxyPort,
			proxyUsername: this.$parent.proxyUsername,
			proxyPassword: this.$parent.proxyPassword,
			signedFile: this.$parent.signedFile,
			success: false,
			failure: false,
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
			const baseUrl = generateUrl('/apps/openotpsign')

			axios.post(baseUrl + '/settings', {
				server_urls: this.serverUrls,
				ignore_ssl_errors: this.ignoreSslErrors,
				client_id: this.clientId,
				default_domain: this.defaultDomain,
				user_settings: this.userSettings,
				use_proxy: this.useProxy,
				proxy_host: this.proxyHost,
				proxy_port: this.proxyPort,
				proxy_username: this.proxyUsername,
				proxy_password: this.proxyPassword,
				signed_file: this.signedFile,
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
			let enable = false
			for (let i = 0; i < this.serverUrls.length; ++i) {
				if (this.serverUrls[i].startsWith('https://')) {
					enable = true
					break
				}
			}

			this.sslSettingEnabled = enable
		},
		testConnection(serverNum, serverUrl) {
			this.statusesRequesting[serverNum] = true
			this.serverMessages[serverNum] = ''
			const baseUrl = generateUrl('/apps/openotpsign')

			axios.post(baseUrl + '/check_server_url', {
				server_url: serverUrl,
				ignore_ssl_errors: this.ignoreSslErrors,
				use_proxy: this.useProxy,
				proxy_host: this.proxyHost,
				proxy_port: this.proxyPort,
				proxy_username: this.proxyUsername,
				proxy_password: this.proxyPassword,
			})
				.then(response => {
					this.statusesRequesting[serverNum] = false
					if (response.data.status === true) {
						this.messageStatusClasses[serverNum] = 'success'
						this.serverMessages[serverNum] = response.data.message
					} else {
						this.messageStatusClasses[serverNum] = 'error'
						this.serverMessages[serverNum] = ''
					}
				})
				.catch(error => {
					this.statusesRequesting[serverNum] = false
					this.messageStatusClasses[serverNum] = 'error'
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
	position: absolute;
	margin-top: 3px;
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
