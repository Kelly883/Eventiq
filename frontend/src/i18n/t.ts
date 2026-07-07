type Vars = Record<string, string | number | boolean | null | undefined>;

function interpolate(template: string, vars?: Vars): string {
  if (!vars) return template;
  return template.replace(/\{\{\s*(\w+)\s*\}\}/g, (_, k: string) => {
    const v = vars[k];
    return v === undefined || v === null ? '' : String(v);
  });
}

function getByPath(obj: any, path: string): any {
  return path.split('.').reduce((acc, part) => {
    if (acc === undefined || acc === null) return undefined;
    return acc[part];
  }, obj);
}

// Lazy-loaded namespaces to keep startup fast.
const cache: Record<string, any> = {};

export async function getNamespace(namespace: string, lang: string = 'en') {
  const key = `${lang}:${namespace}`;
  if (cache[key]) return cache[key];

  // Vite supports dynamic imports of JSON.
  const mod = await import(`./locales/${lang}/${namespace}.json`);
  cache[key] = mod.default ?? mod;
  return cache[key];
}

export async function t(namespace: string, key: string, vars?: Vars, lang: string = 'en') {
  const ns = await getNamespace(namespace, lang);
  const val = getByPath(ns, key);
  const str = typeof val === 'string' ? val : undefined;
  if (!str) return key;
  return interpolate(str, vars);
}

