<template>
  <div>
    <h1>Tarefas</h1>
    <div class="actions">
      <button @click="startCreate">Nova tarefa</button>
    </div>

    <div v-if="loading">Carregando...</div>
    <div v-else>
      <ul class="list">
        <li v-for="t in tasks" :key="t.id">
          <div class="row">
            <div>
              <strong>#{{ t.id }} {{ t.title }}</strong>
              <small> | status: {{ t.status }} | prioridade: {{ t.priority }} | vencimento: {{ t.due_date || '-' }}</small>
              <div class="desc" v-if="t.description">{{ t.description }}</div>
            </div>
            <div class="row-actions">
              <button @click="startEdit(t)">Editar</button>
              <button @click="confirmDelete(t)">Apagar</button>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="showForm" class="form-card">
        <h3>{{ editMode ? 'Editar tarefa' : 'Criar tarefa' }}</h3>
        <form @submit.prevent="submit">
          <label>Título</label>
          <input v-model="form.title" required />
          <label>Descrição</label>
          <textarea v-model="form.description"></textarea>
          <label>Status</label>
          <select v-model="form.status">
            <option value="todo">todo</option>
            <option value="in_progress">in_progress</option>
            <option value="done">done</option>
          </select>
          <label>Prioridade</label>
          <select v-model="form.priority">
            <option value="low">low</option>
            <option value="medium">medium</option>
            <option value="high">high</option>
          </select>
          <label>Vencimento</label>
          <input v-model="form.due_date" type="date" />

          <div class="form-actions">
            <button type="submit" :disabled="saving">{{ saving ? 'Salvando...' : 'Salvar' }}</button>
            <button type="button" @click="cancel">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../api';

export default {
  data: () => ({ tasks: [], loading: true, showForm: false, editMode: false, form: {}, saving: false }),
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
    },
    startCreate() {
      this.form = { title: '', description: '', status: 'todo', priority: 'medium', due_date: null };
      this.editMode = false; this.showForm = true; this.saving = false;
    },
    startEdit(task) {
      this.form = { ...task };
      this.editMode = true; this.showForm = true; this.saving = false;
    },
    cancel() { this.showForm = false; this.form = {}; },
    async submit() {
      this.saving = true;
      try {
        if (this.editMode) {
          const { data } = await api.put(`/tasks/${this.form.id}`, this.form);
          const idx = this.tasks.findIndex(t => t.id === data.id);
          if (idx !== -1) this.$set(this.tasks, idx, data);
        } else {
          const { data } = await api.post('/tasks', this.form);
          this.tasks.unshift(data);
        }
        this.cancel();
      } catch (e) {
        alert((e.response && e.response.data && e.response.data.message) || 'Erro');
      } finally {
        this.saving = false;
      }
    },
    async confirmDelete(task) {
      if (!confirm(`Apagar tarefa #${task.id} ${task.title}?`)) return;
      try {
        await api.delete(`/tasks/${task.id}`);
        this.tasks = this.tasks.filter(t => t.id !== task.id);
      } catch (e) {
        alert((e.response && e.response.data && e.response.data.message) || 'Erro ao apagar');
      }
    }
  }
};
</script>

<style>
.actions { margin-bottom:12px; }
.list { padding:0; list-style:none; }
.list li { background:#fff; margin:8px 0; padding:10px 12px; border-radius:6px; }
.row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.row .desc { margin-top:6px; color:#374151; }
.row-actions { display:flex; gap:8px; }
.row-actions button { padding:6px 8px; font-size:13px; border-radius:6px; border:1px solid #e5e7eb; background:#f3f4f6; }
.form-card { background:#fff; padding:16px; border-radius:8px; margin-top:12px; }
.form-actions { margin-top:12px; display:flex; gap:8px; }
textarea { width:100%; min-height:80px; }
</style>
