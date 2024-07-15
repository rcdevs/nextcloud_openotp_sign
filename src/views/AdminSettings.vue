<!--
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
-->

<template>
	<rcdevsMain>
		<rcdevsSettingsContainer id="rcdevsOosAppName">
			<rcdevsSettingsHeader>
				<rcdevsSettingsTitle>{{ getT('OpenOTP Sign Settings') }}</rcdevsSettingsTitle>
				<rcdevsSettingsItem>{{ getT('Installed version') }} : {{ installedVersion }}</rcdevsSettingsItem>
				<rcdevsSettingsItem>{{ getT('Enter your OpenOTP servers settings in the fields below.') }}</rcdevsSettingsItem>
				<rcdevsSettingsItem>{{ getT('After each settings modification, please save your settings.') }}</rcdevsSettingsItem>
			</rcdevsSettingsHeader>

			<!-- Loop servers -->
			<rcdevsSettingsPartsContainer>
				<rcdevsSettingsRow v-for="(serverUrl, cptServer) in serversUrls">
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('OpenOTP server URL') + ' #' + (parseInt(cptServer) + 1) }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input :id="'serverUrl' + cptServer" :ref="'serverUrl' + cptServer" v-model="serversUrls[cptServer]" type="text" :name="'serverUrl' + cptServer" maxlength="300" :placeholder="`${placeHolderServerUrl}`" />
						<deleteIcon @click="resetValueAndCo(`serverUrl${cptServer}`)">x</deleteIcon>
					</rcdevsSettingsItem>

					<rcdevsSettingsItem class="rcdevsSettingsImage" @click="testConnection(cptServer)">
						<transition name="fade">
							<img v-if="!reqServersUrls[cptServer].enable" class="rcdevsClickable rcdevsStatusLoader" :src="disableImg" />
							<img v-if="reqServersUrls[cptServer].request" class="rcdevsClickable rcdevsStatusLoader rcdevsStatusRequest" :src="requestImg" />
							<img v-if="!reqServersUrls[cptServer].request && reqServersUrls[cptServer].status" class="rcdevsClickable rcdevsStatusLoader" :src="successImg" />
							<img v-if="!reqServersUrls[cptServer].request && !reqServersUrls[cptServer].status" class="rcdevsClickable rcdevsStatusLoader" :src="failureImg" />
						</transition>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>

				<!-- Client ID -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('OpenOTP client ID') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="openotp_client_id" ref="clientId" v-model="clientId" type="text" :name="openotp_client_id" maxlength="300" :placeholder="`${placeHolderClientId}`" />
						<deleteIcon @click="resetValueAndCo('clientId')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>

				<!-- API key -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('OpenOTP API key') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="api_key" ref="apiKey" v-model="apiKey" type="text" name="api_key" maxlength="256" :placeholder="`${placeHolderApiKey}`" />
						<deleteIcon @click="resetValueAndCo('apiKey')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>
			</rcdevsSettingsPartsContainer>
		</rcdevsSettingsContainer>

		<rcdevsSettingsContainer id="rcdevsOosProxy">
			<rcdevsSettingsPartsContainer>
				<rcdevsSettingsRow>
					<NcCheckboxRadioSwitch class="rcdevsSettingsChkBox" :checked.sync="useProxy" type="switch">{{ getT('Use a proxy') }}</NcCheckboxRadioSwitch>
				</rcdevsSettingsRow>

				<!-- Proxy host -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('Proxy host') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="proxyHost" ref="proxyHost" v-model="proxyHost" type="text" name="proxyHost" maxlength="255" />
						<deleteIcon @click="resetValueAndCo('proxyHost')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>

				<!-- Proxy Port -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('Proxy port') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="proxy_port" ref="proxyPort" v-model="proxyPort" type="number" name="proxy_port" min="1" max="65535" />
						<deleteIcon @click="resetValueAndCo('proxyPort')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>

				<!-- Proxy User -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('Proxy username') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="proxy_username" ref="proxyUsername" v-model="proxyUsername" type="text" name="proxy_username" maxlength="255" />
						<deleteIcon @click="resetValueAndCo('proxyUsername')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>

				<!-- Proxy password -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('Proxy password') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="proxy_password" ref="proxyPassword" v-model="proxyPassword" type="text" name="proxy_password" maxlength="255" />
						<deleteIcon @click="resetValueAndCo('proxyPassword')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>
			</rcdevsSettingsPartsContainer>

			<rcdevsSettingsPartsContainer>
				<button class="rcdevsTestConnection" @click="testConnection()">
					{{ getT('Test connection') }}
				</button>
			</rcdevsSettingsPartsContainer>
		</rcdevsSettingsContainer>

		<rcdevsSettingsContainer id="rcdevsOosSignParameters">
			<rcdevsSettingsHeader>
				<rcdevsSettingsTitle>{{ getT('Signatures Parameters') }}</rcdevsSettingsTitle>
			</rcdevsSettingsHeader>

			<rcdevsSettingsPartsContainer>
				<!-- Enable Seal -->
				<rcdevsSettingsRow>
					<NcCheckboxRadioSwitch class="rcdevsSettingsChkBox" :checked.sync="enableOtpSeal" type="switch">{{ getT('Enable OpenOTP seal') }}</NcCheckboxRadioSwitch>
				</rcdevsSettingsRow>

				<!-- Enable Sign -->
				<rcdevsSettingsRow>
					<NcCheckboxRadioSwitch class="rcdevsSettingsChkBox" :checked.sync="enableOtpSign" type="switch">{{ getT('Enable OpenOTP signature') }}</NcCheckboxRadioSwitch>
				</rcdevsSettingsRow>

				<!-- Enable Sign Standard -->
				<rcdevsSettingsRow>
					<NcCheckboxRadioSwitch class="rcdevsSettingsChkBox" :checked.sync="signTypeStandard" type="switch">{{ getT('Enable Standard signature') }}</NcCheckboxRadioSwitch>
				</rcdevsSettingsRow>

				<!-- Enable Sign Advanced -->
				<rcdevsSettingsRow>
					<NcCheckboxRadioSwitch class="rcdevsSettingsChkBox" :checked.sync="signTypeAdvanced" type="switch">{{ getT('Enable Advanced signature') }}</NcCheckboxRadioSwitch>
				</rcdevsSettingsRow>

				<rcdevsSettingsRow>
					<NcCheckboxRadioSwitch class="rcdevsSettingsChkBox" :checked.sync="overwrite" type="switch">{{ getT('Overwrite the original PDF file with its signed/sealed copy (default: time-stamped copy)') }}</NcCheckboxRadioSwitch>
				</rcdevsSettingsRow>

				<!-- Textual Complements : Seal -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('Textual complement of sealed file') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="openotp_textual_complement_seal" ref="textualComplementSeal" v-model="textualComplementSeal" type="text" :name="openotp_textual_complement_seal" maxlength="66" :placeholder="`${placeHolderTextualComplementSeal}`" />
						<deleteIcon @click="resetValueAndCo('textualComplementSeal')">x</deleteIcon>
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="templateFilename">
						<label>{{ getTemplateFilenameSeal }}</label>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>

				<!-- Textual Complements : Signed -->
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						{{ getT('Textual complement of signed file') }}
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="openotp_textual_complement_sign" ref="textualComplementSign" v-model="textualComplementSign" type="text" :name="openotp_textual_complement_sign" maxlength="66" :placeholder="`${placeHolderTextualComplementSign}`" />
						<deleteIcon @click="resetValueAndCo('textualComplementSign')">x</deleteIcon>
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="templateFilename">
						<label>{{ getTemplateFilenameSign }}</label>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>
			</rcdevsSettingsPartsContainer>
		</rcdevsSettingsContainer>

		<rcdevsSettingsContainer id="rcdevsOosCron">
			<rcdevsSettingsHeader>
				<rcdevsSettingsTitle>{{ getT('Cron Parameters') }}</rcdevsSettingsTitle>
			</rcdevsSettingsHeader>

			<rcdevsSettingsPartsContainer>
				<rcdevsSettingsRow>
					{{ getT('To check asynchronous signature requests, you need to define the execution frequency of the background task that checks the status of these signatures.') }}
				</rcdevsSettingsRow>
				<rcdevsSettingsRow class="rcdevsRedNote">
					{{ getT("Please note that for this periodicity to be honored, it is necessary to configure NextCloud background jobs setting with 'Cron' value and to define the crontab periodicity accordingly.") }}
				</rcdevsSettingsRow>
				<rcdevsSettingsRow class="rcdevsReadMore">
					&#10132; <a href="https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/background_jobs_configuration.html#cron" target="_blank">{{ getT('More information here') }}</a>
				</rcdevsSettingsRow>
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel"> {{ getT('Periodicity') }} (mns) </rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<input id="cron_interval" ref="cronInterval" v-model="cronInterval" type="number" name="cron_interval" :min="MIN_CRON_INTERVAL" :max="MAX_CRON_INTERVAL" />
						<deleteIcon @click="resetValueAndCo('cronInterval')">x</deleteIcon>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel"> {{ getT('Checking Cron') }}</rcdevsSettingsItem>
					<rcdevsSettingsItem id="cron_check" name="cron_check" class="rcdevsSettingsInput" :class="[reqCron.status]">
						{{ reqCron.message }}
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>
				<rcdevsSettingsRow class="rcdevsSettingsButton">
					<button v-if="reqCron.code === 0" @click="reset_job()">
						{{ getT('Re-enable cron') }}
					</button>
				</rcdevsSettingsRow>
			</rcdevsSettingsPartsContainer>

			<rcdevsSettingsFooter>
				<rcdevsSettingsRow>
					<rcdevsSettingsItem class="rcdevsSettingsLabel">
						<button @click="saveSettings">
							{{ getT('Save') }}
						</button>
					</rcdevsSettingsItem>
					<rcdevsSettingsItem class="rcdevsSettingsInput">
						<transition name="fade">
							<p v-if="!saved" class="save_warning">
								{{ getT('Do not forget to save your settings!') }}
							</p>
							<p v-if="success" id="save_success">
								{{ getT('Your settings have been saved succesfully') }}
							</p>
							<p v-if="failure" id="save_failure">
								{{ getT('There was an error saving settings') }}
							</p>
						</transition>
					</rcdevsSettingsItem>
				</rcdevsSettingsRow>
			</rcdevsSettingsFooter>
		</rcdevsSettingsContainer>
	</rcdevsMain>
</template>

<script>
import {loadState} from '@nextcloud/initial-state';
import axios from '@nextcloud/axios';
import {generateFilePath, generateOcsUrl} from '@nextcloud/router';
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js';
import {appName} from '../javascript/config.js';
import {getT} from '../javascript/utility.js';

const reqServerUrlItem = {
	enable: true,
	request: false,
	code: 0,
	message: '',
	status: false,
};
const reqCron = {
	enable: false,
	request: false,
	code: 0,
	message: '',
	status: 'chk_0',
};

export default {
	name: 'AdminSettings',
	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		const baseUrl = `apps/${appName}`;

		return {
			getT: getT,
			baseUrl,
			reqServerUrlItem,
			reqServersUrls: [],
			reqCron,
			// From DB table Settings `oc_appconfig`
			apiKey: this.$parent.apiKey,
			asyncTimeout: this.$parent.asyncTimeout,
			clientId: this.$parent.clientId,
			cronInterval: this.$parent.cronInterval,
			// enableDemoMode: !!this.$parent.enableDemoMode,
			enableDemoMode: this.$parent.enableDemoMode,
			enableOtpSeal: this.$parent.enableOtpSeal,
			enableOtpSign: this.$parent.enableOtpSign,
			installedVersion: this.$parent.installedVersion,
			proxyHost: this.$parent.proxyHost,
			proxyPassword: this.$parent.proxyPassword,
			proxyPort: this.$parent.proxyPort,
			proxyUsername: this.$parent.proxyUsername,
			serversUrls: this.$parent.serversUrls,
			overwrite: this.$parent.overwrite,
			signTypeAdvanced: this.$parent.signTypeAdvanced,
			signTypeStandard: this.$parent.signTypeStandard,
			syncTimeout: this.$parent.syncTimeout,
			textualComplementSeal: this.$parent.textualComplementSeal,
			textualComplementSign: this.$parent.textualComplementSign,
			useProxy: this.$parent.useProxy,
			watermarkText: this.$parent.watermarkText,

			success: false,
			failure: false,
			saved: false,
			MIN_TIMEOUT: 1,
			MAX_SYNC_TIMEOUT: 5,
			MAX_ASYNC_TIMEOUT: 30,
			MIN_CRON_INTERVAL: 1,
			MAX_CRON_INTERVAL: 15,

			templateFilenameSeal: '',
			templateFilenameSign: '',
		};
	},

	computed: {
		getTemplateFilenameSeal: function () {
			return this.changeTemplateFilename(this.textualComplementSeal);
		},
		getTemplateFilenameSign: function () {
			return this.changeTemplateFilename(this.textualComplementSign);
		},
	},

	beforeMount() {
		const initialSettings = loadState(appName, 'initialSettings');

		console.log(`initialSettings:[${JSON.stringify(initialSettings)}]`);

		this.apiKey = initialSettings.apiKey;
		this.asyncTimeout = initialSettings.asyncTimeout;
		this.clientId = initialSettings.clientId;
		this.cronInterval = initialSettings.cronInterval;
		this.enableDemoMode = initialSettings.enableDemoMode;
		this.enableOtpSeal = initialSettings.enableOtpSeal;
		this.enableOtpSign = initialSettings.enableOtpSign;
		this.installedVersion = initialSettings.installedVersion;
		this.overwrite = initialSettings.overwrite;
		this.proxyHost = initialSettings.proxyHost;
		this.proxyPassword = initialSettings.proxyPassword;
		this.proxyPort = initialSettings.proxyPort;
		this.proxyUsername = initialSettings.proxyUsername;
		this.serversUrls = initialSettings.serversUrls;
		this.signTypeAdvanced = initialSettings.signTypeAdvanced;
		this.signTypeStandard = initialSettings.signTypeStandard;
		this.syncTimeout = initialSettings.syncTimeout;
		this.textualComplementSeal = initialSettings.textualComplementSeal;
		this.textualComplementSign = initialSettings.textualComplementSign;
		this.useProxy = initialSettings.useProxy;
		this.watermarkText = initialSettings.watermarkText;

		console.log(`this.serversUrls.length:[${this.serversUrls.length}]`);
		for (let cptServer = 0; cptServer < this.serversUrls.length; cptServer++) {
			this.reqServersUrls[cptServer] = JSON.parse(JSON.stringify(this.reqServerUrlItem));
		}
	},

	mounted() {
		this.loadingImg = generateFilePath(appName, '', 'img/') + 'OpenOtpSign.svg';
		this.requestImg = generateFilePath(appName, '', 'img/') + 'OpenOtpSign_gray.svg';
		this.successImg = generateFilePath(appName, '', 'img/') + 'OpenOtpSign_green.svg';
		this.failureImg = generateFilePath(appName, '', 'img/') + 'OpenOtpSign_red.svg';
		this.disableImg = generateFilePath(appName, '', 'img/') + 'OpenOtpSign_disabled.svg';

		this.reqCron = {
			enable: false,
			request: false,
			code: 0,
			message: '',
			status: 'chk_0',
		};

		this.placeHolderServerUrl = this.getT('Write OpenOTP Sign url here');
		this.placeHolderClientId = this.getT('Write OpenOTP Client policy Id');
		this.placeHolderApiKey = this.getT('Get API Key from RCDevs');

		// Add Event Listener on all inputs
		const inputs = document.querySelectorAll('input');
		inputs.forEach((input) => {
			input.addEventListener('change', this.inputNotSaved);
		});

		// Add Event listener on NcCheckboxRadioSwitch (FYI, focus on main generated span tag to check if radio is checked or not: the radio does not throw an event)
		const attrObserver = new MutationObserver((mutations) => {
			mutations.forEach((mu) => {
				if (mu.type === 'attributes' && mu.attributeName === 'class') {
					this.inputNotSaved();
				}
			});
		});

		const ELS_test = document.querySelectorAll('.rcdevsSettingsChkBox');
		ELS_test.forEach((el) => attrObserver.observe(el, {attributes: true}));

		document.querySelectorAll('.rcdevsSettingsChkBox').forEach((btn) => {
			btn.addEventListener('click', () => ELS_test.forEach((el) => el.classList.toggle(btn.dataset.class)));
		});

		this.saved = true;

		// Call server check
		this.testConnection();

		// Check if Cron is enabled or disabled
		this.retrieveCronStatus();
	},

	methods: {
		changeTemplateFilename(textualComplement) {
			let rightNow = Math.floor(Date.now() / 1000);
			const contract = getT('Contract');

			if (textualComplement === '') {
				return `${contract}_${this.formatMe(new Date(rightNow * 1000))}.pdf`;
			} else {
				return `${contract}_${textualComplement}_${this.formatMe(new Date(rightNow * 1000))}.pdf`;
			}
		},

		clearIcons() {
			try {
				for (let cptServer = 0; cptServer < this.serversUrls.length; cptServer++) {
					this.reqServersUrls[cptServer].enable = false;
				}
			} catch (error) {
				console.error(error);
			}
		},

		dateHelperFactory() {
			const padZero = (val, len = 2) => `${val}`.padStart(len, `0`);
			const setValues = (date) => {
				let vals = {
					yyyy: date.getFullYear(),
					m: date.getMonth() + 1,
					d: date.getDate(),
					h: date.getHours(),
					mi: date.getMinutes(),
					s: date.getSeconds(),
				};
				Object.keys(vals)
					.filter((k) => k !== `yyyy`)
					.forEach((k) => (vals[k[0] + k] = padZero(vals[k], (k === `ms` && 3) || 2)));
				return vals;
			};

			return (date) => ({
				values: setValues(date),
				toArr(...items) {
					return items.map((i) => this.values[i]);
				},
			});
		},

		formatMe(date) {
			const dateHelper = this.dateHelperFactory();
			const vals = `yyyy,mm,dd,hh,mmi,ss`.split(`,`);
			const myDate = dateHelper(date).toArr(...vals);
			return `${myDate.slice(0, 3).join(`-`)} ${myDate.slice(3, 6).join(`:`)}.${myDate.slice(-1)[0]}`;
		},

		inputNotSaved(event) {
			this.saved = false;
		},

		reset_job() {
			this.reqCron.enable = true;
			this.reqCron.request = true;

			axios({
				url: generateOcsUrl(`${this.baseUrl}/api/v1/settings/job/reset`),
				method: 'GET',
				timeout: 2000,
				data: {
					xsrfCookieName: 'XSRF-TOKEN',
					xsrfHeaderName: 'X-XSRF-TOKEN',
				},
			})
				.then((response) => {
					console.info(`Reset Job reponse : ${JSON.stringify(response)}`);
					// Restore GUI
					this.reqCron.enable = true;
					this.reqCron.request = false;
					// Handle response
					this.reqCron.code = response.data.code;
					this.reqCron.message = response.data.message;
					this.reqCron.status = `chk_${response.data.code}`;
				})
				.catch((error) => {
					return error.data;
				});
		},

		resetValueAndCo(refData) {
			this[refData] = '';
			this.$refs[refData].focus();

			this.clearIcons();
			this.saved = false;
		},

		retrieveCronStatus() {
			this.reqCron.enable = true;
			this.reqCron.request = true;

			axios({
				url: generateOcsUrl(`${this.baseUrl}/api/v1/settings/check/cron`),
				method: 'GET',
				timeout: 2000,
				data: {
					xsrfCookieName: 'XSRF-TOKEN',
					xsrfHeaderName: 'X-XSRF-TOKEN',
				},
			})
				.then((response) => {
					console.info(`Cron status reponse : ${JSON.stringify(response)}`);
					// Restore GUI
					this.reqCron.enable = true;
					this.reqCron.request = false;
					// Handle response
					this.reqCron.code = response.data.code;
					this.reqCron.message = getT(response.data.message);
					this.reqCron.status = `chk_${response.data.code}`;
				})
				.catch((error) => {
					return error.data;
				});
		},

		saveSettings() {
			this.success = false;
			this.failure = false;

			if (this.syncTimeout < this.MIN_TIMEOUT || this.syncTimeout > this.MAX_SYNC_TIMEOUT || this.asyncTimeout < this.MIN_TIMEOUT || this.asyncTimeout > this.MAX_ASYNC_TIMEOUT || this.cronInterval < this.MIN_CRON_INTERVAL || this.cronInterval > this.MAX_CRON_INTERVAL) {
				this.failure = true;
				return;
			}

			axios
				.post(generateOcsUrl(this.baseUrl + '/api/v1/settings/save'), {
					api_key: this.apiKey,
					async_timeout: this.asyncTimeout,
					client_id: this.clientId,
					cron_interval: this.cronInterval,
					enable_demo_mode: this.enableDemoMode,
					enable_otp_seal: this.enableOtpSeal,
					enable_otp_sign: this.enableOtpSign,
					overwrite: this.overwrite,
					proxy_host: this.proxyHost,
					proxy_password: this.proxyPassword,
					proxy_port: this.proxyPort,
					proxy_username: this.proxyUsername,
					servers_urls: this.serversUrls,
					sign_type_advanced: this.signTypeAdvanced,
					sign_type_standard: this.signTypeStandard,
					sync_timeout: this.syncTimeout,
					textual_complement_seal: this.textualComplementSeal,
					textual_complement_sign: this.textualComplementSign,
					use_proxy: this.useProxy,
					watermark_text: this.watermarkText,
				})
				.then((response) => {
					console.info(`Save Settings reponse : ${JSON.stringify(response)}`);
					this.success = true;
					this.saved = true;
				})
				.catch((error) => {
					this.failure = true;
					this.saved = false;
					// eslint-disable-next-line
					console.log(error);
				});
		},

		testConnection(idxServer = -1) {
			/**
			 * If not parameter sent, proceed for all servers
			 * Otherwise process the server sent in parameter
			 */
			let firstSrv = 0;
			let lastSrv = this.serversUrls.length - 1;

			if (idxServer !== -1) {
				// Only one server to check
				lastSrv = firstSrv = idxServer;
			}

			for (let cptServer = firstSrv; cptServer <= lastSrv; cptServer++) {
				console.log(`Test cx srv ${cptServer}`);
				// Prepare GUI
				this.reqServersUrls[cptServer].enable = true;
				this.reqServersUrls[cptServer].request = true;
				// Reset response
				this.reqServersUrls[cptServer].code = 0;
				this.reqServersUrls[cptServer].message = '';
				this.reqServersUrls[cptServer].status = false;

				axios({
					url: generateOcsUrl(`${this.baseUrl}/api/v1/settings/check/server`),
					method: 'POST',
					timeout: 2000,
					data: {
						xsrfCookieName: 'XSRF-TOKEN',
						xsrfHeaderName: 'X-XSRF-TOKEN',
						serverNumber: cptServer,
					},
				})
					.then((response) => {
						// Handle response
						let localReqServerUrlItem = JSON.parse(JSON.stringify(this.reqServerUrlItem));
						if (response.data.code === -1) {
							// the server URL is empty
							localReqServerUrlItem.enable = false;
						} else {
							localReqServerUrlItem.enable = true;
						}
						localReqServerUrlItem.request = false;
						localReqServerUrlItem.code = response.data.code;
						localReqServerUrlItem.message = response.data.message;
						localReqServerUrlItem.status = response.data.code === 1 || response.data.code === -1 ? true : false;

						// Restore GUI
						this.$set(this.reqServersUrls, cptServer, localReqServerUrlItem);
					})
					.catch((error) => {
						return error.data;
					});
			}
		},
	},
};
</script>

<style>
@import '../styles/rcdevsNxC.css';
@import '../styles/rcdevsSettings.css';
@import '../styles/rcdevsStyle.css';
@import '../styles/rcdevsUtility.css';
</style>
