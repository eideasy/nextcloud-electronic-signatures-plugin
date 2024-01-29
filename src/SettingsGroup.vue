<script>
import { generateUrl } from '@nextcloud/router';
import axios from 'axios';
import SettingsStatus from './SettingsStatus';
import debounce from 'lodash.debounce';
export default {
  name: 'SettingsGroup',
  components: {
    SettingsStatus,
  },
  data() {
    return {
      isLoading: false,
      successMessage: null,
      errorMessage: null,
    };
  },
  props: {
    allowSavingEmptySettings: false,
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

      if (!this.allowSavingEmptySettings) {
        Object.keys(setting).forEach(key => {
          if (setting[key] === undefined || setting[key] === '') {
            shouldSave = false;
          }
        });
      }

      if (!shouldSave) {
        return;
      }

      _self.setIsLoading(true);
      _self.setSuccessMessage(null);
      _self.setErrorMessage(null);
      axios({
        method: 'post',
        url: this.generateNextcloudUrl('/apps/electronicsignatures/settings'),
        responseType: 'json',
        headers: {
          requesttoken: OC.requestToken,
        },
        data: setting,
      })
          .then(function(response) {
            _self.setSuccessMessage(_self.$t(_self.$globalConfig.appId, 'Saved'));
          })
          .catch(function() {
            _self.setErrorMessage(_self.$t(_self.$globalConfig.appId, 'Something went wrong, settings were not saved.'));
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
    <div class="statusWrap">
      <SettingsStatus
          :is-loading="isLoading"
          :error-message="errorMessage"
          :success-message="successMessage" />
    </div>
    <slot :save-setting="debouncedSaveSetting" />
  </div>
</template>
