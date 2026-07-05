// Role and permission utilities
export const formatPermissionName = (name: string) => {
  return name.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
