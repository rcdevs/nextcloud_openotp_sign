<template>
	<div>
		<h1>Pending signature requests</h1>
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Pending',
	mounted() {
		const baseUrl = generateUrl('/apps/openotp_sign')

		axios.get(baseUrl + '/pending_requests')
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
