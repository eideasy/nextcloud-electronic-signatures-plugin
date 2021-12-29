import Vue from 'vue';
import VueCompositionAPI from '@vue/composition-api';
import { translate, translatePlural } from '@nextcloud/l10n';
import App from './App';
import config from './config';

Vue.use(VueCompositionAPI);

Vue.prototype.$t = translate;
Vue.prototype.$n = translatePlural;
Vue.prototype.$globalConfig = config;
// from https://nextcloud-vue-components.netlify.app/
// Some components require Nextcloud functionality to work, so it is currently recommended to extend Vue with the following:
Vue.prototype.t = window.t;
Vue.prototype.n = window.n;
Vue.prototype.OC = window.OC;
Vue.prototype.OCA = window.OCA;

// eslint-disable-next-line
new Vue({
    el: '#electronic-signatures-root',
    render: h => h(App),
});
