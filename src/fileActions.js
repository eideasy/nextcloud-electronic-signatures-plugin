import Vue from 'vue';
import SignatureLinkModal from './SignatureLinkModal';

function addCustomFileActions() {

  const modalHolderId = 'esigModalHolder';
  const modalHolder = document.createElement('div');
  modalHolder.id = modalHolderId;

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
          document.body.append(modalHolder);
          // TODO: try to use a single vue instance
          const modalInstance = new Vue({
            el: modalHolder,
            render: h => {
              context = {
                props: { filename },
              };
              return h(SignatureLinkModal, context);
            },
          });
        },
      });
      console.log(fileList);
    },
  };
  OC.Plugins.register('OCA.Files.FileList', fileActionsPlugin);
}

addCustomFileActions();
