<script>
import axios from 'axios';
import Modal from '@nextcloud/vue/dist/Components/Modal';
import EventBus from './EventBus';

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
      console.log(payload);
      axios.get('/nextcloud/index.php/apps/electronicsignatures/get_sign_link?path=' + payload.filename, {
        responseType: 'json',
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
        ref="modal"
        v-if="modal"
        @close="closeModal">
      <div
          class="modal__content">
        <div
            v-if="signingLink"
            class="signingLinkHolder">
          {{ signingLink }}
        </div>
        <div v-else>Loading...</div>
      </div>
    </modal>
  </div>
</template>

<style scoped>
  .modal__content {
    width: 100%;
    max-width: 700px;
    padding: 2rem;
  }

  .signingLinkHolder {
    user-select: text;
    cursor: text;
  }
</style>
