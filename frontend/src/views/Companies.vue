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
      <div class="actions">
        <button @click="startCreate">Nova empresa</button>
      </div>

      <div v-if="showForm" class="form-card">
        <h3>{{ editMode ? 'Editar empresa' : 'Criar empresa' }}</h3>
        <form @submit.prevent="submit">
          <label>Nome</label>
          <input v-model="form.name" required />
          <label>Apelido</label>
          <input v-model="form.slug" />
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

export default {
  data: () => ({ companies: [], loading: true, showForm: false, editMode: false, form: {}, saving: false, formError: null }),
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
    },
    startCreate() {
      this.form = { name: '', slug: '' };
      this.editMode = false;
      this.formError = null;
      this.showForm = true;
    },
    startEdit(c) {
      this.form = { id: c.id, name: c.name, slug: c.slug };
      this.editMode = true;
      this.formError = null;
      this.showForm = true;
    },
    cancel() { this.showForm = false; this.form = {}; },
    async submit() {
      this.saving = true; this.formError = null;
      try {
        if (this.editMode) {
          const { data } = await api.put(`/companies/${this.form.id}`, { name: this.form.name, slug: this.form.slug });
          const idx = this.companies.findIndex(x => x.id === data.id);
          if (idx !== -1) this.$set(this.companies, idx, data);
        } else {
          const { data } = await api.post('/companies', { name: this.form.name, slug: this.form.slug });
          this.companies.unshift(data);
        }
        this.cancel();
      } catch (e) {
        this.formError = (e.response && e.response.data && (e.response.data.message || e.response.data.error)) || 'Erro';
      } finally {
        this.saving = false;
      }
    },
    async confirmDelete(c) {
      if (!confirm(`Apagar empresa #${c.id} ${c.name}?`)) return;
      try {
        await api.delete(`/companies/${c.id}`);
        this.companies = this.companies.filter(x => x.id !== c.id);
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
.list li { background:#fff; margin:8px 0; padding:8px; border-radius:6px; }
</style>
