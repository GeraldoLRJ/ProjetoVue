<template>
  <nav>
    <h2 style="margin:0 0 8px 0;">Menu</h2>
    <ul class="menu">
      <li><router-link to="/tasks" exact>Tarefas</router-link></li>
      <li v-if="canUsers"><router-link to="/users">Usuários</router-link></li>
      <li v-if="isMaster"><router-link to="/companies">Empresas</router-link></li>
    </ul>
    <div class="auth">
      <div v-if="user">
        <small>Logado como: {{ user.name }} ({{ user.role }})</small>
        <button @click="logout">Sair</button>
      </div>
      <div v-else>
        <router-link to="/login">Login</router-link>
      </div>
    </div>
  </nav>
</template>

<script>
import store from '../store';
export default {
  computed: {
    user() { return store.state.user; },
    isMaster() { return this.user && this.user.role === 'master'; },
    canUsers() { return this.user && (this.user.role === 'admin' || this.user.role === 'master'); }
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
.auth { margin-top: 24px; }
.auth button { margin-top: 8px; }
</style>
