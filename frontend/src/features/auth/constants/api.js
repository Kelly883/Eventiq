export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const AUTH_ENDPOINTS = {
  LOGIN: `${API_BASE_URL}/auth/login`,
  REGISTER: `${API_BASE_URL}/auth/register`,
  FORGOT_PASSWORD: `${API_BASE_URL}/auth/forgot-password`,
  RESET_PASSWORD: `${API_BASE_URL}/auth/reset-password`,
  LOGOUT: `${API_BASE_URL}/auth/logout`,
  ME: `${API_BASE_URL}/auth/me`,
};

export const ROLES_PERMISSIONS_ENDPOINTS = {
  SUBMIT_PERMISSION_REQUEST: `${API_BASE_URL}/permissions/request`,
  ADMIN_ROLES: `${API_BASE_URL}/admin/roles`,
  ADMIN_PERMISSIONS: `${API_BASE_URL}/admin/permissions`,
  ADMIN_AUDIT_LOG: `${API_BASE_URL}/admin/audit-log`,
  ADMIN_PERMISSION_REQUESTS: `${API_BASE_URL}/admin/permission-requests`,
};

export const PASSWORD_REQUIREMENTS = {
  MIN_LENGTH: 8,
  REQUIRE_UPPERCASE: true,
  REQUIRE_LOWERCASE: true,
  REQUIRE_NUMBER: true,
  REQUIRE_SPECIAL_CHAR: true,
};
