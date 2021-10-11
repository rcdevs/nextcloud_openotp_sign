<template>
	<div>
		<button @click="showModal">
			Show Modal
		</button>
		<Modal v-if="modal" @close="closeModal">
			<div class="modal__content">
				<h1>OpenOTP Sign</h1>
				<img v-if="!success" src="/nextcloud/apps/notestutorial/img/mobile-signing.png" style="max-width: 500px;">
				<p v-else id="green-tick">
					&#10003;
				</p>
				<p>
					Digital signature of file <strong>{{ filename }}</strong>
				</p>
				<p v-if="error" class="error">
					{{ errorMessage }}
				</p>
				<br>
				<div v-if="!requesting && !success">
					<button type="button" @click="advancedSignature">
						Advanced signature
					</button>
					<button type="button" @click="qualifiedSignature">
						Qualified signature
					</button>
				</div>
				<div v-if="requesting">
					<img src="/nextcloud/core/img/loading.gif">
				</div>
				<div v-if="success">
					<button type="button" class="primary" @click="closeModal">
						Close
					</button>
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

export default {
	name: 'OpenOTPSignModal',
	components: {
		Modal,
	},
	data() {
		return {
			modal: false,
			requesting: false,
			success: false,
			error: false,
			errorMessage: '',
			source: null,
		}
	},
	mounted() {
		const _self = this
		EventBus.$on('ootp-sign-click', function(payload) {
			_self.showModal()
			_self.filename = payload.filename
		})
	},
	methods: {
		showModal() {
			this.modal = true
			this.requesting = false
			this.success = false
			this.error = false
			this.errorMessage = ''
		},
		closeModal() {
			if (this.source !== null) {
				this.source.cancel('Operation canceled by the user.')
				this.source = null
			}

			this.modal = false
		},
		advancedSignature() {
			this.error = false
			this.requesting = true
			const self = this
			const baseUrl = OC.generateUrl('/apps/openotpsign')

			const CancelToken = axios.CancelToken
			this.source = CancelToken.source()
			axios.post(baseUrl + '/advanced_sign', {
				path: this.getFilePath(),
			}, {
				cancelToken: this.source.token,
			})
				.then(function(response) {
					self.requesting = false
					if (response.data.code === 1) {
						self.success = true
					} else {
						self.error = true
						self.errorMessage = 'Error: ' + response.data.message
					}
				})
				.catch(function(error) {
					self.requesting = false
					self.error = true
					self.errorMessage = error
				})
		},
		qualifiedSignature() {
			this.requesting = true
			const self = this
			setTimeout(function() {
				self.requesting = false
			}, 2000)
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
</style>
