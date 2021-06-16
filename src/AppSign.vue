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
      docId: this.$parent.docId,
      mimeType: this.$parent.mimeType,
      fileContent: this.$parent.fileContent,
      fileName: this.$parent.fileName,
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
  <div class="Layout">
    <div class="Layout_main">
      <div class="Layout_mainContainer">
        <div class="Layout_section">
          <h2 class="h2">
            1. {{ $t($globalConfig.appId, 'Review the file contents before signing:') }}
          </h2>
          <div> {{ docId }} </div>
          <FilePreview
              :file-content="fileContent"
              :file-name="fileName"
              :mime-type="mimeType" />
        </div>
      </div>
    </div>
    <div class="Layout_actions">
      <h2 class="h2">
        2. {{ $t($globalConfig.appId, 'Sign:') }}
      </h2>
      <div class="widgetHolder">
        <eideasy-signing-widget
            :doc-id="docId"
            client-id="r1NXWSK6LZzEcTShjfLmu4kbqE3zi0oo"
            id-host="https://id.eideasy.com"
            country-code="EE"
            language="en"
            :on-success.prop="() => console.log('test')"
            :sandbox="true" />
      </div>
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

@media (min-width: 768px) {
  .Layout {
    display: flex;
    max-width: 100%;
    margin: 0;
    padding: 0;
    height: 100vh;
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
