<template>
  <div v-if="visible" class="modal-backdrop" @keydown.esc="onClose" tabindex="-1">
    <div class="modal-wrapper" role="dialog" aria-modal="true">
      <div class="modal-card">
        <header class="modal-header">
          <slot name="header"></slot>
          <button class="modal-close" @click="onClose">×</button>
        </header>
        <section class="modal-body">
          <slot></slot>
        </section>
        <footer class="modal-footer">
          <slot name="footer"></slot>
        </footer>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Modal',
  props: {
    visible: { type: Boolean, default: false }
  },
  methods: {
    onClose() {
      this.$emit('close');
    }
  },
  mounted() {
    this.$el && this.$el.focus && this.$el.focus();
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-wrapper { max-width: 900px; width: 95%; }
.modal-card { background: #fff; border-radius: 8px; padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee; }
.modal-close { background:transparent; border:0; font-size:20px; cursor:pointer; }
.modal-body { padding:16px; }
.modal-footer { padding:12px 16px; border-top:1px solid #eee; text-align:right; }
</style>
