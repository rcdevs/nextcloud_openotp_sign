<template>
	<div>
		<h1>Failed signature requests</h1>
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Failed',
	mounted() {
		const baseUrl = generateUrl('/apps/openotp_sign')

		axios.get(baseUrl + '/failed_requests')
			.then(response => {
				// eslint-disable-next-line
				console.log(response.data)
			})
			.catch(error => {
				// eslint-disable-next-line
				console.log(error)
			})
	},
}
</script>
