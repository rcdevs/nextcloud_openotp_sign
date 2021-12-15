<template>
	<div class="component-container">
		<h1>Pending signature requests</h1>
		<div v-if="requesting">
			<img :src="loadingImg">
		</div>
		<EmptyContent v-else-if="!requests.length" icon="icon-comment">
			No pending signature requests
			<template #desc>
				There are currently no pending signature requests
			</template>
		</EmptyContent>
		<table v-else>
			<thead>
				<tr>
					<th>Date</th>
					<th>Expiration Date</th>
					<th>Mode</th>
					<th>Recipient</th>
					<th>Type</th>
					<th style="width: 100%">
						File
					</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(request, index) in requests" :key="index">
					<td>{{ request.created }}</td>
					<td>{{ request.expiration_date }}</td>
					<td>{{ request.is_qualified ? 'qualified' : 'advanced' }}</td>
					<td>{{ request.recipient }}</td>
					<td>{{ request.is_yumisign ? 'YumiSign' : 'Nextcloud' }}</td>
					<td>{{ request.path }}</td>
					<td>
						<img v-if="canceling[index]" :src="loadingImg">
						<button v-else @click="cancelRequest(index, request.session)">
							Cancel request
						</button>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
<script>
import Vue from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'Pending',
	components: {
		EmptyContent,
	},
	data() {
		return {
			requesting: false,
			requests: [],
			canceling: [],
		}
	},
	mounted() {
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'
		this.requesting = true

		const baseUrl = generateUrl('/apps/openotp_sign')
		axios.get(baseUrl + '/pending_requests')
			.then(response => {
				this.requesting = false
				this.canceling.splice(response.data.length)
				this.requests = response.data
			})
			.catch(error => {
				this.requesting = false
				// eslint-disable-next-line
				console.log(error)
			})
	},
	methods: {
		cancelRequest(index, session) {
			if (confirm('Are you sure you want to cancel this signature request ?')) {
				Vue.set(this.canceling, index, true)

				const baseUrl = generateUrl('/apps/openotp_sign')
				axios.put(baseUrl + '/cancel_sign_request', {
					session,
				})
					.then(response => {
						Vue.set(this.canceling, index, false)
						if (response.data.code === 1) {
							this.requests.splice(index, 1)
						} else {
							alert(response.data.message)
						}
					})
					.catch(error => {
						Vue.set(this.canceling, index, false)
						alert(error)
					})
			}
		},
	},
}
</script>
<style scoped>
h1 {
	font-size: 2em;
	text-align: center;
}

table {
	border-collapse: collapse;
	margin: 25px 0;
	font-size: 0.9em;
	font-family: sans-serif;
	width: 100%;
	box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
}

thead tr {
	background-color: var(--color-primary-element-light);
	color: #ffffff;
	text-align: left;
}

th, td {
	padding: 12px 15px;
}

tr {
	border-bottom: 1px solid #dddddd;
}

tr:last-of-type {
	border-bottom: 2px solid var(--color-primary-element-light);
}

img {
	display: block;
	margin: 25px auto;
}

td img {
	margin: 0 auto;
}

.component-container {
	margin: 20px;
	width: 100%;
}
</style>
