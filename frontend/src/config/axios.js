import axios from 'axios';
import { API_BASE_URL, TOKEN_KEY } from './constants';
import useAuthStore from '../stores/authStore';
// Create axios instance
const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Request interceptor - Add token to headers
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem('user_data');
      useAuthStore.getState().logout();    }

   // Handle errors
    const message =
      error.response?.data?.message ||
      error.message ||
      'Une erreur est survenue';

    // Return validation errors if available
    if (error.response?.data?.errors) {
      return Promise.reject({
        ...error,
        errors: error.response.data.errors,
      });
    }

    return Promise.reject(error);
  }
);

export default axiosInstance;
