<template>
  <div>
    <h1>Tarefas</h1>
    <div class="actions" v-if=canUsers()>
      <button @click="startCreate">Nova tarefa</button>
    </div>

    <div v-if="loading">Carregando...</div>
    <div v-else>
      <ul class="list">
        <li v-for="t in tasks" :key="t.id">
          <div class="row">
            <div>
              <strong>#{{ t.id }} {{ t.title }}</strong>
              <small>
                | status: {{ labelStatus(t.status) }}
                | prioridade: {{ labelPriority(t.priority) }}
                | vencimento: {{ formatDateTime(t.due_date) }}
                <span v-if="t.user || t.user_id"> | responsável: {{ (t.user && (t.user.name + ' | ' + t.user.email)) || lookupUserLabel(t.user_id) }}</span>
              </small>
              <div class="desc" v-if="t.description">{{ t.description }}</div>
            </div>
            <div class="row-actions">
              <button @click="startEdit(t)" v-if=canUsers()>Editar</button>
              <button @click="confirmDelete(t)" v-if=canUsers()>Apagar</button>
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
            <option value="pending">pendente</option>
            <option value="in_progress">em progresso</option>
            <option value="done">feito</option>
          </select>
          <label>Prioridade</label>
          <select v-model="form.priority">
            <option value="low">baixa</option>
            <option value="medium">média</option>
            <option value="high">alta</option>
          </select>
          <label>Responsável</label>
          <select v-model.number="form.user_id">
            <option :value="null">-- nenhum --</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} | {{ u.email }}</option>
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
import store from '../store';
import { formatDateTime, formatDate } from '../utils/dateFormat';

export default {
  data: () => ({ tasks: [], users: [], loading: true, showForm: false, editMode: false, form: {}, saving: false }),
  async created() {
    await Promise.all([this.load(), this.loadUsers()]);
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
    formatDateTime,
    labelStatus(code) {
      const map = { pending: 'pendente', in_progress: 'em progresso', done: 'feito' };
      return map[code] || code;
    },
    labelPriority(code) {
      const map = { low: 'baixa', medium: 'média', high: 'alta' };
      return map[code] || code;
    },
    lookupUserLabel(id) {
      if (!id) return '-';
      const u = this.users.find(x => x.id === id);
      return u ? `${u.name} | ${u.email}` : `#${id}`;
    },
    startCreate() {
      this.form = { title: '', description: '', status: 'pending', priority: 'medium', due_date: null, user_id: null };
      this.editMode = false; this.showForm = true; this.saving = false;
    },
    startEdit(task) {
      // Ensure the date input (type="date") receives a YYYY-MM-DD string
      const due = task && task.due_date ? formatDate(task.due_date) : null;
      this.form = { ...task, due_date: due, user_id: task.user_id || (task.user && task.user.id) || null };
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
          // When creating, ensure due_date includes time 23:59 if user provided only a date
          const payload = { ...this.form };
          if (payload.due_date && /^\d{4}-\d{2}-\d{2}$/.test(payload.due_date)) {
            payload.due_date = `${payload.due_date} 23:59`;
          }
          const { data } = await api.post('/tasks', payload);
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
    ,
    async loadUsers() {
      try {
        const { data } = await api.get('/users');
        const list = Array.isArray(data.data) ? data.data : data;
        this.users = list;
      } catch (e) {
        // ignore or show message
        this.users = [];
      }
    },
    canUsers() {
      const user = store.state.user;
      return user && (user.role === 'admin' || user.role === 'master');
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
.form-card input[type="date"] {
  width: 180px;
  max-width: 100%;
  padding: 8px;
  box-sizing: border-box;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}
@media (max-width: 640px) {
  .form-card input[type="date"] { width: 100%; }
}

/* título menor e descrição um pouco mais estreita */
.form-card input:first-of-type {
  width: 420px;
  max-width: 100%;
  padding: 10px;
  box-sizing: border-box;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}
.form-card textarea {
  width: 70%;
  min-height: 80px;
  box-sizing: border-box;
}
@media (max-width: 640px) {
  .form-card input:first-of-type { width: 100%; }
  .form-card textarea { width: 100%; }
}

/* Aumentar selects no formulário de tarefas */
.form-card select {
  padding: 10px 12px;
  font-size: 15px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  min-width: 240px;
}
.form-card select[v-model="form.user_id"] {
  min-width: 420px;
}
@media (max-width: 640px) {
  .form-card select { min-width: 100%; width: 100%; }
}
</style>
