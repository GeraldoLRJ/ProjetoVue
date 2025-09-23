
<template>
  <div>
    <HeaderBar />
    <div :class="['layout', { 'layout--login': isLogin }]">
      <aside v-if="!isLogin" class="sidebar">
        <Sidebar />
      </aside>
      <main :class="['content', { 'content--center': isLogin }]">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import Sidebar from './components/Sidebar.vue';
import HeaderBar from './components/Header.vue';

export default {
  components: { Sidebar, HeaderBar },
  computed: {
    isLogin() { return this.$route && this.$route.path === '/login'; }
  }
};
</script>

<style>
.layout { display: flex; min-height: calc(100vh - 56px); font-family: Arial, sans-serif; }
.layout--login { align-items: center; justify-content: center; }
.sidebar { width: 220px; background: #1f2937; color: #fff; padding: 16px; }
.content { flex: 1; padding: 16px; background: #f3f4f6; }
.content--center { display: flex; align-items: center; justify-content: center; }
</style>
