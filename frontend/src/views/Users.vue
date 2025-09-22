<template>
  <div>
    <h1>Usuários</h1>
    <p v-if="!allowed" class="warn">Acesso restrito a admin/master.</p>
    <div v-else>
      <div v-if="loading">Carregando...</div>
      <ul v-else class="list">
        <li v-for="u in users" :key="u.id">
          <strong>#{{ u.id }} {{ u.name }}</strong>
          <small> | {{ u.email }} | role: {{ u.role }} | tenant: {{ u.tenant_id }}</small>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import api from '../api';
import store from '../store';

export default {
  data: () => ({ users: [], loading: true }),
  computed: {
    allowed() {
      const user = store.state.user;
      return user && (user.role === 'admin' || user.role === 'master');
    }
  },
  async created() {
    if (!this.allowed) return;
    await this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const { data } = await api.get('/users');
        this.users = Array.isArray(data.data) ? data.data : data;
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style>
.warn { color:#92400e; background:#fef3c7; padding:8px; border-radius:6px; }
.list { padding:0; list-style:none; }
.list li { background:#fff; margin:8px 0; padding:8px; border-radius:6px; }
</style>
