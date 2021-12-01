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
		<button @click="showModal">
			Show Modal
		</button>
		<Modal v-if="modal" size="large" @close="closeModal">
			<div class="modal__content">
				<h1>{{ $t('openotpsign', 'OpenOTP Sign') }}</h1>
				<p v-if="!settingsOk"
					id="error_settings"
					class="alert alert-danger"
					v-html="$t('openotpsign', 'You have to enter the <strong>OpenOTP server URL</strong> in the <strong>OpenOTP Sign</strong> settings prior to sign any document.')" />
				<div v-if="settingsOk">
					<img v-if="!success" :src="mobileSigningImg" style="max-height: 200px;">
					<p v-else id="green-tick">
						&#10003;
					</p>
					<p v-html="$t('openotpsign', 'Digital signature of file <strong>{filename}</strong>', {filename: filename})" />
					<p v-if="error" class="error">
						{{ errorMessage }}
					</p>
					<br>
					<div v-if="!requesting && !success">
						<CheckboxRadioSwitch
							:checked.sync="recipientType"
							value="self"
							name="recipient_radio"
							type="radio">
							{{ $t('openotpsign', 'Self-signature') }}
						</CheckboxRadioSwitch>
						<div class="flex-container">
							<CheckboxRadioSwitch
								:checked.sync="recipientType"
								value="nextcloud"
								name="recipient_radio"
								type="radio">
								{{ $t('openotpsign', 'Signature by a Nextcloud user:') }}
							</CheckboxRadioSwitch>
							<Multiselect
								v-model="localUser"
								:options="formattedOptions"
								label="displayName"
								track-by="uid"
								:user-select="true"
								style="width: 400px"
								@select="checkNextcloudRadio"
								@search-change="localUserSearchChanged">
								<template #singleLabel="{ option }">
									<ListItemIcon
										v-bind="option"
										:title="option.displayName"
										:avatar-size="24"
										:no-margin="true"
										style="width: 380px;" />
								</template>
							</Multiselect>
						</div>
						<div class="flex-container">
							<CheckboxRadioSwitch
								:checked.sync="recipientType"
								value="external"
								name="recipient_radio"
								type="radio">
								{{ $t('openotpsign', 'Signature by a YumiSign user:') }}
							</CheckboxRadioSwitch>
							<input
								v-model="externalUserEmail"
								type="text"
								placeholder="email_address@domain.tld"
								@input="checkExternalRadio">
						</div>
						<button type="button" @click="advancedSignature">
							{{ $t('openotpsign', 'Advanced signature') }}
						</button>
						<button type="button" @click="qualifiedSignature">
							{{ $t('openotpsign', 'Qualified signature') }}
						</button>
					</div>
					<div v-if="requesting">
						<img :src="loadingImg">
					</div>
					<div v-if="success">
						<button type="button" class="primary" @click="closeModal">
							{{ $t('openotpsign', 'Close') }}
						</button>
					</div>
				</div>
			</div>
		</Modal>
	</div>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import ListItemIcon from '@nextcloud/vue/dist/Components/ListItemIcon'
import EventBus from './EventBus'
import queryString from 'query-string'
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'

export default {
	name: 'OpenOTPSignModal',
	components: {
		Modal,
		CheckboxRadioSwitch,
		Multiselect,
		ListItemIcon,
	},
	data() {
		return {
			modal: false,
			requesting: false,
			success: false,
			error: false,
			errorMessage: '',
			source: null,
			settingsOk: false,
			recipientType: 'self',
			localUser: '',
			externalUserEmail: '',
			formattedOptions: [],
		}
	},
	mounted() {
		this.mobileSigningImg = generateFilePath('openotpsign', '', 'img/') + 'mobile-signing.png'
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'

		EventBus.$on('ootp-sign-click', payload => {
			this.showModal()
			this.filename = payload.filename
		})
	},
	methods: {
		showModal() {
			this.modal = true
			this.requesting = false
			this.success = false
			this.error = false
			this.errorMessage = ''

			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.get(baseUrl + '/check_settings', {
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.settingsOk = response.data
				})
				.catch(error => {
					// eslint-disable-next-line
					console.log(error)
				})
		},
		checkNextcloudRadio() {
			this.recipientType = 'nextcloud'
		},
		localUserSearchChanged(searchQuery, id) {
			if (searchQuery.length >= 3) {
				const baseUrl = generateUrl('/apps/openotpsign')
				const CancelToken = axios.CancelToken
				this.source = CancelToken.source()
				axios.get(baseUrl + '/get_local_users?searchQuery=' + searchQuery, {
				}, {
					cancelToken: this.source.token,
				})
					.then(response => {
						this.formattedOptions = response.data.map(item => {
							return {
								uid: item.uid,
								displayName: item.display_name,
								subtitle: item.email,
								icon: 'icon-user',
								isNoUser: false,
							}
						})
					})
					.catch(error => {
						// eslint-disable-next-line
						console.log(error)
					})
			} else {
				this.formattedOptions = []
			}
		},
		checkExternalRadio() {
			if (this.externalUserEmail.length > 0) {
				this.recipientType = 'external'
			}
		},
		closeModal() {
			if (this.source !== null) {
				this.source.cancel('Operation canceled by the user.')
				this.source = null
			}

			if (this.success) {
				FileList.reload()
			}

			this.modal = false
		},
		advancedSignature() {
			if (this.recipientType === 'self') {
				this.syncAdvancedSignature()
			} else if (this.recipientType === 'nextcloud') {
				this.asyncLocalAdvancedSignature()
			} else {
				this.asyncExternalAdvancedSignature()
			}
		},
		syncAdvancedSignature() {
			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/advanced_sign', {
				path: this.getFilePath(),
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === 1) {
						this.success = true
					} else {
						this.error = true
						this.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(error => {
					this.requesting = false
					this.error = true
					this.errorMessage = error
				})
		},
		asyncLocalAdvancedSignature() {
			if (this.recipientType === 'nextcloud' && !this.localUser) {
				return
			}

			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/async_local_advanced_sign', {
				path: this.getFilePath(),
				username: this.localUser.uid,
				email: this.localUser.subtitle,
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === 2) {
						this.success = true
					} else {
						this.error = true
						this.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(error => {
					this.requesting = false
					this.error = true
					this.errorMessage = error
				})
		},
		asyncExternalAdvancedSignature() {
			if (this.recipientType === 'external' && !this.externalUserEmail) {
				return
			}

			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/async_external_advanced_sign', {
				path: this.getFilePath(),
				email: this.externalUserEmail,
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === 2) {
						this.success = true
					} else {
						this.error = true
						this.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(error => {
					this.requesting = false
					this.error = true
					this.errorMessage = error
				})
		},
		qualifiedSignature() {
			if (this.recipientType === 'self') {
				this.syncQualifiedSignature()
			} else if (this.recipientType === 'nextcloud') {
				this.asyncLocalQualifiedSignature()
			} else {
				this.asyncExternalQualifiedSignature()
			}
		},
		syncQualifiedSignature() {
			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/qualified_sign', {
				path: this.getFilePath(),
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === 1) {
						this.success = true
					} else {
						this.error = true
						this.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(error => {
					this.requesting = false
					this.error = true
					this.errorMessage = error
				})
		},
		asyncLocalQualifiedSignature() {
			if (this.recipientType === 'nextcloud' && !this.localUser) {
				return
			}

			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/async_local_qualified_sign', {
				path: this.getFilePath(),
				username: this.localUser.uid,
				email: this.localUser.subtitle,
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === 2) {
						this.success = true
					} else {
						this.error = true
						this.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(error => {
					this.requesting = false
					this.error = true
					this.errorMessage = error
				})
		},
		asyncExternalQualifiedSignature() {
			if (this.recipientType === 'external' && !this.externalUserEmail) {
				return
			}

			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/async_external_qualified_sign', {
				path: this.getFilePath(),
				email: this.externalUserEmail,
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === 2) {
						this.success = true
					} else {
						this.error = true
						this.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(error => {
					this.requesting = false
					this.error = true
					this.errorMessage = error
				})
		},
		getFilePath() {
			const parsed = queryString.parse(window.location.search)
			if (parsed.dir === '/') {
				return this.filename
			} else {
				return parsed.dir + '/' + this.filename
			}
		},
	},
}
</script>
<style scoped>
	.modal__content {
		margin: 50px;
		text-align: center;
	}

	h1 {
		font-size: 2em;
		font-weight: bold;
	}

	#green-tick {
		font-size: 150px;
		color: green;
		margin-top: 100px;
		margin-bottom: 100px;
	}

	.error {
		color: red;
	}

	.alert-danger {
		color: #721c24;
		background-color: #f8d7da;
		border-color: #f5c6cb;
	}

	.alert {
		display: block;
		position: relative;
		padding: .75rem 1.25rem;
		margin-bottom: 1rem;
		border: 1px solid transparent;
		border-radius: .25rem;
	}

	#error_settings {
		margin-top: 25px;
	}

	.flex-container {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.flex-container:last-of-type {
		margin-bottom: 32px;
	}

	input {
		flex: 1 1 auto;
	}
</style>
