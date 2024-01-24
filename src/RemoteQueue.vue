<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import queryString from 'query-string';
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
import RemoteSigningQueue from './RemoteSigningQueue';
import Error from './Error.vue';
import RequestError from './RequestError';

export default {
  name: 'RemoteQueue',
  components: {
    NcModal,
    NcNoteCard,
    NcLoadingIcon,
    Error,
  },
  props: {
    filePath: String,
  },
  data() {
    return {
      RemoteSigningQueue: new RemoteSigningQueue(),
      isLoading: false,
      error: null,
    };
  },
  methods: {
    startRemoteMultisigning() {
      const _self = this;
      this.isLoading = true;
      this.RemoteSigningQueue.create(this.filePath)
          .then(function (response) {
            if (response.data && response.data.management_page_url) {
              window.location.href = response.data.management_page_url;
            } else {
              _self.setError(new RequestError(_self.$t(_self.$globalConfig.appId, 'Response data does not contain management_page_url'), {}));
            }
          })
          .catch(function (error) {
            console.error(error);
            console.log(error.response);
            console.log(error.config);
            _self.setError(new RequestError(_self.$t(_self.$globalConfig.appId, 'Failed to start remote multisigning'), error));
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
  <div class="contentWrap">
    <h3>
      {{ $t($globalConfig.appId, 'Request signatures via eID Easy') }}
    </h3>

    <NcLoadingIcon
        v-if="isLoading"
        :size="64"
        appearance="dark"
        name="Loading"
    />
    <div v-else>
      <Error v-if="error" :error="error" />

      <button @click.prevent="startRemoteMultisigning">
        {{ $t($globalConfig.appId, 'Request signatures') }}
      </button>
    </div>
  </div>
</template>

<style scoped>
h3 {
  font-size: 20px;
  margin-bottom: 20px;
  font-weight: bold;
}

.contentWrap {
  position: relative;
  padding-bottom: 30px;
}
</style>
