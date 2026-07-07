import { t as _t } from './t';

// Convenience wrapper for components that want: t('admin','userManagement.title')
export function t(namespace, key, vars) {
  return _t(namespace, key, vars);
}

