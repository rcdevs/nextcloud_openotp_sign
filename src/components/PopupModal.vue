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
	<transition name="fade">
		<div v-if="isVisible" class="popup-modal">
			<div class="window">
				<slot />
			</div>
		</div>
	</transition>
</template>

<script>
export default {
	name: 'PopupModal',

	data: () => ({
		isVisible: false,
	}),

	methods: {
		open() {
			this.isVisible = true
		},

		close() {
			this.isVisible = false
		},
	},
}
</script>

<style scoped>
/* css class for the transition */
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.3s;
}

.fade-enter,
.fade-leave-to {
	opacity: 0;
}

.popup-modal {
	background-color: rgba(0, 0, 0, 0.5);
	position: fixed;
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 0.5rem;
	display: flex;
	align-items: center;
	z-index: 1;
}

.window {
	background: #fff;
	border-radius: 5px;
	box-shadow: 2px 4px 8px rgba(0, 0, 0, 0.2);
	max-width: 480px;
	margin-left: auto;
	margin-right: auto;
	padding: 1rem;
}
</style>
