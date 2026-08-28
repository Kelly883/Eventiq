import React from 'react';

/**
 * Global Error Boundary — covers the entire React tree.
 * Captures runtime errors, shows a production fallback,
 * preserves a correlation ID for diagnostics, and offers
 * a reload action. Class-only, no type annotations.
 */
export default class GlobalErrorBoundary extends React.Component {
  state = {
    hasError: false,
    error: null,
    errorId: '',
  };

  static getDerivedStateFromError(error) {
    const errorId = Math.random().toString(36).substring(2, 12);
    return { hasError: true, error, errorId };
  }

  componentDidCatch(error, errorInfo) {
    // Log to console for diagnostics; in production send to backend.
    console.error('GlobalErrorBoundary caught:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          textAlign: 'center',
          padding: '8px 4px',
          background: 'white',
          borderRadius: '8px',
          border: '1px solid #e2e8f0',
          maxWidth: '100%',
        }}>
          <h2 style={{ marginBottom: '12px' }}>Something went wrong</h2>
          <p>Eventiq couldn't load this page correctly.</p>
          <p>Error ID: <code>{this.state.errorId}</code></p>
          <button onClick={() => window.location.reload()} style={{
            padding: '4px 8px',
            background: '#6366f1',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer',
          }}>
            Reload
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}