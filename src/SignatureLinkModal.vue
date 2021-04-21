<script>
import copy from 'copy-to-clipboard';
import axios from 'axios';
import Modal from '@nextcloud/vue/dist/Components/Modal';
import EventBus from './EventBus';
const { OC } = window;

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
    };
  },
  mounted() {
    const _self = this;
    EventBus.$on('GET_SIGNING_LINK_CLICK', function(payload) {
      _self.setSigningLink('');
      _self.showModal();
      axios.get(OC.generateUrl('/apps/electronicsignatures/get_sign_link?path=' + payload.filename), {
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
      })
          .then(function(response) {
            _self.setSigningLink(response.data.sign_link);
          })
          .catch(function(error) {
            // TODO: handle errors
            console.log(error);
          })
          .then(function() {
            // TODO: hide loader
            // always executed
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
    },
    clearSelection(event) {
      event.target.setSelectionRange(0);
    },
    selectAll(event) {
      event.target.setSelectionRange(0, event.target.value.length);
    },
    copyToClipboard() {
      copy(this.signingUrl);
    }
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
            v-if="signingUrl"
            class="signingUrlHolder">
          <div class="copyField">
            <input
                type="text"
                aria-label="Signing link URL"
                readonly="readonly"
                class="staticInput"
                @click="selectAll"
                :value="signingUrl">
            <button @click="copyToClipboard">{{ $t($globalConfig.appId, 'Copy') }}</button>
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

  @media (min-width: 600px) {
    .modal__content {
      padding: 2rem;
    }
  }
</style>
