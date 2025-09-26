<template>
  <div>
    <h1>
      Empresas
      <button class="export-btn" @click="exportCsv" title="Exportar CSV">📥</button>
    </h1>
    <p v-if="!isMaster" class="warn">Acesso restrito a master.</p>
    <div v-else>
      <div class="actions">
        <button @click="startCreate">Nova empresa</button>
      </div>
      <div v-if="loading">Carregando...</div>
      <ul v-else class="list">
        <li v-for="c in companies" :key="c.id">
          <div class="row">
            <div>
              <strong>#{{ c.id }} {{ c.name }}</strong>
              <small> | slug: {{ c.slug || '-' }}</small>
            </div>
            <div class="row-actions">
              <button v-if="isMaster" @click="startEdit(c)">Editar</button>
              <button v-if="isMaster" @click="confirmDelete(c)">Apagar</button>
            </div>
          </div>
        </li>
      </ul>

      <div class="pagination">
        <button @click="changePage(-1)" :disabled="page <= 1">« Anterior</button>
        <span>Página {{ page }} de {{ lastPage }} ({{ total }} itens)</span>
        <button @click="changePage(1)" :disabled="page >= lastPage">Próxima »</button>
      </div>

      <Modal :visible="showForm" @close="cancel">
        <template #header>
          <h3 style="margin:0">{{ editMode ? 'Editar empresa' : 'Criar empresa' }}</h3>
        </template>
  <form class="form-card" @submit.prevent="submit">
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
      </Modal>
    </div>
  </div>
</template>

<script>
import api from '../api';
import store from '../store';
import { downloadCsv } from '../utils/csv';
import Modal from '../components/Modal.vue';

export default {
  components: { Modal },
  data: () => ({ companies: [], loading: true, showForm: false, editMode: false, form: {}, saving: false, formError: null, page: 1, perPage: 20, lastPage: 1, total: 0 }),
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
        const { data } = await api.get('/companies', { params: { page: this.page, per_page: this.perPage } });
        if (data && data.data) {
          this.companies = data.data;
          this.page = data.current_page || this.page;
          this.lastPage = data.last_page || 1;
          this.total = data.total || this.companies.length;
        } else {
          this.companies = Array.isArray(data) ? data : [];
          this.page = 1; this.lastPage = 1; this.total = this.companies.length;
        }
      } finally {
        this.loading = false;
      }
    },

    changePage(delta) {
      this.page = Math.max(1, Math.min(this.lastPage, this.page + delta));
      this.load();
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
    ,
    async exportCsv() {
      try {
        const { data } = await api.get('/companies');
        const rows = Array.isArray(data.data) ? data.data : data;
        const cols = [
          { key: 'id', label: 'ID' },
          { key: 'name', label: 'Nome' },
          { key: 'email', label: 'E-mail' },
          { key: 'phone', label: 'Telefone' },
        ];
        downloadCsv('companies.csv', rows, cols);
      } catch (e) {
        alert('Erro ao exportar empresas');
      }
    }
  }
};
</script>

<style>
.warn { color:#92400e; background:#fef3c7; padding:8px; border-radius:6px; }
.list { padding:0; list-style:none; }
.list li { background:#fff; margin:8px 0; padding:8px; border-radius:6px; }
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

<style>
.form-card select {
  padding: 12px 14px;
  font-size: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  min-width: 320px;
}
.form-card select[v-model="form"] {
  min-width: 480px;
}
</style>
