<template>
  <div>
    <h1>
      Usuários
      <button class="export-btn" @click="exportCsv" title="Exportar CSV">📥</button>
    </h1>
    <p v-if="!allowed" class="warn">Acesso restrito a admin/master.</p>
    <div v-else>
      <div class="actions">
        <button @click="startCreate">Novo usuário</button>
      </div>

      <div v-if="loading">Carregando...</div>
      <ul v-else class="list">
        <li v-for="u in users" :key="u.id">
          <div class="row">
            <div>
              <strong>#{{ u.id }} {{ u.name }}</strong>
              <small> | {{ u.email }} | role: {{ u.role }} | tenant: {{ lookupCompanyLabel(u.tenant_id) }}</small>
            </div>
            <div class="row-actions">
              <button @click="startEdit(u)">Editar</button>
              <button @click="confirmDelete(u)" :disabled="u.role === 'master'">Apagar</button>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="showForm" class="form-card">
        <h3>{{ editMode ? 'Editar usuário' : 'Criar usuário' }}</h3>
        <form @submit.prevent="submit">
          <label>Nome</label>
          <input v-model="form.name" required />
          <label>E-mail</label>
          <input v-model="form.email" type="email" required />
          <label>Senha <small v-if="editMode">(deixe em branco para não alterar)</small></label>
          <input v-model="form.password" type="password" :required="!editMode" />
          <!-- Only allow role editing when current user is master and we're NOT editing an existing master user -->
          <span v-if="isMaster && !(editMode && form.role === 'master')">
            <label>Role</label>
            <select v-model="form.role">
              <option value="user">user</option>
              <option value="admin">admin</option>
            </select>
          </span>
          <span v-if="isMaster && !(editMode && form.role === 'master')">
            <label>Tenant</label>
            <select v-model.number="form.tenant_id">
              <option :value="null">-- selecione --</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </span>

          <div class="form-actions">
            <button type="submit" :disabled="saving">{{ saving ? 'Salvando...' : 'Salvar' }}</button>
            <button type="button" @click="cancel">Cancelar</button>
          </div>
          <p v-if="formError" class="error">{{ formError }}</p>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../api';
import store from '../store';
import { downloadCsv } from '../utils/csv';

export default {
  data: () => ({ users: [], companies: [], loading: true, showForm: false, editMode: false, form: {}, saving: false, formError: null }),
  computed: {
    allowed() {
      const user = store.state.user;
      return user && (user.role === 'admin' || user.role === 'master');
    },
    isMaster() { 
      const user = store.state.user;
      return user && user.role === 'master'; 
    }
  },
  async created() {
    if (!this.allowed) return;
    await this.load();
    if (this.isMaster) await this.loadCompanies();
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
    },
    async loadCompanies() {
      try {
        const { data } = await api.get('/companies');
        this.companies = Array.isArray(data.data) ? data.data : data;
      } catch (e) {
        this.companies = [];
      }
    },
    async exportCsv() {
      try {
        const { data } = await api.get('/users');
        const rows = Array.isArray(data.data) ? data.data : data;
        if (this.companies.length === 0 && this.isMaster) {
          await this.loadCompanies();
        }
        const cols = [
          { key: 'id', label: 'ID' },
          { key: 'name', label: 'Nome' },
          { key: 'email', label: 'E-mail' },
          { key: 'role', label: 'Role' },
          { key: 'tenant', label: 'Tenant' },
        ];
        const norm = rows.map(r => ({
          ...r,
          tenant: (this.companies.find(c => c.id === r.tenant_id) || { name: (r.tenant_id ? `#${r.tenant_id}` : '-') }).name
        }));
        downloadCsv('users.csv', norm, cols);
      } catch (e) {
        alert('Erro ao exportar usuários');
      }
    },
    lookupCompanyLabel(id) {
      if (!id) return '-';
      const c = this.companies.find(x => x.id === id);
      return c ? c.name : `#${id}`;
    },
    startCreate() {
      this.form = { name: '', email: '', password: '', role: 'user', tenant_id: (store.state.user && store.state.user.tenant_id) || null };
      this.editMode = false;
      this.formError = null;
      this.showForm = true;
    },
    startEdit(user) {
      this.form = { id: user.id, name: user.name, email: user.email, password: '', role: user.role, tenant_id: user.tenant_id };
      this.editMode = true;
      this.formError = null;
      this.showForm = true;
    },
    cancel() {
      this.showForm = false;
      this.form = {};
    },
    async submit() {
      this.saving = true; this.formError = null;
      try {
        if (this.editMode) {
          const payload = { name: this.form.name, email: this.form.email, role: this.form.role };
          if (this.form.password) payload.password = this.form.password;
          const { data } = await api.put(`/users/${this.form.id}`, payload);
          // update local list
          const idx = this.users.findIndex(u => u.id === data.id);
          if (idx !== -1) this.$set(this.users, idx, data);
        } else {
          const payload = { ...this.form };
          const { data } = await api.post('/users', payload);
          // prepend new user
          this.users.unshift(data);
        }
        this.cancel();
      } catch (e) {
        this.formError = (e.response && e.response.data && (e.response.data.message || e.response.data.error)) || 'Erro';
      } finally {
        this.saving = false;
      }
    },
    async confirmDelete(user) {
      if (!confirm(`Apagar usuário #${user.id} ${user.name}?`)) return;
      try {
        await api.delete(`/users/${user.id}`);
        this.users = this.users.filter(u => u.id !== user.id);
      } catch (e) {
        alert((e.response && e.response.data && e.response.data.message) || 'Erro ao apagar');
      }
    }
  }
};
</script>

<style>
.warn { color:#92400e; background:#fef3c7; padding:8px; border-radius:6px; }
.list { padding:0; list-style:none; }
.list li { background:#fff; margin:8px 0; padding:10px 12px; border-radius:6px; }
.row { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.row-actions { display:flex; gap:8px; align-items:center; }
.row-actions button {
  padding:6px 8px;
  font-size:13px;
  border-radius:6px;
  border: 1px solid #d1d5db;
  background: #f3f4f6;
  color: #111827;
  cursor: pointer;
}
.row-actions button[disabled] { opacity:0.6; cursor:not-allowed; }
.actions button { padding:8px 10px; }
.export-btn {
  margin-left:12px;
  padding:6px 8px;
  font-size:14px;
  border-radius:6px;
  border: 1px solid #d1d5db;
  background: #f3f4f6;
  cursor: pointer;
}
</style>
