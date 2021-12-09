import Vue from 'vue'
import Router from 'vue-router'
import Pending from './Pending'
import Completed from './Completed'
import Failed from './Failed'

Vue.use(Router)

export default new Router({
	mode: 'hash',
	base: process.env.BASE_URL,
	linkExactActiveClass: 'active',
	routes: [
		{
			path: '/',
			name: 'pending',
			component: Pending,
		},
		{
			path: '/completed',
			name: 'completed',
			component: Completed,
		},
		{
			path: '/failed',
			name: 'failed',
			component: Failed,
		},
	],
})
