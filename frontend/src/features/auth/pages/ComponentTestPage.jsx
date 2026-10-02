import React, { useState, useCallback } from 'react';
import AuthCard from './AuthCard';
import FormField from './FormField';
import PasswordVisibilityToggle from './PasswordVisibilityToggle';
import PasswordStrengthMeter from './PasswordStrengthMeter';
import SubmitButton from './SubmitButton';
import './AuthComponents.css';

const ComponentTestPage = () => {
  const [formValue, setFormValue] = useState('');
  const [formError, setFormError] = useState(null);
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [submitLoading, setSubmitLoading] = useState(false);
  const [submitDisabled, setSubmitDisabled] = useState(false);
  const [logs, setLogs] = useState([]);

  const addLog = useCallback((message) => {
    setLogs((prev) => [...prev.slice(-9), `${new Date().toLocaleTimeString()} — ${message}`]);
  }, []);

  const handleFormChange = (e) => {
    const val = e.target.value;
    setFormValue(val);
    if (val.length > 0 && val.length < 3) {
      setFormError('Too short');
      addLog(`FormField error shown for value "${val}"`);
    } else {
      setFormError(null);
      addLog(`FormField error cleared for value "${val}"`);
    }
  };

  const handleTogglePassword = () => {
    setShowPassword((v) => {
      const next = !v;
      addLog(`PasswordVisibilityToggle: ${next ? 'shown' : 'hidden'}`);
      return next;
    });
  };

  const handlePasswordChange = (e) => {
    setPassword(e.target.value);
  };

  const handleSubmitClick = () => {
    setSubmitLoading(true);
    addLog('SubmitButton loading started');
    setTimeout(() => {
      setSubmitLoading(false);
      addLog('SubmitButton loading finished');
    }, 2000);
  };

  const toggleSubmitDisabled = () => {
    setSubmitDisabled((d) => !d);
    addLog(`SubmitButton disabled set to ${!submitDisabled}`);
  };

  return (
    <div style={{ minHeight: '100vh', background: '#f7f8fa', padding: '40px 16px' }}>
      <AuthCard title="Component Test Page">
        {/* FormField with error state */}
        <FormField
          label="Test Input"
          value={formValue}
          onChange={handleFormChange}
          error={formError}
          placeholder="Type 1-2 chars to show error..."
          id="test-form-field"
          name="testInput"
        />

        {/* Password field with visibility toggle */}
        <div className="auth-password-field">
          <FormField
            label="Password"
            type={showPassword ? 'text' : 'password'}
            value={password}
            onChange={handlePasswordChange}
            placeholder="Type a password..."
            id="test-password"
            name="testPassword"
          />
          <PasswordVisibilityToggle
            visible={showPassword}
            onClick={handleTogglePassword}
          />
        </div>

        {/* Password strength meter */}
        <PasswordStrengthMeter password={password} />

        {/* Submit button */}
        <SubmitButton
          loading={submitLoading}
          disabled={submitDisabled}
          onClick={handleSubmitClick}
        >
          {submitLoading ? 'Submitting…' : 'Submit'}
        </SubmitButton>

        <button
          type="button"
          onClick={toggleSubmitDisabled}
          style={{
            width: '100%',
            minHeight: '44px',
            border: '1px solid #e3e4e6',
            borderRadius: '8px',
            background: '#fff',
            cursor: 'pointer',
            fontSize: '14px',
            fontWeight: 600,
            marginTop: '8px',
          }}
        >
          {submitDisabled ? 'Enable Submit Button' : 'Disable Submit Button'}
        </button>

        {/* Event log */}
        <div
          style={{
            marginTop: '24px',
            padding: '12px',
            background: '#1e1e1e',
            borderRadius: '8px',
            color: '#d4d4d4',
            fontFamily: 'monospace',
            fontSize: '12px',
            lineHeight: '1.6',
            maxHeight: '200px',
            overflowY: 'auto',
          }}
        >
          <div style={{ color: '#888', marginBottom: '8px', fontWeight: 600 }}>Event Log</div>
          {logs.length === 0 && <span style={{ color: '#666' }}>Interact with components to see events…</span>}
          {logs.map((log, i) => (
            <div key={i}>{log}</div>
          ))}
        </div>
      </AuthCard>
    </div>
  );
};

export default ComponentTestPage;
