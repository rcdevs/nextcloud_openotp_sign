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
	<PopupModal ref="popup">
		<h2 style="margin-top: 0">
			{{ title }}
		</h2>
		<p>{{ message }}</p>
		<div class="btns">
			<button @click="_cancel">
				{{ cancelButton }}
			</button>
			<button @click="_confirm">
				{{ okButton }}
			</button>
		</div>
	</PopupModal>
</template>

<script>
import PopupModal from './PopupModal.vue'

export default {
	name: 'CancelRequestForce',

	components: { PopupModal },

	data: () => ({
		// Parameters that change depending on the type of dialogue
		title: undefined,
		message: undefined, // Main text content
		okButton: undefined, // Text for confirm button; leave it empty because we don't know what we're using it for
		cancelButton: 'Cancel', // text for cancel button

		// Private variables
		resolvePromise: undefined,
		rejectPromise: undefined,
	}),

	methods: {
		show(opts = {}) {
			this.title = opts.title
			this.message = opts.message
			this.okButton = opts.okButton
			if (opts.cancelButton) {
				this.cancelButton = opts.cancelButton
			}
			// Once we set our config, we tell the popup modal to open
			this.$refs.popup.open()
			// Return promise so the caller can get results
			return new Promise((resolve, reject) => {
				this.resolvePromise = resolve
				this.rejectPromise = reject
			})
		},

		_confirm() {
			this.$refs.popup.close()
			this.resolvePromise(true)
		},

		_cancel() {
			this.$refs.popup.close()
			this.resolvePromise(false)
			// Or you can throw an error
			// this.rejectPromise(new Error('User canceled the dialogue'))
		},
	},
}
</script>

<style scoped>
.btns {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
}

.ok-btn {
	/* color: red; */
	/* text-decoration: underline; */
	/* line-height: 2.5rem; */
	cursor: pointer;
}

.cancel-btn {
	padding: 0.5em 1em;
	/* background-color: #d5eae7; */
	/* color: #35907f; */
	/* border: 2px solid #0ec5a4; */
	/* border-radius: 5px; */
	/* font-weight: bold; */
	/* font-size: 16px; */
	/* text-transform: uppercase; */
	cursor: pointer;
}
</style>
