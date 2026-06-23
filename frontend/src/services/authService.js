import api from '../config/axios'

const authService = {
  login: (credentials) =>
    api.post('/auth/login', credentials),

  register: (data) =>
    api.post('/auth/register', data),

  logout: () =>
    api.post('/auth/logout'),

  me: () =>
    api.get('/auth/me'),

  forgotPassword: (email) =>
    api.post('/auth/forgot-password', { email }),

  resetPassword: (data) =>
    api.post('/auth/reset-password', data),

  verifyEmail: (token) =>
    api.get(`/auth/verify-email/${token}`),

  resendVerification: () =>
    api.post('/auth/resend-verification'),

  updateProfile: (data) =>
    api.put('/auth/profile', data),

  updatePassword: (data) =>
    api.put('/auth/password', data),

  uploadAvatar: (formData) =>
    api.post('/auth/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
}

export default authService