<script>
import debounce from 'lodash.debounce';
import axios from 'axios';
const { OC } = window;

export default {
  name: 'AppAdmin',
  data() {
    return {
      clientId: this.$parent.clientId,
      secret: this.$parent.secret,
    };
  },
  watch: {
    clientId(newValue) {
      this.debouncedSaveSetting({
        clientId: newValue,
      });
    },
    secret(newValue) {
      this.debouncedSaveSetting({
        secret: newValue,
      });
    }
  },
  created() {
    this.debouncedSaveSetting = debounce(this.saveSetting, 300);
  },
  methods: {
    saveSetting(setting) {
      const _self = this;
      axios({
        method: 'post',
        url: OC.generateUrl('/apps/electronicsignatures/update_credentials'),
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
        data: setting,
      })
          .then(function(response) {
            console.log('------------------saved-------------------');
          })
          .catch(function() {
            console.log('------------------failed-------------------');
          });
    },
  }
};
</script>

<template>
  <div>
    <div class="section">

      <h2>{{ $t('electronicsignatures', 'Electronic signatures settings') }}</h2>

      <div>
        <div class="icon-loading-small" />
        <span class="msg success">Saved</span>
        <span class="msg error">The given legal notice address is not a valid URL</span>
      </div>

      <div>
        <label>
          <span class="settingsLabel">{{ $t('electronicsignatures', 'Client ID') }}</span>
          <input
              v-model="clientId"
              class="input"
              type="text">
        </label>
      </div>
      <div>
        <label>
          <span class="settingsLabel">{{ $t('electronicsignatures', 'Secret') }}</span>
          <input
              class="input"
              type="text"
              v-model="secret">
        </label>
      </div>

    </div>
  </div>
</template>

<style scoped>
  .settingsLabel {
    min-width: 110px;
    display: inline-block;
    padding: 8px 0;
    vertical-align: top;
  }

  .input {
    width: 100%;
    max-width: 270px;
  }

  .msg.error {
    border-radius: var(--border-radius);
  }
</style>
