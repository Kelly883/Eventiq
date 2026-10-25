import type { ReactNode } from 'react';

export interface AuthUser {
  id?: number | string;
  name?: string | null;
  email?: string | null;
  roles?: { id?: number; name?: string }[];
  [key: string]: unknown;
}

export interface AuthContextValue {
  user: AuthUser | null;
  isAuthenticated: boolean;
  loading: boolean;
  [key: string]: unknown;
}

export declare const AuthProvider: (props: { children: ReactNode }) => JSX.Element;
export declare function useAuthContext(): AuthContextValue;
