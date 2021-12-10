<template>
	<div class="component-container">
		<h1>Failed signature requests</h1>
		<div v-if="requesting">
			<img :src="loadingImg">
		</div>
		<EmptyContent v-else-if="!requests.length" icon="icon-comment">
			No failed signature requests
			<template #desc>
				There are currently no failed signature requests
			</template>
		</EmptyContent>
		<table v-else>
			<thead>
				<tr>
					<th>Date</th>
					<th>Mode</th>
					<th>Recipient</th>
					<th>Type</th>
					<th>File</th>
					<th style="width: 100%">
						Error
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(request, index) in requests" :key="index">
					<td>{{ request.created }}</td>
					<td>{{ request.is_qualified ? 'qualified' : 'advanced' }}</td>
					<td>{{ request.recipient }}</td>
					<td>{{ request.is_yumisign ? 'YumiSign' : 'Nextcloud' }}</td>
					<td>{{ request.path }}</td>
					<td>{{ request.message }}</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'

export default {
	name: 'Failed',
	components: {
		EmptyContent,
	},
	data() {
		return {
			requesting: false,
			requests: [],
		}
	},
	mounted() {
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'
		this.requesting = true

		const baseUrl = generateUrl('/apps/openotp_sign')
		axios.get(baseUrl + '/failed_requests')
			.then(response => {
				this.requesting = false
				this.requests = response.data
				// eslint-disable-next-line
				console.log(this.requests)
			})
			.catch(error => {
				this.requesting = false
				// eslint-disable-next-line
				console.log(error)
			})
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

.component-container {
	margin: 20px;
	width: 100%;
}
</style>
