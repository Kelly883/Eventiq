import React from 'react';

export class RolesErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Roles component error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="roles-error-boundary" role="alert" data-testid="roles-error-boundary">
          <p className="roles-error-boundary__message">Something went wrong loading this section.</p>
          {this.props.fallback && <div data-testid="roles-error-boundary-fallback">{this.props.fallback}</div>}
        </div>
      );
    }

    return this.props.children;
  }
}
