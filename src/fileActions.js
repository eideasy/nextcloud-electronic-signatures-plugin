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

  const vm = new Vue({
    el: modalHolder,
    render: h => {
      return h(SignatureLinkModal);
    },
  });

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
  OC.Plugins.register('OCA.Files.FileList', fileActionsPlugin);
}

addCustomFileActions();
