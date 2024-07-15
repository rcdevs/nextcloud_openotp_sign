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
	<div class="search-results" :class="{scrollable: scrollable}">
		<template v-if="addableUsers.length !== 0">
			<ul>
				<ResultItem v-for="item in addableUsers" :key="generateKey(item)" :item="item" :search-text="searchText" @click-item="handleClickItem" />
			</ul>
		</template>

		<template v-if="addableEmails.length !== 0">
			<ul>
				<ResultItem v-for="item in addableEmails" :key="generateKey(item)" :item="item" :search-text="searchText" @click-item="handleClickItem" />
			</ul>
		</template>

		<Hint v-if="entriesLoading" :hint="ui.search.searching" />
		<Hint v-if="noResults && !entriesLoading && sourcesWithoutResults" :hint="ui.search.noSearchResults" />
	</div>
</template>

<script>
import {getT} from '../javascript/utility.js';
import Hint from './Hint.vue';
import ResultItem from './ResultItem.vue';

export default {
	name: 'SearchResults',

	data() {
		this.ui = {
			search: {
				searching: getT('Searching …'),
				noSearchResults: getT('No search results'),
			},
		};
	},

	components: {
		Hint,
		ResultItem,
	},

	props: {
		searchText: {
			type: String,
			required: true,
		},
		searchResults: {
			type: Object,
			required: true,
		},
		entriesLoading: {
			type: Boolean,
			required: true,
		},
		/**
		 * Display no-results state instead of list.
		 */
		noResults: {
			type: Boolean,
			default: false,
		},
		scrollable: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		sourcesWithoutResults() {
			return !this.addableUsers.length && !this.addableEmails.length;
		},

		addableUsers() {
			const exactUsers = this.searchResults.exact?.users || [];
			const users = this.searchResults.users || [];
			return exactUsers.concat(users);
		},

		addableEmails() {
			const emails = this.searchResults.emails || [];
			return emails;
		},
	},

	methods: {
		async handleClickItem(item) {
			this.$emit('click', item);
		},

		generateKey(item) {
			return item.shareWithDisplayNameUnique || '';
		},
	},
};
</script>

<style lang="scss" scoped>
.scrollable {
	overflow-y: auto;
	overflow-x: hidden;
	flex-shrink: 1;
}
</style>
