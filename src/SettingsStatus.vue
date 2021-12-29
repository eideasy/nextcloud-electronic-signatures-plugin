<script>
export default {
  name: 'SettingsStatus',
  props: {
    isLoading: {
      type: Boolean,
      default: false,
    },
    errorMessage: {
      type: String,
      default: '',
    },
    successMessage: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      successTimeout: undefined,
    };
  },
  computed: {
    showSuccessMessage() {
      // show the message while the timeout is running
      return !!this.successTimeout;
    },
  },
  watch: {
    successMessage() {
      this.startSuccessTimeout();
    },
  },
  beforeDestroy() {
    this.clearSuccessTimeout();
  },
  methods: {
    startSuccessTimeout() {
      const _self = this;
      if (this.successTimeout) {
        this.clearSuccessTimeout();
      }
      this.successTimeout = window.setTimeout(() => {
        _self.clearSuccessTimeout();
      }, 3000);
    },
    clearSuccessTimeout() {
      window.clearTimeout(this.successTimeout);
      this.successTimeout = undefined;
    },
  },
};
</script>

<template>
  <div class="wrap">
    <div
        v-if="isLoading"
        class="loaderWrap">
      <div class="icon-loading-small loaderIcon" />
      <span class="statusText">{{ $t('electronicsignatures', 'Saving...') }}</span>
    </div>
    <div v-else>
      <transition name="fade">
        <span
            v-if="showSuccessMessage"
            class="msg success">
          {{ successMessage }}
        </span>
      </transition>
      <span
          v-if="errorMessage"
          class="msg error">
        {{ errorMessage }}
      </span>
    </div>
  </div>
</template>

<style scoped>
  .wrap {
    text-align: left;
    min-height: 24px;
    margin: 3px;
  }

  .wrap:after {
    content: '';
    display: table;
    clear: both;
  }

  .loaderWrap {
    display: flex;
    align-items: center;
  }

  .statusText {
    margin-left: 10px;
  }

  .msg.error {
    border-radius: var(--border-radius);
  }

  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 1s;
  }

  .fade-enter,
  .fade-leave-to {
    opacity: 0;
  }
</style>
