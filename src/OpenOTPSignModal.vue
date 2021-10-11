<template>
	<div>
		<button @click="showModal">
			Show Modal
		</button>
		<Modal v-if="modal" size="small" @close="closeModal">
			<div class="modal__content">
				Hello world
			</div>
		</Modal>
	</div>
</template>
<script>
import Modal from '@nextcloud/vue/dist/Components/Modal'
import EventBus from './EventBus'

export default {
	name: 'OpenOTPSignModal',
	components: {
		Modal,
	},
	data() {
		return {
			modal: false,
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
		},
		closeModal() {
			this.modal = false
		},
	},
}
</script>
<style scoped>
	.modal__content {
		margin: 50px;
		text-align: center;
	}

</style>
