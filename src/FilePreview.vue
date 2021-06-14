<script>
export default {
  name: 'FilePreview',
  props: {
    mimeType: {
      type: String,
      default: '',
    },
    fileContent: {
      type: String,
      default: '',
    },
    fileName: {
      type: String,
      default: '',
    },
  },
  computed: {
    filePreviewData() {
      return `data:${this.mimeType};base64,${this.fileContent}`;
    },
  },
};
</script>

<template>
  <div class="filePreview">
    This be file preview
    <object
      :type="mimeType"
      :data="filePreviewData"
      width="1000"
      height="500">
      <p>This type of file preview cannot be shown</p>
    </object>
  </div>
</template>

<style scoped>
.filePreview {
  width: 100%;
  height: 300px;
  background-color: gray;
}
</style>
