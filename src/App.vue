<template>
	<!--
	SPDX-FileCopyrightText: RCDevs <netadm@rcdevs.com>
	SPDX-License-Identifier: AGPL-3.0-or-later
	-->
	<div class="app-container">
		<div id="app-navigation" role="navigation">
			<ul>
				<li>
					<router-link to="/">
						{{ $t('openotp_sign', 'Pending signature requests') }}
					</router-link>
				</li>
				<li>
					<router-link to="/completed">
						{{ $t('openotp_sign', 'Completed signature requests') }}
					</router-link>
				</li>
				<li>
					<router-link to="/failed">
						{{ $t('openotp_sign', 'Failed signature requests') }}
					</router-link>
				</li>
			</ul>
			<div id="app-settings">
				<div id="app-settings-header">
					<button class="settings-button" data-apps-slide-toggle="#app-settings-content" />
				</div>
				<div id="app-settings-content">
					<!-- Your settings in here -->
				</div>
			</div>
		</div>
		<div id="app-content" tabindex="0" touch-action="pan-y">
			<div id="app-content-wrapper">
				<router-view />
			</div>
		</div>
	</div>
</template>

<style scoped>
.flex-container {
	display: flex;
}

.flex-child {
	flex: 1;
	border: 2px solid yellow;
}

.flex-child:first-child {
	margin-right: 20px;
}

.app-container {
	margin-top: 0px;
	display: flex;
	width: 100%;
}

#app-content >>> h1 {
	font-size: 2em;
	text-align: center;
}

#app-content >>> table {
	border-collapse: collapse;
	margin: 25px 0;
	font-size: 0.9em;
	font-family: sans-serif;
	width: 100%;
	box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
}

#app-content >>> thead tr {
	background-color: var(--color-primary-element-light);
	color: #ffffff;
	text-align: left;
}

#app-content >>> th, #app-content >>> td {
	padding: 12px 15px;
}

#app-content >>> tr {
	border-bottom: 1px solid #dddddd;
}

#app-content >>> tr:last-of-type {
	border-bottom: 2px solid var(--color-primary-element-light);
}

#app-content >>> img {
	display: block;
	margin: 25px auto;
}

#app-content >>> td img {
	margin: 0 auto;
}

#app-content >>> .component-container {
	margin: 20px;
	width: 100%;
}

#app-content >>> .pagination {
	list-style: none;
	padding: 0;
}

#app-content >>> .pagination li {
	float: left;
}

#app-content >>> .pagination a {
	display: block;
	border: solid #dddddd 1px;
	padding: 6px 12px;
	margin-right: -1px;
	color: var(--color-primary-element-light);
}

#app-content >>> .pagination .active a {
	color: var(--color-main-background);
	background-color: var(--color-primary-element-light);
	cursor: default;
}

#app-content >>> .pagination .disabled a {
	color: #dddddd;
	cursor: not-allowed;
}

#app-content >>> .pagination li:not(.active):not(.disabled) a:hover {
	background-color: #dddddd;
}
</style>
