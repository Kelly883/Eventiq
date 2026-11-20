import { useState, useCallback } from 'react';

interface UseApiErrorHandlerReturn {
  error: string | null;
  setError: (err: string) => void;
  resetError: () => void;
  retryCount: number;
  isRecovered: boolean;
  getValidationErrors: (err: any) => string | null;
}

export function useApiErrorHandler(): UseApiErrorHandlerReturn {
  const [error, setError] = useState<string | null>(null);
  const [retryCount, setRetryCount] = useState(0);
  const [isRecovered, setIsRecovered] = useState(false);

  const handleError = useCallback((err: any) => {
    let message = 'An unexpected error occurred.';

    if (err instanceof Error) {
      message = err.message;
    } else if (err?.status) {
      switch (err.status) {
        case 400:
          message = 'Bad request. Please check your input and try again.';
          break;
        case 401:
          message = 'Unauthorized. Please log in and try again.';
          break;
        case 403:
          message = 'Forbidden. You do not have permission to perform this action.';
          break;
        case 404:
          message = 'The requested resource was not found.';
          break;
        case 409:
          message = 'Conflict. The resource may have been modified.';
          break;
        case 422:
          message = getFirstValidationError(err) || 'Validation failed. Please check your input.';
          break;
        case 429:
          message = 'Too many requests. Please wait a moment and try again.';
          break;
        case 500:
        case 502:
        case 503:
          message = 'Server error. Please try again later.';
          break;
        default:
          message = `Error ${err.status}. Please try again.`;
      }
    }

    setError(message);
    setRetryCount(prev => prev + 1);

    if (retryCount >= 3) {
      setIsRecovered(true);
    }
  }, []);

  const resetError = useCallback(() => {
    setError(null);
    setRetryCount(0);
    setIsRecovered(false);
  }, []);

  const getFirstValidationError = useCallback((err: any): string | null => {
    if (!err?.response?.data) return null;
    const errors = err.response.data;
    if (typeof errors === 'string') return errors;
    if (errors.errors) {
      const firstKey = Object.keys(errors.errors)[0];
      return errors.errors[firstKey]?.[0] || null;
    }
    if (errors.message) return errors.message;
    return null;
  }, []);

  return {
    error,
    setError: handleError,
    resetError,
    retryCount,
    isRecovered,
    getValidationErrors: getFirstValidationError,
  };
}
