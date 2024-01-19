<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
export default {
  name: 'SettingsSection',
  components: {
    NcSettingsSection,
  },
  props: {
    title: {
      type: String,
      default: '',
    },
  },
};
</script>

<template>
  <NcSettingsSection
      :title="title">
    <div class="settings-hint settingsHint">
      <slot name="settingsHint" />
    </div>
    <slot />
  </NcSettingsSection>
</template>

<style scoped>
.settingsHint {
  margin-top: 0;
  opacity: 0.9;
}

.settingsHint p {
  margin-bottom: 10px;
}
</style>
