<script>
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js';

export default {
  name: 'Error',
  components: {
    NcNoteCard,
  },
  props: {
    error: {
      type: Error,
      default: null,
    },
  },
};
</script>

<template>
  <NcNoteCard type="error">
    <p>
      {{ error.message }}
    </p>
    <p v-if="error.cause">
      {{ error.cause.message }}
    </p>

    <pre v-if="error.debugInfoPrettyString" class="debugInfo">{{ error.debugInfoPrettyString }}</pre>
  </NcNoteCard>
</template>

<style scoped>
p + p {
  margin-top: 20px;
}

.debugInfo {
  margin-top: 10px;
  background-color: #fff5f5;
  padding: 9px 20px;
  border-radius: 10px;
}
</style>
