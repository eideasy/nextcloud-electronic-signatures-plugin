import config from './config';
import Vue from 'vue';
import SignatureLinkModal from './SignatureLinkModal';
import EventBus from './EventBus';
import { translate, translatePlural } from '@nextcloud/l10n';
import { registerFileAction, FileAction } from '@nextcloud/files';

Vue.prototype.$t = translate;
Vue.prototype.$n = translatePlural;
Vue.prototype.$globalConfig = config;

function addCustomFileActions() {
  const { t } = window;

  const fileAction = new FileAction({
    id: 'eideasy-sign-file-action',
    displayName() {
      return t(config.appId, 'Signing');
    },
    iconSvgInline() {
      return '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 512.008 512.008" style="enable-background:new 0 0 512.008 512.008;" xml:space="preserve"> <g> <g> <path d="M451.291,436.781H85.96c-23.704,0-42.98-19.277-42.98-42.98c0-23.703,19.277-42.98,42.98-42.98h42.98 c11.862,0,21.49-9.628,21.49-21.49s-9.628-21.49-21.49-21.49H85.96c-47.407,0-85.96,38.553-85.96,85.96s38.553,85.96,85.96,85.96 h365.331c11.862,0,21.49-9.628,21.49-21.49S463.153,436.781,451.291,436.781z"/> </g> </g> <g> <g> <path d="M487.996,56.278c-32.063-32.02-84.241-32.063-116.369,0l-32.88,32.88c-0.408,0.344-0.903,0.473-1.289,0.86 s-0.516,0.881-0.86,1.289L226.333,201.572c-21.232,21.275-32.923,49.556-32.923,79.621v48.138c0,11.862,9.628,21.49,21.49,21.49 h48.138c30.065,0,58.345-11.691,79.664-32.944l145.273-145.273C520.016,140.541,520.016,88.341,487.996,56.278z M312.336,287.468 c-13.173,13.13-30.688,20.373-49.298,20.373H236.39v-26.648c0-18.61,7.242-36.125,20.351-49.277l96.125-96.125l55.573,55.573 L312.336,287.468z M457.587,142.217l-18.761,18.761l-55.573-55.573l18.739-18.739c15.322-15.301,40.272-15.279,55.573,0 C472.867,101.988,472.867,126.916,457.587,142.217z"/> </g> </g> </svg>';
    },
    exec(file) {
      console.log(file);
      EventBus.$emit('SIGNATURES_CLICK', {
        filename: file.basename,
      });
    },
    order: 1,
  });
  registerFileAction(fileAction);


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
}

addCustomFileActions();
