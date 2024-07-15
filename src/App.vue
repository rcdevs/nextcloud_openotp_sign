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
    <NcContent :class="{ 'icon-loading': loading }" app-name="openotp_sign">
        <NcAppContent>
            <div class="rcdevsMainContainer">
                <Pending />
                <Issues  />
            </div>
        </NcAppContent>
    </NcContent>
</template>

<script>
import NcAppContent from "@nextcloud/vue/dist/Components/NcAppContent.js";
import NcContent from "@nextcloud/vue/dist/Components/NcContent.js";

import Pending from "./components/Pending.vue";
import Issues from "./components/Issues.vue";
import "./styles/rcdevsStyle.css";

export default {
    name: "App",

    components: {
        NcAppContent,
        NcContent,
        Pending,
        Issues,
    },

    data() {
        return {
            loading: false,
        };
    },
};
</script>
<style lang="scss">
table th,
table td {
    padding: 4px 8px;
}
</style>
