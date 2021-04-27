<script>
import copy from 'copy-to-clipboard';
import axios from 'axios';
import Modal from '@nextcloud/vue/dist/Components/Modal';
import EventBus from './EventBus';
import { generateUrl } from '@nextcloud/router';
import OC from './OC';

export default {
  name: 'SignatureLinkModal',
  components: {
    Modal,
  },
  props: {
    filename: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      modal: false,
      signingUrl: '',
      errorMessage: null,
      simpleSignaturesOnly: false,
    };
  },
  mounted() {
    const _self = this;
    EventBus.$on('GET_SIGNING_LINK_CLICK', function(payload) {
      _self.setSigningLink('');
      _self.showModal();
      axios.get(generateUrl('/apps/electronicsignatures/get_sign_link?path=' + payload.filename), {
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
      })
          .then(function(response) {
            if (response.data.sign_link) {
              _self.setSigningLink(response.data.sign_link);
            } else {
              _self.setErrorMessage(response.data && response.data.message);
            }
          })
          .catch(function(error) {
            _self.setErrorMessage(error.response && error.response.data && error.response.data.message);
          });
    });
  },
  methods: {
    setSigningLink(signingUrl) {
      this.signingUrl = signingUrl;
    },
    showModal() {
      this.modal = true;
    },
    closeModal() {
      this.modal = false;
      this.setErrorMessage(null);
    },
    clearSelection(event) {
      event.target.setSelectionRange(0);
    },
    selectAll(event) {
      event.target.setSelectionRange(0, event.target.value.length);
    },
    copyToClipboard() {
      copy(this.signingUrl);
    },
    setErrorMessage(message) {
      if (!message) {
        this.$t(this.$globalConfig.appId, 'Something went wrong. Make sure that the electronic signatures app settings are correct.');
      } else {
        this.errorMessage = message;
      }
    },
    onSubmit() {
      console.log('handle submit');
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
        <div v-if="errorMessage">
          <span class="msg error">{{ errorMessage }}</span>
        </div>
        <div
            v-else-if="signingUrl"
            class="signingUrlHolder">
          <div v-if="this.$globalConfig.features.signingLinkByEmail">
            <h3>
              {{ $t($globalConfig.appId, 'Send the signing link by email') }}
            </h3>
            <form
                action=""
                @submit.prevent="onSubmit">
              <label
                  class="label"
                  for="signingLinkEmail">
                {{ $t($globalConfig.appId, 'Enter the email address of the person who should sign this document.') }}
              </label>
              <div class="fieldRow">
                <input
                    id="signingLinkEmail"
                    type="email"
                    class="input"
                    placeholder="Email"
                    aria-label="email">
              </div>
              <input
                  type="checkbox"
                  name="allowOnlyEmail"
                  id="allowOnlyEmail"
                  class="checkbox"
                  value="1">
              <label for="allowOnlyEmail">
                {{ $t($globalConfig.appId, 'Allow only email based signatures') }}
              </label>
              <div class="note">
                {{ $t($globalConfig.appId, `If checked, the signer will be forced to use their email and will not be able to choose any other signing method.
                Email based signatures do not qualify as Advanced Electronic Signature (AdES) or Qualified Electronic Signature (QES).`) }}

              </div>
              <button
                  class="submitButton"
                  type="submit">
                {{ $t($globalConfig.appId, 'Send') }}
              </button>
            </form>
          </div>
          <div v-else>
            <div class="copyField">
              <input
                  type="text"
                  aria-label="Signing link URL"
                  readonly="readonly"
                  class="staticInput"
                  :value="signingUrl"
                  @click="selectAll">
              <button @click="copyToClipboard">
                {{ $t($globalConfig.appId, 'Copy') }}
              </button>
            </div>
          </div>
        </div>
        <div v-else
          class="loader">
          <div class="icon-loading" />
        </div>
      </div>
    </modal>
  </div>
</template>

<style scoped>
  .modal__content {
    width: 68vw;
    max-width: 600px;
    padding: 2rem 1rem;
    box-sizing: border-box;
  }

  .modal__content * {
    box-sizing: border-box;
  }

  .loader {
    margin: 0 auto;
  }

  .copyField {
    display: flex;
  }

  .staticInput {
    width: 100%;
  }

  .signingUrlHolder {
    user-select: text;
    cursor: text;
  }

  .error {
    color: #842029;
  }

  h3 {
    font-size: 20px;
    margin-bottom: 20px;
    font-weight: bold;
  }

  .fieldRow {
    display: flex;
    margin-bottom: 1rem;
  }

  .label {
    display: block;
    margin-bottom: 10px;
  }

  .input {
    width: 100%;
    max-width: 300px;
  }

  .note {
    margin-top: 10px;
    font-size: 14px;
    line-height: 1.5;
  }

  .submitButton {
    margin-top: 30px;
  }

  @media (min-width: 600px) {
    .modal__content {
      padding: 2rem;
    }
  }
</style>
