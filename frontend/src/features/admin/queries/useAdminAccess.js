import { useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '../services/apiClient.js'
import { adminKeys } from '../../../lib/queryKeys'

export const ADMIN_ACCESS_QUERY_KEY = adminKeys.access()

const fetchAdminAccess = async () => {
  // The current repo has placeholder API clients.
  // This is wired so you can replace the implementation later.
  const res = await apiClient.get('/admin/access')
  // Expected shape: { role: 'admin', permissions: [...] } OR { isAdmin: true }
  return res?.data ?? null
}

export const useAdminAccess = () => {
  const queryClient = useQueryClient()

  return useQuery({
    queryKey: adminKeys.access(),
    queryFn: fetchAdminAccess,
    staleTime: 60_000, // short stale time; invalidate after mutations
    retry: 1,
  })
}

export const invalidateAdminAccess = () => {
  const queryClient = useQueryClient()
  return queryClient.invalidateQueries({ queryKey: adminKeys.access() })
}


