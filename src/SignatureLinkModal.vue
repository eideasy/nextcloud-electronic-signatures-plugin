<script>
import axios from 'axios';
import Modal from '@nextcloud/vue/dist/Components/Modal';

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
      modal: true,
      signingLink: '',
    };
  },
  watch: {
    modal: {
      handler(newValue, oldValue) {
        const _self = this;
        if (!newValue) {
          return;
        }
        // TODO: make this url dynamic
        axios.get('/nextcloud/index.php/apps/electronicsignatures/get_sign_link?path=' + this.filename, {
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
      },
      immediate: true,
    },
  },
  methods: {
    setSigningLink(signingLink) {
      console.log(signingLink);
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
    <modal v-if="modal" @close="closeModal">
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
