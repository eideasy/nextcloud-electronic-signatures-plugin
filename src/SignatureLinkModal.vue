<script>
import axios from 'axios';
import Modal from '@nextcloud/vue/dist/Components/Modal';
import EventBus from './EventBus';
import { generateUrl } from '@nextcloud/router';
import queryString from 'query-string';
import OC from './OC';
import fetchAdminSettings from './fetchAdminSettings';
import SigningQueue from './SigningQueue';
import SignatureQueue from './SignatureQueue';
import SigningStatus from './SigningStatus';

const EMAIL_FIELD_TEMPLATE = {
  type: 'email',
  value: '',
};

const getFileExtension = function getFileExtension(filename) {
  return filename.split('.').pop();
};

export default {
  name: 'SignatureLinkModal',
  components: {
    Modal,
    SignatureQueue,
  },
  data() {
    return {
      SigningQueue: new SigningQueue(),
      modal: false,
      errorMessage: null,
      successMessage: null,
      isLoading: false,
      isLoadingSettings: false,
      adminSettings: null,
      signeeFormSchema: [
        {
          ...EMAIL_FIELD_TEMPLATE,
        },
      ],
      email: '',
      filename: '',
      isLoadingQueue: false,
      signatureQueue: [],
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
        const { client_id_provided, secret_provided } = this.adminSettings;
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
    EventBus.$on('SIGNATURES_CLICK', function(payload) {
      _self.filename = payload.filename;
      _self.showModal();
    });
  },
  methods: {
    removeSignerFromQueue(index) {
      const emails = this.signatureQueue.reduce((acc, item, itemIndex) => {
        if (itemIndex !== index
            && item.status !== SigningStatus.EMAIL_SENT
            && item.status !== SigningStatus.DOCUMENT_SIGNED) {
          acc.push(item.email);
        }
        return acc;
      }, []);
      this.setSigningQueue(emails);
    },
    addEmailRow() {
      this.signeeFormSchema.push({
        ...EMAIL_FIELD_TEMPLATE,
      });
    },
    removeEmailRow(index) {
      if (this.signeeFormSchema.length <= 1) {
        return;
      }
      this.signeeFormSchema.splice(index, 1);
    },
    generateNextcloudUrl(url) {
      return generateUrl(url);
    },
    showModal() {
      if (!this.adminSettings) {
        this.getAdminSettings();
      }
      this.getSigningQueue();
      this.setErrorMessage(null);
      this.setSuccessMessage(null);
      this.modal = true;
    },
    closeModal() {
      this.modal = false;
    },
    setErrorMessage(message) {
      if (message === null) {
        this.errorMessage = null;
      } else if (!message) {
        this.errorMessage = this.$t(this.$globalConfig.appId, 'Something went wrong. Make sure that the electronic signatures app settings are correct.');
      } else {
        this.errorMessage = message;
      }
    },
    setSuccessMessage(message) {
      this.successMessage = message;
    },
    getFilePath() {
      const parsed = queryString.parse(window.location.search);
      if (parsed.dir === '/') {
        return this.filename;
      } else {
        return parsed.dir + '/' + this.filename;
      }
    },
    setAdminSettings(settings) {
      this.adminSettings = settings;
    },
    getAdminSettings() {
      const _self = this;
      _self.isLoadingSettings = true;
      fetchAdminSettings()
          .then(function(response) {
            _self.setAdminSettings(response.data);
          })
          .catch(function(error) {
            console.error(error);
            _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Failed to fetch electronicsignatures settings'));
          })
          .then(function() {
            _self.isLoadingSettings = false;
          });
    },
    getSigningQueue() {
      const _self = this;
      _self.isLoadingQueue = true;
      this.SigningQueue.getQueue(this.getFilePath())
          .then(function(response) {
            _self.signatureQueue = response.data.signersQueue || [];
          })
          .catch(function(error) {
            console.error(error);
            _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Failed to fetch the signing queue'));
          })
          .then(function() {
            _self.isLoadingQueue = false;
          });
    },
    setSigningQueue(emails) {
      const _self = this;
      _self.isLoading = true;
      this.SigningQueue.setQueue(this.getFilePath(), emails)
          .then(function(response) {
            _self.signatureQueue = response.data.signersQueue || [];
          })
          .catch(function(error) {
            console.error(error);
            _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Failed to modify the signing queue'));
          })
          .then(function() {
            _self.isLoading = false;
          });
    },
    getModifiableSigners() {
      return this.signatureQueue.filter(item => item.status === SigningStatus.EMAIL_PENDING);
    },
    addToSigningQueue(emails) {
      const queueEmails = this.getModifiableSigners().map(item => item.email);
      this.setSigningQueue([...queueEmails, ...emails]);
    },
    clearSigneeForm() {
      this.signeeFormSchema = [
        {
          ...EMAIL_FIELD_TEMPLATE,
        },
      ];
    },
    onSubmit() {
      const _self = this;
      this.isLoading = true;
      this.setErrorMessage(null);
      this.setSuccessMessage(null);
      const emails = this.signeeFormSchema.map(field => field.value);

      if (this.signatureQueue.length) {
        this.addToSigningQueue(emails);
        this.clearSigneeForm();
        return;
      }

      axios({
        method: 'post',
        url: generateUrl('/apps/electronicsignatures/create_signing_queue'),
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
        data: {
          path: this.getFilePath(),
          emails,
        },
      })
          .then(function() {
            _self.setSuccessMessage(_self.$t(_self.$globalConfig.appId, 'Email successfully sent!'));
            // reset the email fields after successful submit
            _self.clearSigneeForm();
          })
          .catch(function(error) {
            _self.setErrorMessage(error.response && error.response.data && error.response.data.message);
          })
          .then(function() {
            _self.isLoading = false;
          });
    },
  },
};
</script>

<template>
  <div>
    <modal
        v-if="modal"
        @close="closeModal">
      <div
          class="modal__content">
        <div
            v-if="isLoading || isLoadingSettings || isLoadingQueue"
            class="loader">
          <div class="icon-loading spinner" />
        </div>
        <div v-if="!isLoadingSettings" class="contentWrap">
          <div
              v-if="signatureQueue.length"
              class="siqQueue">
            <h3>
              {{ $t($globalConfig.appId, 'Signature queue') }}
            </h3>
            <SignatureQueue
                :signature-queue="signatureQueue"
                :on-remove-item-click="removeSignerFromQueue" />
          </div>
          <h3 v-else>
            {{ $t($globalConfig.appId, 'Request a signature via email') }}
          </h3>

          <div v-if="errorMessage">
            <span class="alert alert-danger">{{ errorMessage }}</span>
          </div>

          <div v-if="successMessage">
            <span class="alert alert-success">{{ successMessage }}</span>
          </div>

          <div
              v-if="missingAdminSettings.length">
            <div class="alert alert-danger">
              {{ $t($globalConfig.appId, 'The following credentials are missing: ') }}
              <ul>
                <li v-for="credential in missingAdminSettings" :key="credential">
                  <b>{{ credential }}</b>
                </li>
              </ul>
              {{
                $t($globalConfig.appId, 'Please make sure that you have filled in the eID Easy credential fields on the "Electronic Signatures" app settings page.')
              }}
            </div>
          </div>
          <div v-else>
            <form
                action=""
                @submit.prevent="onSubmit">
              <label
                  class="label"
                  for="signingLinkEmail">
                {{ $t($globalConfig.appId, 'Add signers') }}
              </label>

              <div
                  v-for="(field, index) in signeeFormSchema"
                  :key="index"
                  class="fieldRow">
                <input
                    id="signingLinkEmail"
                    v-model="field.value"
                    type="email"
                    class="input"
                    placeholder="Email"
                    required
                    aria-label="email">
                <button
                    v-if="index > 0"
                    @click.prevent="removeEmailRow(index)">
                  {{ $t($globalConfig.appId, 'Remove') }}
                </button>
              </div>
              <button
                  v-if="!signatureQueue.length"
                  @click.prevent="addEmailRow">
                + {{ $t($globalConfig.appId, 'Add') }}
              </button>
              <div
                  v-if="!signatureQueue.length"
                  style="margin-bottom: 20px; margin-top: 20px">
                {{
                  $t($globalConfig.appId, 'After you click "Request signature" a copy of this document will be created. An email with an invitation to sign this newly created copy will be sent to the first signer. After the first signer has signed the document, next singer in the list will get the invitation email and so on until everyone has signed.')
                }}
              </div>
              <div
                  v-if="adminSettings && adminSettings.enable_otp && currentFileExtension !== 'pdf'"
                  class="basicNote">
                {{
                  $t($globalConfig.appId, 'Note: You have enabled simple signatures in the settings. Simple signatures can only be added to pdf files, but this file is not a pdf file. This means that the signer can not sign this file using simple signatures. However, they can still use all the other available signing methods.')
                }}
              </div>

              <div />

              <button
                  v-if="signatureQueue.length"
                  type="submit"
                  class="submitButton">
                {{ $t($globalConfig.appId, 'Add to queue') }}
              </button>
              <button
                  v-else
                  type="submit"
                  class="submitButton">
                {{ $t($globalConfig.appId, 'Request signature') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </modal>
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

.loader {
  margin: 0 auto;
  position: absolute;
  z-index: 500;
  width: 100%;
  height: 100%;
  left: 0;
  top: 0;
  background-color: rgba(255, 255, 255, 0.9);
}

.contentWrap {
  position: relative;
  padding-bottom: 30px;
}

.error {
  color: #842029;
}

h3 {
  font-size: 20px;
  margin-bottom: 20px;
  font-weight: bold;
}

a {
  color: var(--color-primary-element);
}

.fieldRow {
  display: flex;
  margin-bottom: 1rem;
}

.radioRow + .radioRow {
  margin-top: 2px;
}

.label {
  display: block;
  margin-bottom: 10px;
}

.input {
  width: 100%;
  max-width: 300px;
}

.spinner {
  top: 50%;
}

.alert {
  display: block;
  position: relative;
  padding: .75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: .25rem;
}

.alert-success {
  color: #155724;
  background-color: #d4edda;
  border-color: #c3e6cb;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

.alert-warning {
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeeba;
}

.basicNote {
  margin-bottom: 16px;
  font-style: italic;
}

.submitButton {
  background-color: #0082c9;
  color: #fff;
}

@media (min-width: 600px) {
  .modal__content {
    padding: 2rem;
  }
}

.queueTitle {
  font-size: 18px;
  margin-bottom: 10px;
}

.siqQueue {
  margin-bottom: 20px;
}
</style>
