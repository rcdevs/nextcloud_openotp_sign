<template>
	<div class="component-container">
		<h1>{{ $t('openotp_sign', 'Failed signature requests') }}</h1>
		<div v-if="requesting">
			<img :src="loadingImg">
		</div>
		<EmptyContent v-else-if="!requests.length" icon="icon-comment">
			{{ $t('openotp_sign', 'No failed signature requests') }}
			<template #desc>
				{{ $t('openotp_sign', 'There are currently no failed signature requests') }}
			</template>
		</EmptyContent>
		<table v-else>
			<thead>
				<tr>
					<th>{{ $t('openotp_sign', 'Date') }}</th>
					<th>{{ $t('openotp_sign', 'Mode') }}</th>
					<th>{{ $t('openotp_sign', 'Recipient') }}</th>
					<th>{{ $t('openotp_sign', 'Type') }}</th>
					<th>{{ $t('openotp_sign', 'File') }}</th>
					<th style="width: 100%">
						{{ $t('openotp_sign', 'Error') }}
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
		<Paginate v-show="pageCount > 1 && requests.length"
			:page-count="pageCount"
			:page-range="3"
			:margin-pages="2"
			:click-handler="changePage"
			:prev-text="'Prev'"
			:next-text="'Next'"
			:container-class="'pagination'">
		</Paginate>
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import Paginate from 'vuejs-paginate'

export default {
	name: 'Failed',
	components: {
		EmptyContent,
		Paginate
	},
	data() {
		return {
			pageCount: null,
			requesting: false,
			requests: [],
		}
	},
	created() {
		this.NB_ITEMS_PER_PAGE = 20
	},
	mounted() {
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'
		this.requesting = true

		const baseUrl = generateUrl('/apps/openotp_sign')
		axios.get(baseUrl + '/failed_requests?nbItems=' + this.NB_ITEMS_PER_PAGE)
			.then(response => {
				this.requesting = false
				this.pageCount = Math.ceil(response.data.count / this.NB_ITEMS_PER_PAGE)
				this.requests = response.data.requests
			})
			.catch(error => {
				this.requesting = false
				// eslint-disable-next-line
				console.log(error)
			})
	},
	methods: {
		changePage(pageNum) {
			this.requesting = true
			this.requests = []

			const baseUrl = generateUrl('/apps/openotp_sign')

			axios.get(baseUrl + '/failed_requests?page=' + (pageNum - 1) + '&nbItems=' + this.NB_ITEMS_PER_PAGE)
				.then(response => {
					this.requesting = false
					this.pageCount = Math.ceil(response.data.count / this.NB_ITEMS_PER_PAGE)
					this.requests = response.data.requests
				})
				.catch(error => {
					this.requesting = false
					// eslint-disable-next-line
					console.log(error)
				})
		}
	}
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

.pagination {
	list-style: none;
	padding: 0;
}

.pagination >>> li {
	float: left;
}

.pagination >>> a {
	display: block;
	border: solid #dddddd 1px;
	padding: 6px 12px;
	margin-right: -1px;
	color: var(--color-primary-element-light);
}

.pagination >>> .active > a {
	color: var(--color-main-background);
	background-color: var(--color-primary-element-light);
	cursor: default;
}

.pagination >>> .disabled > a {
	color: #dddddd;
	cursor: not-allowed;
}

.pagination >>> li:not(.active):not(.disabled) > a:hover {
	background-color: #dddddd;
}
</style>
