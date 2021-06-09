<script>
import SmartCardButton from './methodButtons/SmartCardButton';
import SmartIdButton from './methodButtons/SmartIdButton';
import { imagePath } from '@nextcloud/router';
import FilePreview from './FilePreview';
export default {
  name: 'AppSign',
  components: {
    FilePreview,
    SmartCardButton,
    SmartIdButton,
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
      console.log(method);
    },
  },
};
</script>

<template>
  <div class="container">
    <h2 class="signingTitle">
        Sa allkirjastad allolevat dokumenti<br>
      <small>Palun kontrolli enne selle dokumendi sisu üle</small>
    </h2>
    <div class="methodsGrid">
      <div class="methodsGridUnit">
        <SmartCardButton
            :on-click="() => selectMethod('smartCard')"
            :generate-icon-path="generateIconPath" />
      </div>
      <div class="methodsGridUnit">
        <SmartIdButton
            :on-click="() => selectMethod('smartId')"
            :generate-icon-path="generateIconPath" />
      </div>
      <div class="preview">
        <FilePreview />
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

.methodsGrid {
  display: flex;
  flex-wrap: wrap;
}

.methodsGridUnit {
  width: 25%;
  padding: 7px;
}
</style>
