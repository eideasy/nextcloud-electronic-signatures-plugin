<template>
	<NcContent :class="{'icon-loading': loading}" app-name="vueexample" />
</template>

<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'

export default {
	name: 'App',
	components: {
    NcContent,
	},
	data() {
		return {
			loading: false,
			date: Date.now() + 86400000 * 3,
			date2: Date.now() + 86400000 * 3 + Math.floor(Math.random() * 86400000 / 2),
			show: true,
			starred: false,
		};
	},
	methods: {
		addOption(val) {
			this.options.push(val);
			this.select.push(val);
		},
		previous(data) {
			console.debug(data);
		},
		next(data) {
			console.debug(data);
		},
		close(data) {
			console.debug(data);
		},
		newButtonAction(e) {
			console.debug(e);
		},
		log(e) {
			console.debug(e);
		},
	},
};
</script>
