<script>
export default {
  name: 'MethodButtonBase',
  props: {
    onClick: {
      type: Function,
      default: () => {},
    },
  },
  methods: {
    handleClick(e) {
      if (!this.disabled && this.onClick) {
        this.onClick(e);
      }
    },
  },
};
</script>

<template>
  <a
    href="#"
    class="button"
    role="button"
    @click.prevent="handleClick">
    <div>
      <slot name="slot1" />
    </div>
    <div>
      <slot name="slot2" />
    </div>
  </a>
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
  font-size: 16px;
  color: #2c3e50;
  transition: .1s;
  min-height: 52px;
  background-color: #fff;
  box-shadow: 0 2px 7px 0 rgba(0, 0, 0, 0.06);
  margin: 0;
  font-weight: 500;
}
</style>
