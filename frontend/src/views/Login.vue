<template>
  <div class="card">
    <h1>Login</h1>
    <form @submit.prevent="submit">
      <label>E-mail</label>
      <input v-model="email" type="email" required />
      <label>Senha</label>
      <input v-model="password" type="password" required />
      <button :disabled="loading">Entrar</button>
      <p v-if="error" class="error">{{ error }}</p>
    </form>
  </div>
</template>

<script>
import api from '../api';
import store from '../store';

export default {
  data: () => ({ email: 'master@local.test', password: 'master123', loading: false, error: null }),
  methods: {
    async submit() {
      this.loading = true; this.error = null;
      try {
        const { data } = await api.post('/login', { email: this.email, password: this.password });
        store.setToken(data.access_token);
        await store.fetchMe();
        this.$router.replace('/tasks');
      } catch (e) {
        this.error = (e.response && e.response.data && (e.response.data.error || e.response.data.message)) || 'Erro de login';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style>
.card { background:#fff; padding:16px; border-radius:6px; max-width:360px; }
label { display:block; margin-top:8px; }
input { width:100%; padding:8px; margin:4px 0 8px 0; }
button { padding:8px 12px; }
.error { color:#b91c1c; }
</style>
