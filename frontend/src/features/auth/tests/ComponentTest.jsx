import React, { useState } from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { FormField } from './FormField.jsx';
import { PasswordVisibilityToggle } from './PasswordVisibilityToggle.jsx';
import { PasswordStrengthMeter } from './PasswordStrengthMeter.jsx';
import { SubmitButton } from './SubmitButton.jsx';
import { AuthCard } from './AuthCard.jsx';

export default {
  title: 'Auth Components Test',
  component: {},
};

const Template = (args) => <div>{args.children}</div>;

export const Default = () => {
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');

  const user = new userEvent();

  return (
    <AuthCard gradient={true} title="Test Components">
      <FormField
        label="Email"
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        onBlur={() => {}}
        error={email ? null : 'Email is required'}
        name="email"
      />
      <FormField
        label="Password"
        type="password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        onBlur={() => {}}
        error={password.length < 6 ? 'Password too short' : null}
        name="password"
      />
      <PasswordVisibilityToggle
        visible={showPassword}
        onClick={() => setShowPassword(!showPassword)}
      />
      <PasswordStrengthMeter password={password} />
      <SubmitButton loading={isLoading} disabled={false}>
        Submit
      </SubmitButton>
      <button onClick={() => setIsLoading(true)}>Trigger Loading</button>
    </AuthCard>
  );
};