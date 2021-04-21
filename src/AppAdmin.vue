<script>
export default {
  name: 'AppAdmin',
  data() {
    return {
      clientId: this.$parent.clientId,
      secret: this.$parent.secret,
    };
  },
};
</script>

<template>
  <div>
    <div class="section">

      <h2>{{ t('electronicsignatures', 'Electronic signatures settings') }}</h2>

      <div>
        <div class="icon-loading-small" />
        <span class="msg success">Saved</span>
        <span class="msg error">The given legal notice address is not a valid URL</span>
      </div>

      <div>
        <label>
          <span class="settingsLabel">{{ t('electronicsignatures', 'Client ID') }}</span>
          <input
              class="input"
              type="text"
              v-model="clientId">
        </label>
      </div>
      <div>
        <label>
          <span class="settingsLabel">{{ t('electronicsignatures', 'Secret') }}</span>
          <input
              class="input"
              type="text"
              v-model="secret">
        </label>
      </div>

    </div>
  </div>
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

  .msg.error {
    border-radius: var(--border-radius);
  }
</style>
