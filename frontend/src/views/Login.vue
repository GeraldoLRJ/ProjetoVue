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
.card { background:#fff; padding:36px; border-radius:10px; max-width:720px; width:100%; box-shadow: 0 10px 30px rgba(15,23,42,0.08); }
h1 { margin: 0 0 12px 0; font-size: 28px; }
label { display:block; margin-top:12px; font-weight:600; }
input { width:100%; padding:14px; margin:6px 0 12px 0; box-sizing: border-box; font-size:16px; border-radius:6px; border:1px solid #e5e7eb; }
button { padding:10px 16px; font-size:16px; border-radius:6px; background:#2563eb; color:#fff; border:none; cursor:pointer; }
button[disabled] { opacity:0.6; cursor:not-allowed; }
.error { color:#b91c1c; margin-top:8px; }

@media (max-width: 640px) {
  .card { padding:20px; max-width: 92%; }
  h1 { font-size:22px; }
  input { padding:12px; }
}
</style>
