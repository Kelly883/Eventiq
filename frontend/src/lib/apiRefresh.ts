// Shared refresh/token helpers placeholder.
// Kept separate so `frontend/src/lib/api.ts` stays focused on transport concerns.
//
// Backend integration required: implement POST /auth/refresh (or equivalent)
// and return { accessToken: string }.

export type RefreshResponse = {
  accessToken?: string;
};

export async function refreshAccessToken(axiosInstance: any): Promise<string | null> {
  const response = await axiosInstance.post('/auth/refresh');
  const data = response?.data as RefreshResponse | undefined;
  return data?.accessToken ?? null;
}

