<script>
import '@eid-easy/eideasy-widget';
import { imagePath } from '@nextcloud/router';
import FilePreview from './FilePreview';
import generateAppUrl from './generateAppUrl';

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
      docId: this.$parent.docId,
      mimeType: this.$parent.mimeType,
      fileContent: this.$parent.fileContent,
      fileName: this.$parent.fileName,
      clientId: this.$parent.clientId,
      fileUrl: this.$parent.fileUrl,
      signedContainerUrl: null,
      enableSandbox: !!this.$parent.enableSandbox,
      apiEndpoints: {
        base: () => this.$parent.apiUrl,
      },
      enabledMethods: {
        signature: ['id-signature', 'be-id-signature', 'lt-id-signature', 'lv-id-signature', 'fi-id-signature', 'pt-id-signature'],
      },
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
    handleSigningSuccess() {
      this.signedContainerUrl = generateAppUrl('/download-signed-file/' + this.docId);
    },
  },
};
</script>

<template>
  <div class="Layout">
    <div class="Layout_main">
      <div class="Layout_mainContainer">
        <div class="Layout_section">
          <h2 class="h2">
            1. {{ $t($globalConfig.appId, 'You are signing the following documents:') }}
          </h2>

          <FilePreview
              :file-content="fileContent"
              :file-name="fileName"
              :file-url="fileUrl"
              :mime-type="mimeType" />
        </div>
      </div>
    </div>
    <div class="Layout_actions">
      <h2 class="h2">
        2. {{ $t($globalConfig.appId, 'Sign:') }}
      </h2>
      <div
          v-if="signedContainerUrl">
        <span class="alert alert-success">
          {{ $t($globalConfig.appId, 'File successfully signed!') }}
        </span>
        <a
            v-if="signedContainerUrl"
            :href="signedContainerUrl"
            :download="fileName"
            target="_blank"
            class="button">
        <span class="filePreview_actionIcon">
          <span class="icon icon-download-white" />
        </span>
          {{ $t($globalConfig.appId, 'Download signed document') }}
        </a>
      </div>
      <div
          v-else
          class="widgetHolder">
        <eideasy-widget
            country-code="EE"
            language="en"
            :sandbox="enableSandbox"
            :doc-id="docId"
            :client-id="clientId"
            :enabled-methods.prop="enabledMethods"
            :api-endpoints.prop="apiEndpoints"
            :on-success.prop="handleSigningSuccess" />
      </div>
    </div>
  </div>
</template>

<style>
@media (min-width: 768px) {
  #body-public #content {
    min-height: 100%;
  }
}
</style>

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
  width: 100%;
}

.Layout_actions {
  margin-top: 40px;
}

.Layout_section + .Layout_section {
  margin-top: 20px;
}

.Layout_mainContainer {
  width: 100%;
}

.Layout {
  width: 100%;
  max-width: 480px;
  margin: 50px auto 0 auto;
  padding: 0 20px;
}

.h2 {
  margin-bottom: 30px;
}

.alert {
  display: block;
  position: relative;
  padding: .75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: .25rem;
}

.alert-success {
  color: #155724;
  background-color: #d4edda;
  border-color: #c3e6cb;
}

@media (min-width: 768px) {
  .Layout {
    display: flex;
    max-width: 100%;
    margin: 0;
    padding: 0;
  }

  .Layout_mainContainer {
    max-width: 920px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .Layout_main {
    flex-grow: 1;
    padding-top: 40px;
    padding-bottom: 40px;
    height: 100%;
    overflow: auto;
  }

  .Layout_actions {
    border-left: 1px solid var(--color-border);
    width: 400px;
    padding: 40px 20px;
    margin-top: 0;
    height: 100%;
    overflow: auto;
  }
}

@media (min-width: 992px) {
  .Layout_actions {
    width: 500px;
  }
}

</style>
