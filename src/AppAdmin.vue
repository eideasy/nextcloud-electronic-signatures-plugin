<script>
import debounce from 'lodash.debounce';
import axios from 'axios';
import { generateUrl } from '@nextcloud/router';
import SettingsStatus from './SettingsStatus';
const { OC } = window;

export default {
  name: 'AppAdmin',
  components: {
    SettingsStatus,
  },
  data() {
    return {
      clientId: this.$parent.clientId,
      secret: this.$parent.secret,
      isLoading: false,
      successMessage: null,
      errorMessage: null,
    };
  },
  created() {
    this.debouncedSaveSetting = debounce(this.saveSetting, 300);
  },
  methods: {
    setIsLoading(isLoading) {
      this.isLoading = isLoading;
    },
    setSuccessMessage(message) {
      this.successMessage = message;
    },
    setErrorMessage(message) {
      this.errorMessage = message;
    },
    saveSetting(setting) {
      const _self = this;
      _self.setIsLoading(true);
      _self.setSuccessMessage(null);
      _self.setErrorMessage(null);
      axios({
        method: 'post',
        url: generateUrl('/apps/electronicsignatures/update_credentials'),
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
        data: setting,
      })
          .then(function(response) {
            _self.setSuccessMessage(_self.$t('electronicsignatures', 'Saved'));
          })
          .catch(function() {
            _self.setErrorMessage(_self.$t('electronicsignatures', 'Something went wrong, settings were not saved.'));
          })
          .then(function() {
            _self.setIsLoading(false);
          });
    },
  },
};
</script>

<template>
  <div>
    <div class="section">
      <h2>{{ $t('electronicsignatures', 'Electronic signatures settings') }}</h2>

      <div class="statusWrap">
        <SettingsStatus
          :is-loading="isLoading"
          :error-message="errorMessage"
          :success-message="successMessage" />
      </div>

      <div>
        <label>
          <span class="settingsLabel">{{ $t('electronicsignatures', 'Client ID') }}</span>
          <input
              v-model="clientId"
              class="input"
              type="text">
          <button
              class="button"
              @click="debouncedSaveSetting({clientId})">
            {{ $t('electronicsignatures', 'Save') }}
          </button>
        </label>
      </div>
      <div>
        <label>
          <span class="settingsLabel">{{ $t('electronicsignatures', 'Save') }}</span>
          <input
              v-model="secret"
              class="input"
              type="text">
          <button
              class="button"
              @click="debouncedSaveSetting({secret})">
            {{ $t('electronicsignatures', 'Save') }}
          </button>
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

  .statusWrap {
    margin: 10px;
  }
</style>
