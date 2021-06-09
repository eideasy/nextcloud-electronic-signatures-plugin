<script>
import MethodButtonBase from './MethodButtonBase';
export default {
  name: 'SmartCardButton',
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
      <img class="image" :src="generateIconPath('id-card.svg')" alt="Smart Card logo">
    </template>
    <template v-slot:slot2>
      {{ $t($globalConfig.appId, 'Smart Card') }}
    </template>
  </MethodButtonBase>
</template>

<style scoped>
.image {
  display: block;
  width: 50px;
  height: auto;
  margin-right: 10px;
}
</style>
