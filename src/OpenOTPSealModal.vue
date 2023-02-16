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
		<Modal v-if="modal" size="large" @close="closeModal">
			<div class="modal__content">
				<h1>{{ $t('openotp_sign', 'OpenOTP Sign') }}</h1>
				<img v-if="checkingSettings" :src="loadingImg">
				<p v-else-if="!settingsOk"
					id="error_settings"
					class="alert alert-danger"
					v-html="$t('openotp_sign', 'You have to enter the <strong>OpenOTP server URL</strong> in the <strong>OpenOTP Sign</strong> settings prior to seal any document.')" />
				<div v-else>
					<img v-if="!success" :src="mobileSigningImg" style="max-width: 200px;">
					<p v-else id="green-tick">
						&#10003;
					</p>
					<p v-html="$t('openotp_sign', 'Digital seal of file <strong>{filename}</strong>', {filename: filename})" />
					<p v-if="error" class="error">
						{{ errorMessage }}
					</p>
					<br>
					<div v-if="!requesting && !success">
						<button type="button" @click="seal">
							{{ $t('openotp_sign', 'Seal') }}
						</button>
					</div>
					<div v-if="requesting">
						<img :src="loadingImg">
					</div>
					<div v-if="success">
						<button type="button" class="primary" @click="closeModal">
							{{ $t('openotp_sign', 'Close') }}
						</button>
					</div>
				</div>
			</div>
		</Modal>
	</div>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import EventBus from './EventBus'
import queryString from 'query-string'
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'

export default {
	name: 'OpenOTPSealModal',
	components: {
		Modal,
	},
	data() {
		return {
			modal: false,
			checkingSettings: true,
			requesting: false,
			success: false,
			error: false,
			errorMessage: '',
			source: null,
			settingsOk: false,
		}
	},
	mounted() {
		this.mobileSigningImg = generateFilePath('openotp_sign', '', 'img/') + 'mobile-signing.png'
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'

		EventBus.$on('ootp-seal-click', payload => {
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

			const baseUrl = generateUrl('/apps/openotp_sign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.get(baseUrl + '/check_settings', {
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.checkingSettings = false
					this.settingsOk = response.data
				})
				.catch(error => {
					// eslint-disable-next-line
					console.log(error)
				})
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
		seal() {
			this.error = false
			this.requesting = true
			const baseUrl = generateUrl('/apps/openotp_sign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/seal', {
				path: this.getFilePath(),
			}, {
				cancelToken: this.source.token,
			})
				.then(response => {
					this.requesting = false
					if (response.data.code === '1') {
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
		margin-bottom: 10px;
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
</style>
