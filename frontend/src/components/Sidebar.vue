<template>
  <nav>
    <h2 style="margin:0 0 8px 0;">Menu</h2>
    <ul class="menu">
      <li v-if="canAll"><router-link to="/tasks" exact>Tarefas</router-link></li>
      <li v-if="canUsers"><router-link to="/users">Usuários</router-link></li>
      <li v-if="isMaster"><router-link to="/companies">Empresas</router-link></li>
    </ul>
  </nav>
</template>

<script>
import store from '../store';
export default {
  computed: {
    user() { return store.state.user; },
    isMaster() { return this.user && this.user.role === 'master'; },
    canUsers() { return this.user && (this.user.role === 'admin' || this.user.role === 'master'); },
    canAll() { return this.user && (this.user.role === 'admin' || this.user.role === 'master' || this.user.role === 'user'); }
  },
  methods: {
    logout() {
      store.clearAuth();
      this.$router.push('/login');
    }
  }
};
</script>

<style>
.menu { list-style: none; padding: 0; }
.menu li { margin: 8px 0; }
.menu a { color: #fff; text-decoration: none; }
.menu a.router-link-exact-active { font-weight: bold; }
</style>
