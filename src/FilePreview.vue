<script>
import { imagePath } from '@nextcloud/router';
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
    fileUrl: {
      type: String,
      default: '',
    },
  },
  computed: {
    filePreviewData() {
      return this.fileUrl;
    },
    previewType() {
      const { mimeTypeIncludes } = this;
      let type = 'download';
      if (mimeTypeIncludes(['image/jpeg', 'image/jpg', 'image/svg+xml', 'image/png'])) {
        type = 'img';
      } else if (mimeTypeIncludes(['application/pdf'])) {
        type = 'object';
      }

      return type;
    },
  },
  methods: {
    mimeTypeIncludes(mimeTypeArray) {
      return mimeTypeArray.includes(this.mimeType);
    },
    generateIconPath(file) {
      return imagePath(this.$globalConfig.appId, file);
    },
  },
};
</script>

<template>
  <div class="filePreview">
    <div class="filePreview_header">
      <a
        :href="filePreviewData"
        :download="fileName"
        target="_blank"
        class="filePreview_action">
        <span class="filePreview_actionIcon">
          <span class="icon icon-download-white"></span>
        </span>
        {{ fileName }}
      </a>
    </div>
    <img
      v-if="previewType === 'img'"
      class="image"
      :src="filePreviewData">
    <object
      v-else-if="previewType === 'object'"
      :type="mimeType"
      :data="filePreviewData"
      width="1000"
      height="500" />
  </div>
</template>

<style scoped>
.filePreview {
  width: 100%;
}

.filePreview_header {
  padding: 12px 16px;
  border-bottom: 1px solid #D9D9D9;
  border-top: 1px solid #D9D9D9;
}

.image {
  display: block;
  width: 100%;
  height: auto;
}

.icon {
  display: inline-block;
  vertical-align: middle;
  background-size: 16px 16px;
}

.filePreview_action {
  display: block;
  padding-left: 40px;
  padding-top: 2px;
  padding-bottom: 5px;
  position: relative;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}

.filePreview_actionIcon {
  display: inline-block;
  vertical-align: middle;
  background-color: #343131;
  width: 31px;
  height: 31px;
  padding-top: 2px;
  padding-left: 7px;
  border-radius: 50%;
  margin-right: 6px;
  position: absolute;
  left: 0;
  top: 0;
}

</style>
