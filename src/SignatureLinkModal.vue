<script>
import axios from 'axios';
import Modal from '@nextcloud/vue/dist/Components/Modal';
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch';
import EventBus from './EventBus';
import { generateUrl } from '@nextcloud/router';
import queryString from 'query-string';
import OC from './OC';

const CONTAINER_TYPE = {
  asice: 'asice',
  pdf: 'pdf',
};

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
    CheckboxRadioSwitch,
  },
  data() {
    return {
      modal: false,
      errorMessage: null,
      successMessage: null,
      isLoading: false,
      adminSettings: null,
      containerType: '',
      signeeFormSchema: [
        {
          ...EMAIL_FIELD_TEMPLATE,
        }
      ],
      containerTypeOptions: [
        {
          value: CONTAINER_TYPE.asice,
          text: '.asice',
        },
        {
          value: CONTAINER_TYPE.pdf,
          text: '.pdf',
        },
      ],
      email: '',
      filename: '',
    };
  },
  computed: {
    fileExtension() {
      return getFileExtension(this.filename);
    },
    containerTypeModel: {
      get() {
        if (getFileExtension(this.filename) !== CONTAINER_TYPE.pdf) {
          return CONTAINER_TYPE.asice;
        }

        if (this.containerType) {
          return this.containerType;
        } else {
          return CONTAINER_TYPE.pdf;
        }
      },
      set(val) {
        this.containerType = val;
      },
    },
    shouldShowContainerSelect() {
      return this.fileExtension === CONTAINER_TYPE.pdf;
    },
    isSupportedFileType() {
      return this.fileExtension !== 'asice';
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
      _self.showModal();
      _self.filename = payload.filename;
    });
  },
  methods: {
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
        this.fetchAdminSettings();
      }
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
    fetchAdminSettings() {
      const _self = this;
      _self.isLoading = true;
      axios({
        method: 'get',
        url: this.generateNextcloudUrl('/apps/electronicsignatures/settings'),
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
      })
          .then(function(response) {
            _self.setAdminSettings(response.data);
          })
          .catch(function(error) {
            console.error(error);
            _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Failed to fetch electronicsignatures settings'));
          })
          .then(function() {
            _self.isLoading = false;
          });
    },
    onSubmit() {
      const _self = this;
      this.isLoading = true;
      this.setErrorMessage(null);
      this.setSuccessMessage(null);
      axios({
        method: 'post',
        url: generateUrl('/apps/electronicsignatures/send_sign_link_by_email'),
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
        data: {
          path: this.getFilePath(),
          email: this.email,
          container_type: this.containerTypeModel,
        },
      })
          .then(function() {
            _self.setSuccessMessage(_self.$t(_self.$globalConfig.appId, 'Email successfully sent!'));
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
            v-if="isLoading"
            class="loader">
          <div class="icon-loading spinner" />
        </div>
        <div class="contentWrap">
          <h3>
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
          <div
              v-else-if="!isSupportedFileType">
            <span class="alert alert-warning">
              {{ $t($globalConfig.appId, 'Signing existing .asice containers is currently not supported.') }}
            </span>
          </div>
          <form
              v-else
              action=""
              @submit.prevent="onSubmit">
            <label
                class="label"
                for="signingLinkEmail">
              {{ $t($globalConfig.appId, 'Email addresses') }}
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
            <button @click.prevent="addEmailRow">
              + {{ $t($globalConfig.appId, 'Add') }}
            </button>
            <div style="margin-bottom: 20px; margin-top: 20px">
              {{
                $t($globalConfig.appId, 'An email with a link to the signing page will be sent to the entered emails.')
              }}
            </div>
            <div
                v-if="adminSettings && adminSettings.enable_otp && containerTypeModel !== 'pdf' && isSupportedFileType"
                class="basicNote">
              {{
                $t($globalConfig.appId, 'Note: You have enabled simple signatures in the settings. Simple signatures can only be added to pdf files, but this file is not a pdf file. This means that the signer can not sign this file using simple signatures. However, they can still use all the other available signing methods.')
              }}
            </div>
            <div v-if="shouldShowContainerSelect">
              <label
                  class="label">
                <b>{{ $t($globalConfig.appId, 'Output file type') }}</b>
              </label>
              <div class="radioRow">
                <CheckboxRadioSwitch
                    :checked.sync="containerTypeModel"
                    value="pdf"
                    name="container_type_radio"
                    type="radio">
                  {{ $t($globalConfig.appId, '.pdf') }}
                </CheckboxRadioSwitch>
              </div>
              <div class="radioRow">
                <CheckboxRadioSwitch
                    :checked.sync="containerTypeModel"
                    value="asice"
                    name="container_type_radio"
                    type="radio">
                  {{ $t($globalConfig.appId, '.asice') }}
                </CheckboxRadioSwitch>
                <a href="#" class="infoTip">
                  <span class="icon icon-details" />
                </a>
                <div>
                  {{
                    $t($globalConfig.appId, '.asice files can be opened and verified with the DigiDoc4 application that is available at:')
                  }}
                  <ul>
                    <li>
                      {{ $t($globalConfig.appId, 'Windows - ') }}
                      <a
                          href="https://www.microsoft.com/en-us/p/digidoc4-client/9pfpfk4dj1s6"
                          target="_blank">
                        https://www.microsoft.com/en-us/p/digidoc4-client/9pfpfk4dj1s6
                      </a>
                    </li>
                    <li>
                      {{ $t($globalConfig.appId, 'macOS - ') }}
                      <a
                          href="https://apps.apple.com/us/app/digidoc4-client/id1370791134"
                          target="_blank">
                        https://apps.apple.com/us/app/digidoc4-client/id1370791134
                      </a>
                    </li>
                    <li>
                      {{ $t($globalConfig.appId, 'or alternatively - ') }}
                      <a
                          href="https://www.id.ee/en/article/install-id-software/"
                          target="_blank">
                        https://www.id.ee/en/article/install-id-software
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <button
                type="submit"
                class="submitButton">
              {{ $t($globalConfig.appId, 'Request signature') }}
            </button>
          </form>
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
</style>
