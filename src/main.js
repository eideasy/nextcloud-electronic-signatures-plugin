import Vue from 'vue';
import { translate, translatePlural } from '@nextcloud/l10n';
import App from './App';
import AppAdmin from './AppAdmin';
import AppSign from './AppSign';
import './fileActions';
import config from './config';

Vue.prototype.$t = translate;
Vue.prototype.$n = translatePlural;
Vue.prototype.$globalConfig = config;

let vueInstance;
if (document.getElementById('electronic-signatures-root')) {
  vueInstance = new Vue({
    el: '#electronic-signatures-root',
    render: h => h(App),
  });
}

if (document.getElementById('electronic-signatures-sign-root')) {
  vueInstance = new Vue({
    el: '#electronic-signatures-sign-root',
    render: h => h(AppSign),
  });
}

const adminRootElement = document.getElementById('electronic-signatures-admin-root');
if (adminRootElement) {
  vueInstance = new Vue({
    el: adminRootElement,
    data: () => Object.assign({}, adminRootElement.dataset),
    render: h => h(AppAdmin),
  });
}

export default vueInstance;
