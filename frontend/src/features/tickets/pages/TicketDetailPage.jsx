import React, { useEffect } from 'react';
import { useParams, useNavigate, Navigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ticketKeys } from '../../lib/queryKeys';
import { api } from '../../lib/api';
import { Skeleton, Box, Text, Button } from '@/ui';

const TicketDetailPage = () => {
  const { ticketId } = useParams();
  const navigate = useNavigate();

  // Guard: if no ticketId, redirect to tickets list
  if (!ticketId) {
    return <Navigate to="/my-tickets" replace />;
  }

  const { data, isLoading, isError, error } = useQuery(
    ticketKeys.detail(ticketId),
    async () => {
      const response = await api.get(`/tickets/${ticketId}`);
      return response.data;
    },
    {
      staleTime: 5 * 60 * 1000,
      cacheTime: 30 * 60 * 1000,
      retry: 2,
    }
  );

  // Error / not-found handling
  if (isError || !data) {
    return (
      <Box
        textAlign="center"
        py={8}
        px={4}
        bgcolor="white"
        borderRadius="xl"
        border="1px solid #e2e8f0"
      >
        <Text fontSize="lg" color="#64748b">
          {isError ? 'Failed to load ticket' : 'Ticket not found'}
        </Text>
        <Button
          onClick={() => navigate('/my-tickets', { replace: true })}
          marginTop={4}
          size="sm"
          color="secondary"
        >
          Go back to my tickets
        </Button>
      </Box>
    );
  }

  // Skeleton / loading state
  if (isLoading) {
    return (
      <Box py={8} px={4}>
        <Skeleton className="h-64 w-96 mb-4" />
        <Skeleton className="h-16 w-80 mb-2" />
        <Skeleton className="h-10 w-60 mb-2" />
        <Skeleton className="h-8 w-40" />
        <Button
          onClick={() => navigate('/my-tickets', { replace: true })}
          marginTop={4}
          size="sm"
          disabled
        >
          Loading ticket details…
        </Button>
      </Box>
    );
  }

  // Successful render - ticket data available
  const { id, code, status, createdAt, events, ...rest } = data;

  return (
    <Box py={6} px={4} bg="white" borderRadius="xl" border="1px solid #e2e8f0">
      <Box mb={4}>
        <Text fontSize="xl" fontWeight="bold" color="#1e293b">
          Ticket #{id}
        </Text>
        <Text fontSize="sm" color="#64748b">
          Code: {code}
        </Text>
      </Box>

      <Box mb={6}>
        <Text fontSize="sm" color="#64748b">
          Status: {status}
        </Text>
        <Text fontSize="sm" color="#64748b">
          Created: {createdAt ? new Date(createdAt).toLocaleDateString() : 'N/A'}
        </Text>
      </Box>

      <Box>
        {/* Ticket details / events go here */}
        <Text fontSize="sm" color="#64748b">
          Events: {events?.length || 0}{' '}
          {(events?.length || 0) > 1 && (
            <small>
              ({events.length} activities)
            </small>
          )}
        </Text>
      </Box>
    </Box>
  );
};

export default TicketDetailPage;