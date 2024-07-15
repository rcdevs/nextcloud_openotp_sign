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
	<NcModal class="rcdevsOosModal" :show.sync="modal" @close="closeModal" :outTransition="true" :aria-label="ncModalAriaLabel">
		<rcdevsOosModalContent ref="rcdevsOosModalForm">
			<rcdevsOosLogo>
				<img id="rcdevsLogo" :src="ui.pictures.rcdevsLogo" />
			</rcdevsOosLogo>

			<rcdevsOosModalTitle>
				{{ ui.title.chosen }}
			</rcdevsOosModalTitle>

			<rcdevsOosSettingsKO v-if="settingKO && this.rcdevsSettings.checked" id="errorSettings" class="alert alertDanger">
				{{ ui.messages.warning.server }}
			</rcdevsOosSettingsKO>

			<rcdevsOosSettingsKO v-if="actionSeal && disabledSeal && this.enabledOtp.checked" class="alert alertDanger disabledAction">
				{{ ui.messages.warning.disabled.seal }}
			</rcdevsOosSettingsKO>

			<rcdevsOosSettingsKO v-if="actionSign && disabledSign && this.enabledOtp.checked" class="alert alertDanger disabledAction">
				{{ ui.messages.warning.disabled.sign }}
			</rcdevsOosSettingsKO>

			<rcdevsWaitingOcs v-if="!this.rcdevsSettings.checked || !this.enabledOtp.checked || !this.signTypes.checked">
				<img :src="ui.pictures.loadingImg" />
			</rcdevsWaitingOcs>

			<rcdevsOosModalMainContainer v-if="(enabledSeal && actionSeal) || (enabledSign && actionSign)">
				<rcdevsOosRow v-if="!axiosTransaction.success">
					<img :src="ui.pictures.mobileSigningImg" style="max-height: 200px" />
				</rcdevsOosRow>

				<rcdevsOosRow class="chosenFile">
					<rcdevsOosItem>
						{{ file.message }}
					</rcdevsOosItem>
					<rcdevsOosItem class="filename">
						{{ file.basename }}
					</rcdevsOosItem>
				</rcdevsOosRow>

				<rcdevsOosRow v-if="axiosTransaction.success && this.file.signedOrSealed" class="greenTick">
					<greenTick>
						<img id="greenTick" :src="ui.pictures.greenTick" />
					</greenTick>
					<button type="button" @click="closeModal" class="closeModal">
						{{ ui.button.close }}
					</button>
				</rcdevsOosRow>

				<rcdevsOosError v-if="axiosTransaction.error">
					{{ axiosTransaction.message }}
				</rcdevsOosError>

				<rcdevsOosSuccess v-if="axiosTransaction.success">
					{{ axiosTransaction.message }}
				</rcdevsOosSuccess>

				<rcdevsOosRecipientsChoices v-if="!axiosTransaction.inProgress && actionSign && !this.file.signedOrSealed">
					<recipientsSignleChoice v-on:click="changeNcSelectvalue(constantes.self.label)">
						<NcCheckboxRadioSwitch v-if="!selfDisabled" :checked.sync="recipientType" :value="constantes.self.label" :disabled="selfDisabled" name="rcdevsOosRecipientRadio" type="radio">
							{{ constantes.self.value }}
						</NcCheckboxRadioSwitch>
						<DisplaySelfEmail>
							<span>{{ currentUserFullData }}</span>
						</DisplaySelfEmail>
					</recipientsSignleChoice>

					<recipientsSignleChoice v-on:click="changeNcSelectvalue(constantes.nextcloud)">
						<NcCheckboxRadioSwitch :checked.sync="recipientType" :value="constantes.nextcloud" name="rcdevsOosRecipientRadio" type="radio">
							{{ ui.messages.filenameMessage.nextcloudUser }}
						</NcCheckboxRadioSwitch>
						<SelectNextcloudUsers>
							<NcSelect id="usersFiltered" v-bind="usersListProps" v-model="usersListProps.value" @search="search" />

							<SearchResults v-if="user !== ''" :search-text="user" :search-results="userResults" :entries-loading="usersLoading" :no-results="noUserResults" :scrollable="true" :selectable="true" @click="addUser" />
						</SelectNextcloudUsers>
					</recipientsSignleChoice>
				</rcdevsOosRecipientsChoices>

				<rcdevsOosRow v-if="axiosTransaction.inProgress">
					<img :src="ui.pictures.loadingImg" />
				</rcdevsOosRow>
			</rcdevsOosModalMainContainer>

			<rcdevsOosSettingsKO v-if="settingKO" id="errorSettings" class="alert alertDanger">
				<button type="button" @click="closeModal" class="closeModal">
					{{ ui.button.close }}
				</button>
			</rcdevsOosSettingsKO>

			<rcdevsOosModalFooter v-if="(enabledSeal || enabledSign) && !file.signedOrSealed">
				<button v-if="enabledSeal" type="button" :disabled="axiosTransaction.inProgress" @click="axiosSeal" class="submitTransaction">
					{{ signTypes.seal.label }}
				</button>
				<button v-if="enabledSign && signTypes.standard.enabled" type="button" :disabled="axiosTransaction.inProgress" @click="runTransactionSignatureStandard" class="submitTransaction">
					{{ signTypes.standard.label }}
				</button>
				<button v-if="enabledSign && signTypes.advanced.enabled" type="button" :disabled="axiosTransaction.inProgress" @click="runTransactionSignatureAdvanced" class="submitTransaction">
					{{ signTypes.advanced.label }}
				</button>
				<button type="button" @click="closeModal" class="closeModal">
					{{ ui.button.close }}
				</button>
			</rcdevsOosModalFooter>
		</rcdevsOosModalContent>
	</NcModal>
</template>

<style>
@import '../styles/rcdevsNxC.css';
@import '../styles/rcdevsStyle.css';
@import '../styles/rcdevsUtility.css';
</style>

<script>
import {appName, sealAction, signAction} from '../javascript/config.js';
import {File, Permission} from '@nextcloud/files';
import {emit} from '@nextcloud/event-bus';
import {generateFilePath, generateRemoteUrl} from '@nextcloud/router';
import {getBasename, getOcsUrl, getT, isEmail, isEnabled, isValidResponse, log} from '../javascript/utility';
import {getCurrentUser} from '@nextcloud/auth';
import axios from '@nextcloud/axios';
import debounce from 'debounce';
import ListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon.js';
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js';
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js';
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js';
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js';
import SearchResults from '../components/SearchResults.vue';

export default {
	name: 'OpenOTPSignModal',
	components: {
		ListItemIcon,
		NcCheckboxRadioSwitch,
		NcModal,
		NcSelect,
		NcTextField,
		SearchResults,
	},

	data() {
		this.apis = [];
		this.apis.settingsCheck = '/settings/check';
		this.apis.settingsCheckOtp = '/settings/check/otp';
		this.apis.settingsCheckTypes = '/settings/check/types';
		this.apis.signAdvanced = '/sign/advanced';
		this.apis.signStandard = '/sign/standard';
		this.apis.signAdvancedLocalAsync = '/sign/advanced/local/async';
		this.apis.signStandardLocalAsync = '/sign/standard/local/async';
		this.apis.seal = '/seal';
		this.apis.userEmail = '/user/email';
		this.apis.userId = '/user/id';
		this.apis.usersAll = '/users/all';

		this.constantes = [];
		this.constantes.self = [];
		this.constantes.self.label = 'self';
		this.constantes.self.value = getT('Self-signature');
		this.constantes.nextcloud = 'nextcloud';

		this.ui = [];

		this.ui.axios = [
			{
				requestCancelled: getT('Transaction cancelled'),
			},
		];

		this.ui.button = [];
		this.ui.button.close = getT('Close');

		this.ui.messages = [];
		this.ui.messages.filenameMessage = [];
		this.ui.messages.filenameMessage.nextcloudUser = getT('Signature by a Nextcloud user');
		this.ui.messages.filenameMessage.searchUsers = getT('Search users');
		this.ui.messages.filenameMessage.seal = getT('Digital sealing of file');
		this.ui.messages.filenameMessage.sign = getT('Digital signature of file');
		this.ui.messages.placeHolderUser = getT('Write a user Id');
		this.ui.messages.warning = [];
		this.ui.messages.warning.disabled = [];
		this.ui.messages.warning.disabled.seal = getT('Sealing mode is disabled; you have to enable it in the OpenOTP Sign settings prior to seal any document');
		this.ui.messages.warning.disabled.sign = getT('All signature modes are disabled; you have to enable minimum one mode in the OpenOTP Sign settings prior to sign any document');
		this.ui.messages.warning.server = getT('You have to enter the OpenOTP server URL in the OpenOTP Sign settings prior to sign any document');

		this.ui.pictures = [];
		this.ui.pictures.mobileSigningImg = generateFilePath(appName, '', 'img/') + 'mobile-signing.png';
		this.ui.pictures.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif';
		this.ui.pictures.rcdevsLogo = generateFilePath(appName, '', 'img/') + 'rcdevsLogo.svg';
		this.ui.pictures.greenTick = generateFilePath(appName, '', 'img/') + 'greenTick.svg';

		this.ui.title = [
			{
				chosen: '',
				seal: 'OpenOTP Seal',
				sign: 'OpenOTP Sign',
			},
		];

		this.currentUser = {};
		this.currentUserFullData = '';
		this.selfDisabled = false;

		return {
			fileList: [],
			ncModalAriaLabel: '',

			noUserResults: false,
			usersLoading: false,
			userResults: [],
			user: '',

			actionSeal: false,
			actionSign: false,
			disabledSeal: true,
			disabledSign: true,
			enabledSeal: false,
			enabledSign: false,
			file: [
				{
					message: '',
					signedOrSealed: false,
					basename: getBasename(this.chosenFile),
				},
			],
			filenameMessage: '',

			enabledOtp: {
				checked: false,
				seal: false,
				sign: false,
			},
			rcdevsSettings: {
				checked: false,
				validated: false,
			},
			signTypes: [],

			settingKO: true,

			axiosChecking: {
				abortCtrl: null,
				inProgress: false,
				success: false,
				error: false,
				message: null,
			},

			axiosTransaction: {
				abortCtrl: null,
				inProgress: false,
				success: false,
				error: false,
				message: null,
			},

			axiosUser: {
				abortCtrl: null,
				inProgress: false,
				success: false,
				error: false,
				message: null,
			},

			chosenFile: null,
			action: null,
			modal: false,
			checkingSettings: true,
			errorMessage: '',
			recipientType: '',
			selfEmail: '',
			localUser: [],
			formattedOptions: [],
			designerUrl: '',

			signatureTypeSelected: 'simple', // Setting initial value
			usersListProps: {
				inputLabel: 'User select',
				userSelect: true,
				options: [],
			},
		};
	},

	beforeCreate() {
		try {
			log.info(`[beforeCreate] Running...`);
			this.noUserResults = false;
			this.usersLoading = false;
			this.userResults = {};

			this.$root.$watch('chosenFile', async (newValue) => {
				this.chosenFile = newValue;
				this.commonWatch(newValue);
			});

			this.$root.$watch('action', async (newValue) => {
				this.action = newValue;
				this.commonWatch(newValue);
			});
		} catch (exception) {
			log.error(`[beforeCreate] ${exception}`);
		}
	},

	mounted() {},

	methods: {
		commonWatch: function (newValue) {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				if (newValue) {
					this.modal = true;

					this.resetInputs();

					// Verify App settings
					this.axiosSettingsCheck();

					// CHeck Signature Types and enabled Modes
					this.axiosSettingsCheckTypes();
					this.axiosSettingsCheckOtp();

					// Get current user Id
					this.axiosUserId();
					this.axiosUserEmail();
				} else {
					this.modal = false;
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		commonSealSyncSign: function (action, response) {
			log.debug(`Refresh ${action} file : [${response.data.data.fileId}]`);

			const davUrl = generateRemoteUrl('dav');
			const currentUserId = getCurrentUser()?.uid;

			log.debug(`DAV : [${davUrl}/files/${currentUserId}/${response.data.data.name}]`);

			const file = new File({
				source: `${davUrl}/files/${currentUserId}/${response.data.data.name}`,
				id: response.data.data.fileId,
				size: response.data.data.size,
				mtime: new Date(),
				mime: 'application/pdf',
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.ALL,
				root: `/files/${currentUserId}`,
			});

			if (response.data.data.overwrite) {
				emit('files:node:updated', file);
			} else {
				emit('files:node:created', file);
			}

			this.axiosTransaction.error = !(this.file.signedOrSealed = this.axiosTransaction.success = true);
		},

		resetInputs: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.ui.title.chosen = this.getModalTitle();

				this.axiosChecking = {
					abortCtrl: null,
					inProgress: false,
					success: false,
					error: false,
					message: null,
				};

				this.axiosTransaction = {
					abortCtrl: null,
					inProgress: false,
					success: false,
					error: false,
					message: null,
				};
				log.info(`Initialize axiosTransaction : [${JSON.stringify(this.axiosTransaction)}]`);

				this.axiosUser = {
					abortCtrl: null,
					inProgress: false,
					success: false,
					error: false,
					message: null,
				};

				this.userResults = {};
				this.noUserResults = false;
				this.usersLoading = false;
				this.recipientType = this.constantes.self.label;
				this.localUser = [];
				this.selfDisabled = false;

				this.enabledOtp = {
					checked: false,
					seal: false,
					sign: false,
				};

				this.file = {
					message: '',
					signedOrSealed: false,
					basename: getBasename(this.chosenFile),
				};

				this.rcdevsSettings = {
					checked: false,
					validated: false,
				};

				this.signTypes = {
					checked: false,
					seal: {
						label: getT('Seal'),
					},
					advanced: {
						enabled: false,
						label: '',
					},
					standard: {
						enabled: false,
						label: '',
					},
				};

				this.refreshUiVariables();
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		addUser(item) {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.user = this.localUser.applicantId = item.value.shareWith;
				this.localUser.email = item.shareWithDisplayNameUnique;
				this.userResults = {};
				this.noUserResults = false;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		cancelSearchLabel() {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return getT('Cancel search');
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isActionSeal() {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return this.action === sealAction ? true : false;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isActionSign() {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return this.action === signAction ? true : false;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isSearchingUser() {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return this.user !== '';
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		getFunctionName: function () {
			const error = new Error();
			const stackLines = error.stack.split('\n');
			// The stack trace format can vary; you may need to adjust the index
			const callerLine = stackLines[2].trim();
			const functionName = callerLine.split(' ')[1];
			return functionName;
		},

		search(query, loading) {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				log.debug(`[${this.getFunctionName()}] query : [${query}]`);

				if (query.length >= 3) {
					this.axiosUsersList(query);
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
				showError(getT('An error occurred while performing the search'));
			}
		},

		handleUserInput() {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction.error = false;
				this.noUserResults = false;
				this.usersLoading = true;
				this.userResults = {};
				this.debounceSearchUsers();
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		abortUserSearch() {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		debounceSearchUsers: debounce(function (query, loading) {
			this.searchUsers(query, loading);
		}, 250),

		async searchUsers(query, loading) {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				log.debug(`[${this.getFunctionName()}] query : [${query}]`);

				if (query.length >= 3) {
					const search = async (search, type) => {
						return await axios.post(getOcsUrl(this.apis.usersAll), {
							search: query,
							type: 'user',
						});
					};

					const exact = search.exact?.users || [];
					const users = search.users || [];
					log.debug(`[${this.getFunctionName()}] search:[${search}] / exact:[${exact}] / users:[${users}]`);

					this.usersListProps.options = [];

					exact.forEach((singleUser) => {
						this.usersListProps.options.push({
							id: singleUser.value.shareWith,
							displayName: singleUser.label,
							subname: singleUser.shareWithDisplayNameUnique,
						});
					});

					users.forEach((singleUser) => {
						this.usersListProps.options.push({
							id: singleUser.value.shareWith,
							displayName: singleUser.label,
							subname: singleUser.shareWithDisplayNameUnique,
						});
					});
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
				showError(getT('An error occurred while performing the search'));
			}
		},

		changeNcSelectvalue: function (radiovalue) {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				if (!(this.selfDisabled && radiovalue === this.constantes.self.label)) {
					this.recipientType = radiovalue;
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		getCurrentUser: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.currentUserFullData = '';

				switch (true) {
					case isEmail(this.currentUser.id):
						this.currentUserFullData = this.currentUser.id;
						break;
					default:
						this.currentUserFullData = this.currentUser.id;
						break;
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
				this.currentUserFullData = '';
			}
		},

		getNcModalAriaLabel: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				if (this.isActionSeal()) {
					getT('Seal with OpenOTP');
				}
				if (this.isActionSign()) {
					getT('Sign with OpenOTP');
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isDisabledSeal: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				// Using rcdevsSettings.checked because if not checked, impossible to say if Settings are OK or KO
				return this.rcdevsSettings.checked && this.enabledOtp.checked && (!this.enabledOtp.seal || this.settingKO);
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isEnabledSeal: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				// Using rcdevsSettings.checked because if not checked, impossible to say if Settings are OK or KO
				return this.rcdevsSettings.checked && this.enabledOtp.checked && this.enabledOtp.seal && this.settingOK;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isDisabledSign: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				// Using rcdevsSettings.checked because if not checked, impossible to say if Settings are OK or KO
				return this.rcdevsSettings.checked && this.enabledOtp.checked && this.signTypes.checked && (!this.enabledOtp.sign || this.settingKO || (!this.signTypes.advanced.enabled && !this.signTypes.standard.enabled));
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isEnabledSign: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				// Using rcdevsSettings.checked because if not checked, impossible to say if Settings are OK or KO
				return this.rcdevsSettings.checked && this.enabledOtp.checked && this.signTypes.checked && this.enabledOtp.sign && this.settingOK && (this.signTypes.advanced.enabled || this.signTypes.standard.enabled);
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isSettingKO: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				// Using rcdevsSettings.checked because if not checked, impossible to say if Settings are OK or KO
				return this.rcdevsSettings.checked && !this.rcdevsSettings.validated;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isSettingOK: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				// Using rcdevsSettings.checked because if not checked, impossible to say if Settings are OK or KO
				return this.rcdevsSettings.checked && this.rcdevsSettings.validated;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isTransactionInProgress: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return this.axiosTransaction.inProgress;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		isSuccessTransaction: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return !this.axiosTransaction.inProgress && this.axiosTransaction.success;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		getModalTitle: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return this.isActionSign() ? this.ui.title.sign : this.ui.title.seal;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		getFilenameMessage: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				return this.isActionSeal() ? this.ui.messages.filenameMessage.seal : this.ui.messages.filenameMessage.sign;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		runTransactionSignatureAdvanced: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				if (this.recipientType === 'self') {
					this.axiosSyncAdvancedSignature();
				} else if (this.recipientType === 'nextcloud') {
					log.debug(JSON.stringify(this.localUser));

					if (this.recipientType === 'nextcloud' && !this.localUser) {
						return;
					}

					this.axiosAsyncLocalAdvancedSignature();
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		runTransactionSignatureStandard: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				if (!this.chosenFile.path.toLowerCase().endsWith('.pdf')) {
					alert(getT('Standard signature is possible only with PDF files'));
					return;
				}

				if (this.recipientType === 'self') {
					this.axiosSyncStandardSignature();
				} else if (this.recipientType === 'nextcloud') {
					log.debug(JSON.stringify(this.localUser));

					if (this.recipientType === 'nextcloud' && !this.localUser) {
						return;
					}

					this.axiosAsyncLocalStandardSignature();
				}
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosAsyncLocalAdvancedSignature: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction = this.initAxios();

				log.info(`chosenFile : ${JSON.stringify(this.chosenFile)}`);
				log.info(`fileId : ${this.chosenFile._attributes.fileid}`);

				axios
					.post(
						getOcsUrl(this.apis.signAdvancedLocalAsync),
						{
							path: this.chosenFile.path,
							fileId: this.chosenFile._attributes.fileid,
							recipientId: this.usersListProps.value.id,
							recipientName: this.usersListProps.value.displayName,
							recipientEmail: this.usersListProps.value.subname,
						},
						{
							signal: this.axiosTransaction.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Asynchronized signature response : [${JSON.stringify(response.data)}]`);

						if (parseInt(response.data.code) !== 1) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}
						this.axiosTransaction.error = !(this.file.signedOrSealed = this.axiosTransaction.success = true);
						this.axiosTransaction.message = response.data.message;
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosTransaction.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosTransaction.message = exception.message;
						}

						this.axiosTransaction.error = !(this.file.signedOrSealed = false);
					})
					.finally(() => {
						this.axiosTransaction.inProgress = false;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosAsyncLocalStandardSignature: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction = this.initAxios();

				log.info(`chosenFile : ${JSON.stringify(this.chosenFile)}`);
				log.info(`fileId : ${this.chosenFile._attributes.fileid}`);

				axios
					.post(
						getOcsUrl(this.apis.signStandardLocalAsync),
						{
							path: this.chosenFile.path,
							fileId: this.chosenFile._attributes.fileid,
							recipientId: this.usersListProps.value.id,
							recipientName: this.usersListProps.value.displayName,
							recipientEmail: this.usersListProps.value.subname,
						},
						{
							signal: this.axiosTransaction.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Asynchronized signature response : [${JSON.stringify(response.data)}]`);

						if (parseInt(response.data.code) !== 1) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}
						this.axiosTransaction.error = !(this.file.signedOrSealed = this.axiosTransaction.success = true);
						this.axiosTransaction.message = response.data.message;
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosTransaction.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosTransaction.message = exception.message;
						}

						this.axiosTransaction.error = !(this.file.signedOrSealed = false);
					})
					.finally(() => {
						this.axiosTransaction.inProgress = false;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosSeal: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction = this.initAxios();

				log.info(`chosenFile : ${JSON.stringify(this.chosenFile)}`);
				log.info(`fileId : ${this.chosenFile._attributes.fileid}`);

				axios
					.post(
						getOcsUrl(this.apis.seal),
						{
							path: this.chosenFile.path,
							fileId: this.chosenFile._attributes.fileid,
						},
						{
							signal: this.axiosTransaction.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Synchronized sealing response : [${JSON.stringify(response.data)}]`);

						if (!isValidResponse(response)) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}

						this.commonSealSyncSign('sealed', response);
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosTransaction.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosTransaction.message = exception.message;
						}

						this.axiosTransaction.error = !(this.file.signedOrSealed = false);
					})
					.finally(() => {
						this.axiosTransaction.inProgress = false;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosSettingsCheck: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosChecking = this.initAxios();

				log.info(`Contact server to check settings (Global)`);

				axios
					.get(getOcsUrl(this.apis.settingsCheck), {
						signal: this.axiosChecking.abortCtrl.signal,
					})
					.then((response) => {
						log.debug(`Response for ${JSON.stringify(response.data)}`);

						if (!isValidResponse(response)) {
							throw new Error(`No property named "code" in Axios response`);
						}

						log.debug(`isEnabled(response.data.code):[${isEnabled(response.data.code)}]`);

						this.axiosChecking.success = true;
						this.rcdevsSettings.validated = isEnabled(response.data.code);
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosChecking.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosChecking.message = exception.message;
						}

						this.axiosChecking.error = !(this.rcdevsSettings.validated = false);
					})
					.finally(() => {
						this.axiosChecking.inProgress = false;
						this.rcdevsSettings.checked = true;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosSettingsCheckOtp: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosChecking = this.initAxios();

				log.info(`Contact server to check settings (OTP)`);

				axios
					.get(getOcsUrl(this.apis.settingsCheckOtp), {
						signal: this.axiosChecking.abortCtrl.signal,
					})
					.then((response) => {
						log.debug(`Response for ${JSON.stringify(response.data)}`);

						// Check results Seal
						if (!response.data.hasOwnProperty('enableOtpSeal')) {
							throw new Error('OTP Seal is missing');
						}
						this.enabledOtp.seal = isEnabled(response.data.enableOtpSeal) && this.isActionSeal();

						// Check results Sign
						if (!response.data.hasOwnProperty('enableOtpSign')) {
							throw new Error('OTP Sign is missing');
						}
						this.axiosChecking.success = true;
						this.enabledOtp.sign = isEnabled(response.data.enableOtpSign) && this.isActionSign();
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosChecking.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosChecking.message = exception.message;
						}

						this.axiosChecking.error = !(this.enabledOtp.seal = this.enabledOtp.sign = false);
					})
					.finally(() => {
						this.axiosChecking.inProgress = false;
						this.enabledOtp.checked = true;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosSettingsCheckTypes: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosChecking = this.initAxios();

				log.info(`Contact server to check settings (Signature types)`);

				axios
					.get(getOcsUrl(this.apis.settingsCheckTypes), {
						signal: this.axiosChecking.abortCtrl.signal,
					})
					.then((response) => {
						log.debug(`Response for ${JSON.stringify(response.data)}`);

						// Check results Advanced
						if (!response.data.hasOwnProperty('signTypeAdvanced')) {
							throw new Error('Sign type advanced is missing');
						}

						// Check results Standard
						if (!response.data.hasOwnProperty('signTypeStandard')) {
							throw new Error('Sign type standard is missing');
						}

						this.axiosChecking.success = true;

						// Use temporary vars (issue on response which cannot be changed !?!)
						let responseDataSigntypeadvanced = response.data.signTypeAdvanced;
						let responseDataSigntypestandard = response.data.signTypeStandard;

						// According to file to sign extension, disable the standard signature (if not a PDF)
						if (!this.chosenFile.path.toLowerCase().endsWith('.pdf')) {
							responseDataSigntypestandard = '0';
						}

						log.info(`The signTypes are standard : [${responseDataSigntypestandard}] and dvanced : [${responseDataSigntypeadvanced}]`);

						switch (true) {
							case isEnabled(responseDataSigntypeadvanced) && isEnabled(responseDataSigntypestandard):
								// Both types are enabled ==> two buttons activated
								this.signTypes.advanced.enabled = true;
								this.signTypes.standard.enabled = true;
								this.signTypes.advanced.label = getT('Advanced signature');
								this.signTypes.standard.label = getT('Standard signature');
								break;

							case !isEnabled(responseDataSigntypeadvanced) && !isEnabled(responseDataSigntypestandard):
								// Both types are disabled ==> two buttons activated
								throw new Error('Minimum one signature type is needed to sign the document');
								break;

							case isEnabled(responseDataSigntypeadvanced) && !isEnabled(responseDataSigntypestandard):
								// only advanced type is enabled
								this.signTypes.advanced.enabled = true;
								this.signTypes.standard.enabled = false;
								this.signTypes.advanced.label = getT('Signature');
								break;

							case !isEnabled(responseDataSigntypeadvanced) && isEnabled(responseDataSigntypestandard):
								// only standard type is enabled
								this.signTypes.advanced.enabled = false;
								this.signTypes.standard.enabled = true;
								this.signTypes.standard.label = getT('Signature');
								break;

							default:
								throw new Error(`This case is not implemented : this.signTypes = [${JSON.stringify(this.signTypes)}]`);
								break;
						}
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosChecking.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosChecking.message = exception.message;
						}

						this.axiosChecking.error = !(this.signTypes.advanced.enabled = this.signTypes.standard.enabled = false);
					})
					.finally(() => {
						this.axiosChecking.inProgress = false;
						this.signTypes.checked = true;
						this.refreshUiVariables();
						log.debug(`this.signTypes.advanced.enabled:[${this.signTypes.advanced.enabled}] / this.signTypes.standard.enabled:[${this.signTypes.standard.enabled}]`);
						log.debug(`this.enabledSeal:[${this.enabledSeal}] / this.enabledSign:[${this.enabledSign}]`);
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosSyncAdvancedSignature: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction = this.initAxios();

				log.info(`chosenFile : ${JSON.stringify(this.chosenFile)}`);
				log.info(`fileId : ${this.chosenFile._attributes.fileid}`);

				axios
					.post(
						getOcsUrl(this.apis.signAdvanced),
						{
							path: this.chosenFile.path,
							fileId: this.chosenFile._attributes.fileid,
						},
						{
							signal: this.axiosTransaction.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Synchronized advanced signature response : [${JSON.stringify(response.data)}]`);

						if (!isValidResponse(response)) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}

						this.commonSealSyncSign('signed', response);
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosTransaction.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosTransaction.message = exception.message;
						}

						this.axiosTransaction.error = !(this.file.signedOrSealed = this.axiosTransaction.success = false);
					})
					.finally(() => {
						this.axiosTransaction.inProgress = false;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosSyncStandardSignature: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction = this.initAxios();

				log.info(`chosenFile : ${JSON.stringify(this.chosenFile)}`);
				log.info(`fileId : ${this.chosenFile._attributes.fileid}`);

				axios
					.post(
						getOcsUrl(this.apis.signStandard),
						{
							path: this.chosenFile.path,
							fileId: this.chosenFile._attributes.fileid,
						},
						{
							signal: this.axiosTransaction.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Synchronized signature response : [${JSON.stringify(response.data)}]`);

						if (!isValidResponse(response)) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}

						this.commonSealSyncSign('signed', response);
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosTransaction.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosTransaction.message = exception.message;
						}

						this.axiosTransaction.error = !(this.file.signedOrSealed = this.axiosTransaction.success = false);
					})
					.finally(() => {
						this.axiosTransaction.inProgress = false;
						this.refreshUiVariables();
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosUserEmail: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosUser = this.initAxios();

				log.info(`Contact server to retrieve User's email`);

				axios
					.get(getOcsUrl(this.apis.userEmail), {
						signal: this.axiosUser.abortCtrl.signal,
					})
					.then((response) => {
						log.debug(`Response for ${JSON.stringify(response.data)}`);

						this.currentUser.email = response.data;
						this.getCurrentUser();

						this.axiosUser.success = true;
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosUser.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosUser.message = exception.message;
						}

						this.axiosUser.error = true;
						this.currentUser.email = null;
					})
					.finally(() => {
						this.axiosUser.inProgress = false;
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosUserId: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosUser = this.initAxios();

				log.info(`Contact server to retrieve User's id`);

				axios
					.get(getOcsUrl(this.apis.userId), {
						signal: this.axiosUser.abortCtrl.signal,
					})
					.then((response) => {
						log.debug(`Response for ${JSON.stringify(response.data)}`);

						this.currentUser.id = response.data;
						this.getCurrentUser();

						this.axiosUser.success = true;
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosUser.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosUser.message = exception.message;
						}

						this.axiosUser.error = true;
						this.currentUser.id = null;
					})
					.finally(() => {
						this.axiosUser.inProgress = false;
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosUsersList: function (query) {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				this.axiosUser = this.initAxios();

				log.info(`Contact server to retrieve Users list`);

				return axios
					.post(
						getOcsUrl(this.apis.usersAll),
						{
							search: query,
							type: 'user',
						},
						{
							signal: this.axiosUser.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Response for ${JSON.stringify(response.data)}`);
						this.axiosUser.success = true;

						const respData = response.data;
						const exact = respData.exact?.users || [];
						const users = respData.users || [];
						log.debug(`[${this.getFunctionName()}] respData:[${respData}] / exact:[${exact}] / users:[${users}]`);

						this.usersListProps.options = [];

						exact.forEach((singleUser) => {
							log.debug(`[${this.getFunctionName()}] singleUser:[${JSON.stringify(singleUser)}]`);
							this.usersListProps.options.push({
								id: singleUser.value.shareWith,
								displayName: singleUser.label,
								subname: singleUser.shareWithDisplayNameUnique,
							});
						});

						users.forEach((singleUser) => {
							log.debug(`[${this.getFunctionName()}] singleUser:[${JSON.stringify(singleUser)}]`);
							this.usersListProps.options.push({
								id: singleUser.value.shareWith,
								displayName: singleUser.label,
								subname: singleUser.shareWithDisplayNameUnique,
							});
						});

						log.debug(`[${this.getFunctionName()}] this.usersListProps.options:[${JSON.stringify(this.usersListProps.options)}]`);
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosUser.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosUser.message = exception.message;
						}

						this.axiosUser.error = true;
						this.currentUser.id = null;
					})
					.finally(() => {
						this.axiosUser.inProgress = false;
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		findUsersFiltered: function () {
			log.info(this.usersListProps.value);
		},

		closeModal: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				if (this.axiosTransaction.abortCtrl && this.axiosTransaction.abortCtrl.signal) {
					this.axiosTransaction.abortCtrl.abort('Operation canceled by the user');
					log.info('Operation canceled by the user');
				}

				this.resetInputs();
				this.$root.$emit('dialog:closed');
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		initAxios: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				let currentAxios = {};
				currentAxios.abortCtrl = new AbortController();
				currentAxios.inProgress = true;
				currentAxios.error = false;
				currentAxios.message = null;

				return currentAxios;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		refreshUiVariables: function () {
			try {
				log.debug(`[${this.getFunctionName()}] Running...`);
				log.debug('Refresh UI Vars');
				this.actionSeal = this.isActionSeal();
				this.actionSign = this.isActionSign();
				this.disabledSeal = this.isDisabledSeal();
				this.disabledSign = this.isDisabledSign();
				this.enabledSeal = this.isEnabledSeal();
				this.enabledSign = this.isEnabledSign();
				this.file.message = this.getFilenameMessage();
				this.ncModalAriaLabel = this.getNcModalAriaLabel();
				this.settingKO = this.isSettingKO();
				this.settingOK = this.isSettingOK();
				this.axiosTransaction.success = this.isSuccessTransaction();
				this.axiosTransaction.inProgress = this.isTransactionInProgress();
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},
	}, // END methods
};
</script>
