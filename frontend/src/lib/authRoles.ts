export function getUserRoles(user: any): string[] {
  if (!user) return [];
  const roles = new Set<string>();

  if (user.role && typeof user.role === 'string') {
    roles.add(user.role);
  }
  if (user.roleRelation?.name) {
    roles.add(user.roleRelation.name);
  }
  (user.roles || []).forEach((r: any) => {
    if (r?.name) roles.add(r.name);
  });

  return Array.from(roles);
}
