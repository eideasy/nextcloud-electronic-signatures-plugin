<script>
import MethodButtonBase from './MethodButtonBase';
export default {
  name: 'SmartIdButton',
  components: {
    MethodButtonBase,
  },
  props: {
    onClick: {
      type: Function,
      default: () => {},
    },
    generateIconPath: {
      type: Function,
      default: () => {},
    },
  },
};
</script>

<template>
  <MethodButtonBase :on-click="onClick">
    <template v-slot:slot1>
      <img class="image" :src="generateIconPath('smart-id-inline-logo.svg')" alt="Smart Id logo">
    </template>
  </MethodButtonBase>
</template>

<style scoped>
.image {
  display: block;
  width: 110px;
  height: auto;
}
</style>
