import Vue from 'vue';
import Router from 'vue-router';
import store from './store';

import Login from './views/Login.vue';
import Tasks from './views/Tasks.vue';
import Users from './views/Users.vue';
import Companies from './views/Companies.vue';

Vue.use(Router);

const router = new Router({
  mode: 'hash',
  routes: [
    { path: '/login', component: Login },
    { path: '/', redirect: '/tasks' },
    { path: '/tasks', component: Tasks, meta: { requiresAuth: true } },
    { path: '/users', component: Users, meta: { requiresAuth: true } },
    { path: '/companies', component: Companies, meta: { requiresAuth: true } }
  ]
});

router.beforeEach(async (to, _, next) => {
  if (to.matched.some(r => r.meta.requiresAuth)) {
    if (!store.state.token) return next('/login');
    if (!store.state.user) await store.fetchMe();
    return next();
  }
  next();
});

export default router;
