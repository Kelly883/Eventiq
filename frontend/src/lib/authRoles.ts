export function getUserRoles(user: any): string[] {
  if (!user) return [];
  const roles = new Set<string>();

  const addRole = (value: unknown) => {
    if (typeof value !== 'string') return;
    roles.add(value.toLowerCase());
  };
  if (user.role && typeof user.role === 'string') {
    addRole(user.role);
  }
  if (user.roleRelation?.name) {
    addRole(user.roleRelation.name);
  }
  (user.roles || []).forEach((r: any) => {
    if (r?.name) addRole(r.name);
  });

  return Array.from(roles);
}
