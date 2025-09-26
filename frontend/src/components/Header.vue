<template>
	<header class="topbar">
		<div class="left">
			<h1 class="brand">Controle de Tarefas</h1>
		</div>
		<div class="right">
			<template v-if="user">
				<span class="username">{{ user.name || user.email || 'Usuário' }}
					<span v-if="user.company && user.company.name"> — {{ user.company.name }}</span>
				</span>
				<button class="btn" @click="logout">Sair</button>
			</template>
			<template v-else>
				<router-link class="btn" to="/login">Login</router-link>
			</template>
		</div>
	</header>
</template>

<script>
import store from '../store';
import api from '../api';

export default {
	name: 'HeaderBar',
	data() {
		return {
			storeState: store.state
		};
	},
	computed: {
		user() {
			return this.storeState.user;
		},
		companyName() {
			const u = this.user;
			if (!u || u.company === undefined || u.company === null) return null;
			// backend may return company as a string or as an object with name
			if (typeof u.company === 'string') return u.company;
			return u.company.name || null;
		}
	},
	methods: {
		async logout() {
			// try to notify server to invalidate the token, but remove token locally regardless
			try {
				await api.post('/logout');
			} catch (e) {
				// ignore errors (we still want to clear local token)
			} finally {
				store.clearAuth();
				this.$router.push('/login');
			}
		}
	}
};
</script>

<style scoped>
.topbar { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: #111827; color: #fff; }
.brand { margin: 0; font-size: 18px; font-weight: 600; }
.right { display: flex; align-items: center; gap: 10px; }
.username { margin-right: 8px; }
.btn { background: #2563eb; color: #fff; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; text-decoration: none; }
.btn:hover { background: #1d4ed8; }
a.btn { display: inline-block; }
</style>
