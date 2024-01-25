<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import EventBus from './EventBus';
import RemoteQueue from './RemoteQueue.vue';
import fetchAdminSettings from './fetchAdminSettings';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
import RequestError from './RequestError.js';
import Error from './Error.vue';

const getFileExtension = function getFileExtension(filename) {
  return filename.split('.').pop();
};

export default {
  name: 'SignatureLinkModal',
  components: {
    Error,
    NcLoadingIcon,
    NcModal,
    RemoteQueue,
    NcNoteCard,
  },
  data() {
    return {
      modal: false,
      error: null,
      isLoading: false,
      adminSettings: null,
      filePath: '',
      fileMime: '',
    };
  },
  computed: {
    currentFileExtension() {
      return getFileExtension(this.filename);
    },
    missingAdminSettings() {
      const missingSettings = [];
      /* eslint-disable camelcase */
      if (this.adminSettings) {
        const {client_id_provided, secret_provided} = this.adminSettings;
        if (!client_id_provided) {
          missingSettings.push('client_id');
        }
        if (!secret_provided) {
          missingSettings.push('secret');
        }
      }
      /* eslint-enable camelcase */
      return missingSettings;
    },
  },
  mounted() {
    const _self = this;
    EventBus.$on('SIGNATURES_CLICK', function (payload) {
      _self.filePath = payload.path;
      _self.fileMime = payload.mime;
      _self.showModal();
    });
  },
  methods: {
    showModal() {
      this.getAdminSettings();
      this.setError(null);
      this.modal = true;
    },
    closeModal() {
      this.modal = false;
    },
    setAdminSettings(settings) {
      this.adminSettings = settings;
    },
    getAdminSettings() {
      const _self = this;
      _self.isLoading = true;
      fetchAdminSettings()
          .then(function (response) {
            _self.setAdminSettings(response.data);
          })
          .catch(function (error) {
            console.error(error);
            console.log(error.response);
            console.log(error.config);
            _self.setError(new RequestError(_self.$t(_self.$globalConfig.appId, 'Failed to fetch the eID Easy electronic signatures app settings'), error));
          })
          .then(function () {
            _self.isLoading = false;
          });
    },
    setError(error) {
      this.error = error;
    },
  },
};
</script>

<template>
  <div>
    <nc-modal
        v-if="modal"
        @close="closeModal"
    >
      <div class="modal__content">
        <NcLoadingIcon
            v-if="isLoading"
            :size="64"
            appearance="dark"
            name="Loading modal content"
        />
        <Error v-else-if="error" :error="error" />
        <NcNoteCard v-else-if="adminSettings.signing_mode !== 'remote'" type="error">
          <p>
            {{
              $t($globalConfig.appId, 'The currently configured signing mode is deprecated in Nextcloud v28+. See the following guide on how to enable the new and recommended mode: ')
            }}
          </p>
        </NcNoteCard>
        <NcNoteCard v-else-if="missingAdminSettings.length" type="error" heading="Error">
          <p>
            {{ $t($globalConfig.appId, 'The following credentials are missing: ') }}
          </p>
          <ul>
            <li v-for="credential in missingAdminSettings" :key="credential">
              <b>{{ credential }}</b>
            </li>
          </ul>
          <p>
            {{
              $t($globalConfig.appId, 'Please make sure that you have filled in the eID Easy credential fields on the "Electronic Signatures" app settings page.')
            }}
          </p>
        </NcNoteCard>
        <NcNoteCard v-else-if="fileMime !== 'application/pdf' && adminSettings.container_type !== 'asice'" type="warning">
          <p>
            {{
              $t($globalConfig.appId, 'You are trying to sign a file that is not a PDF.')
            }}
            {{
              $t($globalConfig.appId, 'For this you first need to change the output file type to .asice in the Electronic Sigantures admin settings.')
            }}
            {{
              $t($globalConfig.appId, 'See the following guide for more information:')
            }}
          </p>
        </NcNoteCard>
        <RemoteQueue v-else :file-path="filePath" />
      </div>
    </nc-modal>
  </div>
</template>

<style>
.modal-wrapper .modal-container.modal-container.modal-container {
  overflow: auto;
}
</style>

<style scoped>
.modal__content {
  width: 68vw;
  max-width: 600px;
  padding: 2rem 1rem;
  box-sizing: border-box;
  position: relative;
}

.modal__content * {
  box-sizing: border-box;
}

a {
  color: var(--color-primary-element);
}

@media (min-width: 600px) {
  .modal__content {
    padding: 2rem;
  }
}
</style>
