<script>
export default {
  name: 'SettingsTextInput',
  props: {
    placeholder: {
      type: String,
      default: '',
    },
    value: {
      type: String,
      default: '',
    },
    onButtonClick: {
      type: Function,
      default: () => {},
    },
  },
  computed: {
    inputVal: {
      get() {
        return this.value;
      },
      set(val) {
        this.$emit('input', val);
      },
    },
  },
};
</script>

<template>
  <label>
    <span class="settingsLabel">
      <slot name="label" />
    </span>
    <input
        v-model="inputVal"
        :placeholder="placeholder"
        class="input"
        type="text">
    <button
        class="button"
        @click="onButtonClick">
      <slot name="buttonText">{{ $t($globalConfig.appId, 'Save') }}</slot>
    </button>
  </label>
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
</style>
