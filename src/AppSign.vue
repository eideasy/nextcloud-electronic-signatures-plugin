<script>
import MethodButton from './MethodButton';
import { imagePath } from '@nextcloud/router';
export default {
  name: 'AppSign',
  components: {
    MethodButton,
  },
  methods: {
    generateIconPath(file) {
      return imagePath(this.$globalConfig.appId, 'methods/' + file);
    },
  },
  computed: {
    signingMethods() {
      return this.$globalConfig.methods.filter(method => method.enabled);
    }
  }
};
</script>

<template>
  <div class="methodsGrid">
    <div
        v-for="method in signingMethods"
        :key="method.name"
        class="methodsGridUnit">
      <MethodButton>
        <img
            v-if="method.icon"
            class="methodIcon"
            :src="generateIconPath(method.icon)"
            :alt="method.name">
        <span v-else>
          {{ method.text }}
        </span>
      </MethodButton>
    </div>
  </div>
</template>

<style scoped>
.methodIcon {
  display: block;
  width: auto;
  height: 34px;
  margin: 0 auto;
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
