<template>
	<div class="component-container">
		<h1>{{ $t('openotp_sign', 'Pending signature requests') }}</h1>
		<div v-if="requesting">
			<img :src="loadingImg">
		</div>
		<EmptyContent v-else-if="!requests.length" icon="icon-comment">
			{{ $t('openotp_sign', 'No pending signature requests') }}
			<template #desc>
				{{ $t('openotp_sign', 'There are currently no pending signature requests') }}
			</template>
		</EmptyContent>
		<table v-else>
			<thead>
				<tr>
					<th>{{ $t('openotp_sign', 'Date') }}</th>
					<th>{{ $t('openotp_sign', 'Expiration Date') }}</th>
					<th>{{ $t('openotp_sign', 'Mode') }}</th>
					<th>{{ $t('openotp_sign', 'Recipient') }}</th>
					<th>Type</th>
					<th style="width: 100%">
						{{ $t('openotp_sign', 'File') }}
					</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(request, index) in requests" :key="index">
					<td>{{ request.created }}</td>
					<td>{{ request.expiration_date }}</td>
					<td>{{ request.is_advanced ? 'advanced' : 'mobile' }}</td>
					<td>{{ request.recipient }}</td>
					<td>{{ request.is_yumisign ? 'YumiSign' : 'Nextcloud' }}</td>
					<td>{{ request.path }}</td>
					<td>
						<img v-if="canceling[index]" :src="loadingImg">
						<button v-else @click="cancelRequest(index, request.session)">
							{{ $t('openotp_sign', 'Cancel request') }}
						</button>
					</td>
				</tr>
			</tbody>
		</table>
		<Paginate v-show="pageCount > 1 && requests.length"
			:page-count="pageCount"
			:page-range="3"
			:margin-pages="2"
			:click-handler="changePage"
			:prev-text="$t('openotp_sign', 'Previous')"
			:next-text="$t('openotp_sign', 'Next')"
			:container-class="'pagination'">
		</Paginate>
	</div>
</template>
<script>
import Vue from 'vue'
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import Paginate from 'vuejs-paginate'

export default {
	name: 'Pending',
	components: {
		EmptyContent,
		Paginate
	},
	data() {
		return {
			pageCount: null,
			requesting: false,
			requests: [],
			canceling: [],
		}
	},
	created() {
		this.NB_ITEMS_PER_PAGE = 20
	},
	mounted() {
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif'
		this.requesting = true

		const baseUrl = generateUrl('/apps/openotp_sign')
		axios.get(baseUrl + '/pending_requests?nbItems=' + this.NB_ITEMS_PER_PAGE)
			.then(response => {
				this.requesting = false
				this.pageCount = Math.ceil(response.data.count / this.NB_ITEMS_PER_PAGE)
				this.canceling.splice(response.data.requests.length)
				this.requests = response.data.requests
			})
			.catch(error => {
				this.requesting = false
				// eslint-disable-next-line
				console.log(error)
			})
	},
	methods: {
		cancelRequest(index, session) {
			if (confirm(t('openotp_sign', 'Are you sure you want to cancel this signature request ?'))) {
				Vue.set(this.canceling, index, true)

				const baseUrl = generateUrl('/apps/openotp_sign')
				axios.put(baseUrl + '/cancel_sign_request', {
					session,
				})
					.then(response => {
						Vue.set(this.canceling, index, false)
						if (response.data.code === '1') {
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
		changePage(pageNum) {
			this.requesting = true
			this.requests = []

			const baseUrl = generateUrl('/apps/openotp_sign')

			axios.get(baseUrl + '/pending_requests?page=' + (pageNum - 1) + '&nbItems=' + this.NB_ITEMS_PER_PAGE)
				.then(response => {
					this.requesting = false
					this.pageCount = Math.ceil(response.data.count / this.NB_ITEMS_PER_PAGE)
					this.canceling.splice(response.data.requests.length)
					this.requests = response.data.requests
				})
				.catch(error => {
					this.requesting = false
					// eslint-disable-next-line
					console.log(error)
				})
		}
	},
}
</script>
