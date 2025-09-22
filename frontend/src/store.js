import Vue from 'vue';
import api from './api';

const state = Vue.observable({
  token: localStorage.getItem('token') || null,
  user: null,
  loading: false,
  error: null
});

async function fetchMe() {
  if (!state.token) return null;
  try {
    const { data } = await api.get('/me');
    state.user = data;
    return data;
  } catch (e) {
    clearAuth();
    return null;
  }
}

function setToken(token) {
  state.token = token;
  localStorage.setItem('token', token);
}

function clearAuth() {
  state.token = null;
  state.user = null;
  localStorage.removeItem('token');
}

export default {
  state,
  fetchMe,
  setToken,
  clearAuth
};
