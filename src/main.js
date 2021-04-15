import Vue from 'vue';
import { translate, translatePlural } from '@nextcloud/l10n';
import App from './App';
import './fileActions';

Vue.prototype.t = translate;
Vue.prototype.n = translatePlural;

let vueInstance;
if (document.getElementById('electronic-signatures-root')) {
  vueInstance = new Vue({
    el: '#electronic-signatures-root',
    render: h => h(App),
  });
}

export default vueInstance;
