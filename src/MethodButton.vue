<script>
export default {
  name: 'MethodButton',
  methods: {
    handleClick(e) {
      if (!this.disabled && this.onClick) {
        this.onClick(e);
      }
    }
  }
};
</script>

<template>
  <button
      @click="handleClick"
      class="button">
    <slot />
  </button>
</template>

<style scoped>
.button {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid #cdcdcd;
  padding: 8px 16px;
  width: 100%;
  text-align: center;
  font-size: 18px;
  color: #2c3e50;
  transition: .1s;
  min-height: 52px;
  background-color: #fff;
  box-shadow: 0 2px 7px 0 rgba(0, 0, 0, 0.06);
  margin: 0;
}
</style>
