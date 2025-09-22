<template>
  <div>
    <h1>Tarefas</h1>
    <div v-if="loading">Carregando...</div>
    <div v-else>
      <ul class="list">
        <li v-for="t in tasks" :key="t.id">
          <strong>#{{ t.id }} {{ t.title }}</strong>
          <small> | status: {{ t.status }} | prioridade: {{ t.priority }} | vencimento: {{ t.due_date || '-' }}</small>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import api from '../api';

export default {
  data: () => ({ tasks: [], loading: true }),
  async created() {
    await this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const { data } = await api.get('/tasks');
        this.tasks = Array.isArray(data.data) ? data.data : data;
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style>
.list { padding:0; list-style:none; }
.list li { background:#fff; margin:8px 0; padding:8px; border-radius:6px; }
</style>
