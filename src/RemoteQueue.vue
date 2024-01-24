<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import queryString from 'query-string';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
import RemoteSigningQueue from './RemoteSigningQueue';

export default {
  name: 'RemoteQueue',
  components: {
    NcModal,
    NcNoteCard,
    NcLoadingIcon,
  },
  data() {
    return {
      RemoteSigningQueue: new RemoteSigningQueue(),
      isLoading: false,
      errorMessage: null,
    };
  },
  methods: {
    getFilePath() {
      const parsed = queryString.parse(window.location.search);
      if (parsed.dir === '/') {
        return this.filename;
      } else {
        return parsed.dir + '/' + this.filename;
      }
    },
    startRemoteMultisigning() {
      const _self = this;
      this.isLoading = true;
      this.RemoteSigningQueue.create(this.getFilePath())
          .then(function (response) {
            if (response.data && response.data.management_page_url) {
              window.location.href = response.data.management_page_url;
            } else {
              _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Response data does not contain management_page_url'));
            }
          })
          .catch(function (error) {
            console.error(error);
            _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Failed to start remote multisigning'));
          })
          .then(function () {
            _self.isLoading = false;
          });
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
  },
};
</script>

<template>
  <div class="contentWrap">
    <h3>
      {{ $t($globalConfig.appId, 'Request signatures via eID Easy') }}
    </h3>

    <NcLoadingIcon
        v-if="isLoading"
        :size="64"
        appearance="dark"
        name="Loading modal content"
    />
    <div v-else>
      <button @click.prevent="startRemoteMultisigning">
        {{ $t($globalConfig.appId, 'Request signatures') }}
      </button>

      <NcNoteCard v-if="errorMessage" type="error" heading="Error">
        <p>{{ errorMessage }}<</p>
      </NcNoteCard>

      <NcNoteCard v-if="successMessage" type="success">
        <p>{{ successMessage }}<</p>
      </NcNoteCard>
    </div>
  </div>
</template>

<style scoped>
.contentWrap {
  position: relative;
  padding-bottom: 30px;
}
</style>
