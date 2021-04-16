import Vue from 'vue';
import SignatureLinkModal from './SignatureLinkModal';
import EventBus from './EventBus';

function addCustomFileActions() {

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

  console.log(vm.$refs);

  const fileActionsPlugin = {
    attach(fileList) {
      console.log('fileActionsPlugin');
      fileList.fileActions.registerAction({
        mime: 'all',
        name: 'Sign',
        displayName: 'Get signing url',
        order: -100,
        permissions: 0,
        iconClass: 'icon-shared',
        actionHandler(filename, context) {
          EventBus.$emit('GET_SIGNING_LINK_CLICK', { filename });
        },
      });
      console.log(fileList);
    },
  };
  OC.Plugins.register('OCA.Files.FileList', fileActionsPlugin);
}

addCustomFileActions();
