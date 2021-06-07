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
      clientId: '',
      secret: '',
      isLoading: false,
      successMessage: null,
      errorMessage: null,
      clientIdPlaceholder: this.$parent.clientId,
      secretPlaceholder: this.$parent.secret,
      allowSimpleSignatures: this.$parent.enableOtp,
    };
  },
  computed: {
    fetchSignedFileUrl() {
      return window.location.origin + this.generateNextcloudUrl('/index.php/apps/electronicsignatures/fetch_signed_file');
    },
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
    generateNextcloudUrl(url) {
      return generateUrl(url);
    },
    saveSetting(setting) {
      const _self = this;
      // do not save empty values
      let shouldSave = true;
      Object.keys(setting).forEach(key => {
        if (!setting[key]) {
          shouldSave = false;
        }
      });
      if (!shouldSave) {
        return;
      }

      _self.setIsLoading(true);
      _self.setSuccessMessage(null);
      _self.setErrorMessage(null);
      axios({
        method: 'post',
        url: this.generateNextcloudUrl('/apps/electronicsignatures/update_settings'),
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
      <p class="settings-hint settingsHint">
        {{ $t('electronicsignatures', 'You can find your credentials under the "My Webpages" section on your dashboard at: ') }}
        <a
            class="link"
            target="_blank"
            href="https://id.eideasy.com/">
          id.eideasy.com
        </a>
      </p>

      <p class="settings-hint settingsHint">
        {{ $t('electronicsignatures', 'Ensure that in your eID Easy panel under "My Websites", you have added the following notification hook to your website: ') + fetchSignedFileUrl }}
      </p>

      <p class="settings-hint settingsHint">
        {{ $t('electronicsignatures', 'NB! Please note that whenever you are generating a signature link, the contents of your document are securely sent to the eID Easy server for signing.') }}
      </p>

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
              :placeholder="clientIdPlaceholder"
              class="input"
              type="text">
          <button
              class="button"
              @click="debouncedSaveSetting({client_id: clientId})">
            {{ $t('electronicsignatures', 'Save') }}
          </button>
        </label>
      </div>
      <div>
        <label>
          <span class="settingsLabel">{{ $t('electronicsignatures', 'Secret') }}</span>
          <input
              v-model="secret"
              :placeholder="secretPlaceholder"
              class="input"
              type="text">
          <button
              class="button"
              @click="debouncedSaveSetting({secret})">
            {{ $t('electronicsignatures', 'Save') }}
          </button>
        </label>
      </div>
      <div class="checkboxWrap">
        <input
            v-model="allowSimpleSignatures"
            id="allowOnlyEmail"
            type="checkbox"
            class="checkbox"
            true-value="1"
            false-value="0"
            @change="debouncedSaveSetting({enable_otp: !!allowSimpleSignatures})">
        <label for="allowOnlyEmail">
          {{ $t($globalConfig.appId, 'Allow simple signatures') }}
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

  .link {
    color: #0082c9;
  }

  .settingsHint {
    opacity: 0.9;
  }

  .checkboxWrap {
    margin-top: 20px;
  }

  .settingsHint + .settingsHint {
    margin-top: 0;
  }
</style>
