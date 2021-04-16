<script>
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
      signingLink: '',
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
            console.log(error);
          })
          .then(function() {
            // always executed
          });
    });
  },
  methods: {
    setSigningLink(signingLink) {
      this.signingLink = signingLink;
    },
    showModal() {
      this.modal = true;
    },
    closeModal() {
      this.modal = false;
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
            v-if="signingLink"
            class="signingLinkHolder">
          {{ signingLink }}
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
    width: 100%;
    max-width: 700px;
    min-width: 200px;
    padding: 2rem;
    box-sizing: border-box;
  }

  .modal__content * {
    box-sizing: border-box;
  }

  .loader {
    margin: 0 auto;
  }

  .signingLinkHolder {
    user-select: text;
    cursor: text;
  }
</style>
