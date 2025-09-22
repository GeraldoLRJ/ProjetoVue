import axios from 'axios';
import store from './store';

const api = axios.create({
  baseURL: process.env.VUE_APP_API_BASE || 'http://localhost:8080/api'
});

api.interceptors.request.use((config) => {
  if (store.state.token) {
    config.headers.Authorization = `Bearer ${store.state.token}`;
  }
  return config;
});

api.interceptors.response.use(
  r => r,
  err => {
    if (err.response && err.response.status === 401) {
      store.clearAuth();
      window.location.href = '/#/login';
    }
    return Promise.reject(err);
  }
);

export default api;
