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
	<div class="component-container">
		<IssueTitle>
			{{ ui.requestsIssues }}
		</IssueTitle>

		<div v-if="axiosTransaction.inProgress">
			<img :src="loadingImg" />
		</div>
		<NcEmptyContent v-else-if="!requests.length" icon="icon-comment" :description="ui.noSignatureRequestsIssuesShort">
			<template #desc>
				{{ ui.noSignatureRequestsIssuesLong }}
			</template>
		</NcEmptyContent>
		<table v-else class="listings">
			<thead>
				<tr>
					<th>
						{{ ui.creationDate }}
					</th>
					<th>
						{{ ui.expirationDate }}
					</th>
					<th>
						{{ ui.recipient }}
					</th>
					<th>
						{{ ui.file }}
					</th>
					<th>
						{{ ui.transactionStatus }}
					</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(request, index) in requests" :key="index" :class="['rcdevsTransaction_' + request.transactions[0].session]">
					<td>{{ request.transactions[0].created | moment }}</td>
					<td>{{ request.transactions[0].expiry_date | moment }}</td>
					<td>
						<div v-for="(transaction, indexTr) in request.transactions" :key="indexTr" :class="['multipleRows rcdevsTransaction_' + request.transactions[0].session + '_' + (request.transactions.length === 1 ? 'all' : indexTr)]">
							<span>{{ transaction.recipient }}</span>
						</div>
					</td>
					<td>{{ request.transactions[0].file_path }}</td>
					<td>
						{{ getTransactionStatus(request.transactions[0]) }}
					</td>
					<td>
						<img v-if="removing[index]" :src="loadingImg" />
						<button v-else @click="deleteRequest(index, request.transactions[0].session, false, false)">
							{{ ui.deleteRequest }}
						</button>
					</td>
				</tr>
			</tbody>
		</table>
		<paginationFlexContainer>
			<Paginate v-show="pageCount > 1 && requests.length" :page-count="pageCount" :page-range="3" :margin-pages="2" :click-handler="axiosChangePage" :prev-text="ui.previous" :next-text="ui.next" :container-class="'paginationContainer'" :page-class="'paginationPage'" :prev-class="'paginationPage'" :next-class="'paginationPage'" />
		</paginationFlexContainer>

		<ConfirmDialogue ref="ConfirmDialogue" />
	</div>
</template>

<script>
import '../styles/rcdevsListing.css';
import {appName, baseUrl} from '../javascript/config.js';
import {generateFilePath, generateOcsUrl} from '@nextcloud/router';
import {getOcsUrl, getT, log} from '../javascript/utility';
import axios from '@nextcloud/axios';
import ConfirmDialogue from './CancelRequestForce.vue';
import moment from 'moment';
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js';
import Paginate from 'vuejs-paginate';

export default {
	name: 'Issues',
	components: {
		NcEmptyContent,
		Paginate,
		ConfirmDialogue,
	},
	filters: {
		moment(date) {
			return moment(date * 1000).format('YYYY-MM-DD HH:mm:ss');
		},
	},
	data() {
		this.apis = [];
		this.apis.requestsIssues = '/requests/issues';
		this.apis.uiItemsPage = '/ui/items/page';

		this.ui = [];
		this.ui.requestsIssues = getT('Requests issues');
		this.ui.noSignatureRequestsIssuesShort = getT('No signature requests issues');
		this.ui.noSignatureRequestsIssuesLong = getT('There are currently no signature requests issues');
		this.ui.creationDate = getT('Creation date');
		this.ui.expirationDate = getT('Expiration date');
		this.ui.recipient = getT('Recipient');
		this.ui.file = getT('File');
		this.ui.transactionStatus = getT('Transaction status');
		this.ui.deleteRequest = getT('Delete request');
		this.ui.previous = getT('Previous');
		this.ui.next = getT('Next');

		return {
			pageCount: null,
			requests: [],
			removing: [],

			axiosChecking: {
				abortCtrl: null,
				inProgress: false,
				success: false,
				error: false,
				message: null,
			},

			axiosTransaction: {
				abortCtrl: null,
				inProgress: false,
				success: false,
				error: false,
				message: null,
			},
		};
	},
	created() {
		const CancelToken = axios.CancelToken;
		this.source = CancelToken.source();
	},
	mounted() {
		this.NB_ITEMS_PER_PAGE = 20; // default value if API call fails
		this.loadingImg = generateFilePath('core', '', 'img/') + 'loading.gif';

		this.axiosUiItemsPage();

		this.axiosChangePage();
	},

	methods: {
		// runAPI: function (urlApi) {
		// 	try {
		// 		return axios
		// 			.get(
		// 				generateOcsUrl(`apps/${appName}/api/v1/${urlApi}`),
		// 				{},
		// 				{
		// 					cancelToken: this.source.token,
		// 				}
		// 			)
		// 			.catch((error) => {
		// 				this.error = true;
		// 				console.log(error);
		// 			});
		// 	} catch (error) {
		// 		console.error(error.message);
		// 	}
		// },

		axiosChangePage(pageNum) {
			try {
				log.info(`[${this.getFunctionName()}] Running...`);
				this.axiosTransaction = this.initAxios();

				axios
					.post(
						getOcsUrl(this.apis.requestsIssues),
						{
							page: pageNum - 1,
							nbItems: this.NB_ITEMS_PER_PAGE,
						},
						{
							signal: this.axiosTransaction.abortCtrl.signal,
						}
					)
					.then((response) => {
						log.debug(`Issues changing page : [${JSON.stringify(response.data)}]`);

						if (parseInt(response.data.code) !== 1) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}

						this.axiosTransaction.error = !(this.axiosTransaction.success = true);
						this.axiosTransaction.message = response.data.message;

						this.pageCount = Math.ceil(response.data.count / this.NB_ITEMS_PER_PAGE);
						// this.removing.splice(response.data.requests.length);
						this.requests = this.getRequests(response.data.requests);
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosTransaction.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosTransaction.message = exception.message;
						}

						this.axiosTransaction.error = true;
					})
					.finally(() => {
						this.axiosTransaction.inProgress = false;
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		axiosUiItemsPage() {
			try {
				log.info(`[${this.getFunctionName()}] Running...`);
				this.axiosChecking = this.initAxios();

				axios
					.get(getOcsUrl(this.apis.uiItemsPage), {
						signal: this.axiosChecking.abortCtrl.signal,
					})
					.then((response) => {
						log.debug(`UI items per page : [${JSON.stringify(response.data)}]`);

						if (parseInt(response.data.code) !== 1) {
							throw new Error(getT('Error: ') + getT(response.data.message));
						}

						this.axiosChecking.error = !(this.axiosChecking.success = true);
						this.axiosChecking.message = response.data.message;

						this.NB_ITEMS_PER_PAGE = response.data.itemsPerPage;
					})
					.catch((exception) => {
						if (axios.isCancel(exception)) {
							this.axiosChecking.message = this.ui.axios.requestCancelled;
						} else {
							this.axiosChecking.message = exception.message;
						}

						this.axiosChecking.error = true;
					})
					.finally(() => {
						this.axiosChecking.inProgress = false;
					});
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},

		async deleteRequest(index, session, recipient, tagToDelete) {
			const ok = await this.$refs.ConfirmDialogue.show({
				title: getT('Deleting signature issue'),
				message: t(appName, 'Are you sure you want to delete this signature issue ?'),
				cancelButton: getT('Go back'),
				okButton: getT('Delete issue'),
			});
			if (recipient === false) {
				recipient = '';
			}
			if (ok) {
				let urlRequest = generateOcsUrl(baseUrl + '/api/v1/requests/deletion');
				axios
					.put(urlRequest, {
						session,
						recipient,
					})
					.then((response) => {
						switch (response.data.code) {
							case '1':
								this.deleteTableRows(this.requests, session, recipient, tagToDelete);
								break;
							case true:
								this.deleteTableRows(this.requests, session, recipient, tagToDelete);
								break;
							default:
								alert(response.data.message);
								break;
						}
					})
					.catch((error) => {
						alert(getT('Deletion process failed') + '\n' + error);
					});
			}
		},

		deleteTableRows(requests, session, recipient, tagToDelete) {
			if (!tagToDelete) {
				// Remove the entire row (means the Transaction with all recipients)
				document.querySelectorAll('.rcdevsTransaction_' + session).forEach((el) => el.remove());
			} else {
				// Remove only the recipient; Special: if only one recipient (tag ends with '_all'), remove the entire row
				if (tagToDelete.substr(tagToDelete.length - 4).toLowerCase() === '_all') {
					document.querySelectorAll('.rcdevsTransaction_' + session).forEach((el) => el.remove());
				} else {
					document.querySelectorAll('.' + tagToDelete).forEach((el) => el.remove());
				}
			}
		},

		getFunctionName: function () {
			const error = new Error();
			const stackLines = error.stack.split('\n');
			// The stack trace format can vary; you may need to adjust the index
			const callerLine = stackLines[2].trim();
			const functionName = callerLine.split(' ')[1];
			return functionName;
		},

		getRequests(responseRequests) {
			// Group recipients for the same transaction (only one line per transaction)
			const tmpRequests = {};
			const returnRequests = [];

			responseRequests.forEach((transaction) => {
				if (!tmpRequests[transaction.session]) {
					// session not found
					const transactionArray = [transaction];
					tmpRequests[transaction.session] = {
						transactions: transactionArray,
					};
				} else {
					// session found, append
					tmpRequests[transaction.session].transactions.push(transaction);
				}
			});

			// Convert object into an array
			Object.keys(tmpRequests).forEach((key) => {
				returnRequests.push(tmpRequests[key]);
			});

			return returnRequests;
		},

		getTransactionStatus: function (requestTransactions) {
			return getT(requestTransactions.global_status);
		},

		initAxios: function () {
			try {
				log.info(`[${this.getFunctionName()}] Running...`);
				let currentAxios = {};
				currentAxios.abortCtrl = new AbortController();
				currentAxios.inProgress = true;
				currentAxios.error = false;
				currentAxios.message = null;

				return currentAxios;
			} catch (exception) {
				log.error(`[${this.getFunctionName()}] ${exception}`);
			}
		},
	},
};
</script>
