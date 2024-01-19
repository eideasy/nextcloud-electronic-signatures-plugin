import config from './config';
import Vue from 'vue';
import SignatureLinkModal from './SignatureLinkModal';
import EventBus from './EventBus';
import { translate, translatePlural } from '@nextcloud/l10n';

Vue.prototype.$t = translate;
Vue.prototype.$n = translatePlural;
Vue.prototype.$globalConfig = config;

function addCustomFileActions() {
  const { t } = window;

  const modalHolderId = 'esigModalHolder';
  const modalHolder = document.createElement('div');
  modalHolder.id = modalHolderId;
  document.body.append(modalHolder);
 /* eslint-disable no-unused-vars */
  const vm = new Vue({
    el: modalHolder,
    render: h => {
      return h(SignatureLinkModal);
    },
  });
  /* eslint-enable */

  const fileActionsPlugin = {
    attach(fileList) {
      fileList.fileActions.registerAction({
        mime: 'file',
        name: 'Sign',
        displayName: t(config.appId, 'Signing'),
        order: -100,
        permissions: 0,
        iconClass: 'custom-icon-signature',
        actionHandler(filename, context) {
          EventBus.$emit('SIGNATURES_CLICK', { filename });
        },
      });
    },
  };
  console.log('4389057ujew9e8fepofj registering fileActionsPlugin');
  OC.Plugins.register('OCA.Files.FileList', fileActionsPlugin);
}

addCustomFileActions();
