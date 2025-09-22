<template>
  <div>
    <h1>Empresas</h1>
    <p v-if="!isMaster" class="warn">Acesso restrito a master.</p>
    <div v-else>
      <div v-if="loading">Carregando...</div>
      <ul v-else class="list">
        <li v-for="c in companies" :key="c.id">
          <strong>#{{ c.id }} {{ c.name }}</strong>
          <small> | slug: {{ c.slug || '-' }}</small>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import api from '../api';
import store from '../store';

export default {
  data: () => ({ companies: [], loading: true }),
  computed: {
    isMaster() { return store.state.user && store.state.user.role === 'master'; }
  },
  async created() {
    if (!this.isMaster) return;
    await this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const { data } = await api.get('/companies');
        this.companies = Array.isArray(data.data) ? data.data : data;
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
