<script>
import '@eid-easy/eideasy-signing-widget';
import { imagePath } from '@nextcloud/router';
import FilePreview from './FilePreview';

const METHODS = {
  smartCard: 'smartCard',
  smartId: 'smartId',
};

export default {
  name: 'AppSign',
  components: {
    FilePreview,
  },
  data() {
    return {
      METHODS,
      selectedMethod: null,
    };
  },
  computed: {
    signingMethods() {
      return this.$globalConfig.methods.filter(method => method.enabled);
    },
  },
  methods: {
    generateIconPath(file) {
      return imagePath(this.$globalConfig.appId, 'methods/' + file);
    },
    selectMethod(method) {
      this.selectedMethod = method;
    },
  },
};
</script>

<template>
  <div class="container">
    <h2 class="signingTitle">
      {{ $t($globalConfig.appId, 'You are signing the document below') }}<br>
      <small>{{ $t($globalConfig.appId, 'Please review its contents before signing.') }}</small>
    </h2>
    <div class="widgetHolder">
      <eideasy-signing-widget
        id-host="https://id.eideasy.com"
        country-code="EE"
        language="et"
        :sandbox="true" />
    </div>
    <div class="preview">
      <FilePreview />
    </div>
  </div>
</template>

<style scoped>
.container {
  margin-left: auto;
  margin-right: auto;
  padding-left: 15px;
  padding-right: 15px;
  width: 100%;
  max-width: 1170px;
}

.widgetHolder {
  width: 400px;
}
</style>
