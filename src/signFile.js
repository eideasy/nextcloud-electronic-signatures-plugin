import Vue from 'vue';
import { translate, translatePlural } from '@nextcloud/l10n';
import AppSign from './AppSign';
import './fileActions';
import config from './config';

Vue.prototype.$t = translate;
Vue.prototype.$n = translatePlural;
Vue.prototype.$globalConfig = config;
// from https://nextcloud-vue-components.netlify.app/
// Some components require Nextcloud functionality to work, so it is currently recommended to extend Vue with the following:
Vue.prototype.t = window.t;
Vue.prototype.n = window.n;
Vue.prototype.OC = window.OC;
Vue.prototype.OCA = window.OCA;

const signingRootElement = document.getElementById('electronic-signatures-sign-root');
// eslint-disable-next-line
new Vue({
    el: '#electronic-signatures-sign-root',
    data: () => Object.assign({}, signingRootElement.dataset),
    render: h => h(AppSign),
});
