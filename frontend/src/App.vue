<template>
  <div class="layout">
    <aside class="sidebar">
      <Sidebar />
    </aside>
    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<script>
import Sidebar from './components/Sidebar.vue';
export default {
  components: { Sidebar }
};
</script>

<style>
.layout { display: flex; min-height: 100vh; font-family: Arial, sans-serif; }
.sidebar { width: 220px; background: #1f2937; color: #fff; padding: 16px; }
.content { flex: 1; padding: 16px; background: #f3f4f6; }
</style>
