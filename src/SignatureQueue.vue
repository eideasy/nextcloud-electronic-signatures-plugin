<script>
import SigningStatus from './SigningStatus';

export default {
  name: 'SignatureQueue',
  props: {
    signatureQueue: {
      type: Array,
      default: () => [],
    },
    onRemoveItemClick: {
      type: Function,
      default: () => null,
    },
  },
  methods: {
    emailSent(status) {
      return status === SigningStatus.EMAIL_SENT;
    },
    statusText(status) {
      if (status === SigningStatus.EMAIL_SENT) {
        return this.$t(this.$globalConfig.appId, 'Email sent');
      } else if (status === SigningStatus.EMAIL_PENDING) {
        return this.$t(this.$globalConfig.appId, 'Waiting');
      }
    },
  },
};
</script>

<template>
  <div>
    <div
        v-for="(item, index) in signatureQueue"
        :key="item.email"
        class="actionItem">
      <div class="actionItem_main">
        <div class="actionItem_title">
          {{ item.email }}
        </div>
        <div class="actionItem_meta">
          {{ statusText(item.status) }}
        </div>
      </div>
      <div class="actionItem_actions">
        <button
            v-if="!emailSent(item.status)"
            @click.prevent="onRemoveItemClick(index)">
          {{ $t($globalConfig.appId, 'Remove') }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.actionItem {
  display: flex;
  align-items: center;
  border-bottom: 1px solid #cdcdcd;
  padding: 10px 0;
}

.actionItem_title {
  font-weight: 500;
  font-size: 16px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.actionItem_main {
  padding-right: 20px;
  width: 100%;
  min-width: 0;
}

.actionItem_actions {
  margin-left: auto;
  margin-right: 0;
}
</style>
