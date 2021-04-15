<template>
	<Content :class="{'icon-loading': loading}" app-name="vueexample">
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content';

export default {
	name: 'App',
	components: {
		Content,
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
