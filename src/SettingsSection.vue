<script>
import SettingsSectionNC from '@nextcloud/vue/dist/Components/SettingsSection';
export default {
  name: 'SettingsSection',
  components: {
    SettingsSectionNC,
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
  <SettingsSectionNC
      :title="title">
    <div class="settings-hint settingsHint">
      <slot name="settingsHint" />
    </div>
    <slot />
  </SettingsSectionNC>
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
