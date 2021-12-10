<template>
	<div class="app-container">
		<div id="app-navigation">
			<ul>
				<li>
					<router-link to="/">
						Pending signature requests
					</router-link>
				</li>
				<li>
					<router-link to="/completed">
						Completed signature requests
					</router-link>
				</li>
				<li>
					<router-link to="/failed">
						Failed signature requests
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

		<div id="app-content">
			<div id="app-content-wrapper">
				<router-view />
			</div>
		</div>
	</div>
</template>
<style scoped>
.app-container {
	width: 100%;
}
</style>
