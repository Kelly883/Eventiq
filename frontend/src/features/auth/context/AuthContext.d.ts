/// <reference types="react" />
import type { ReactNode, ReactElement } from 'react';

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

export declare const AuthProvider: (props: { children: ReactNode }) => ReactElement;
export declare function useAuthContext(): AuthContextValue;
