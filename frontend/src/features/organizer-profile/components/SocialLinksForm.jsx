import React, { useState, useEffect } from 'react';
import './OrganizerProfileComponents.css';

const PLATFORMS = [
  { key: 'twitter', label: 'Twitter / X', placeholder: 'https://x.com/username' },
  { key: 'instagram', label: 'Instagram', placeholder: 'https://instagram.com/username' },
  { key: 'linkedin', label: 'LinkedIn', placeholder: 'https://linkedin.com/in/username' },
  { key: 'youtube', label: 'YouTube', placeholder: 'https://youtube.com/@channel' },
];

const isValidUrl = (value) => {
  if (!value) return true;
  try {
    const url = new URL(value);
    return ['http:', 'https:'].includes(url.protocol);
  } catch {
    return false;
  }
};

const SocialLinksForm = ({ socialLinks = {}, onChange }) => {
  const [values, setValues] = useState({
    twitter: socialLinks.twitter || '',
    instagram: socialLinks.instagram || '',
    linkedin: socialLinks.linkedin || '',
    youtube: socialLinks.youtube || '',
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    setValues({
      twitter: socialLinks.twitter || '',
      instagram: socialLinks.instagram || '',
      linkedin: socialLinks.linkedin || '',
      youtube: socialLinks.youtube || '',
    });
  }, [socialLinks]);

  const handleChange = (key, value) => {
    const updated = { ...values, [key]: value };
    setValues(updated);

    if (value && !isValidUrl(value)) {
      setErrors((prev) => ({ ...prev, [key]: 'Please enter a valid URL starting with http(s)://' }));
    } else {
      setErrors((prev) => {
        const next = { ...prev };
        delete next[key];
        return next;
      });
    }

    onChange?.(updated);
  };

  return (
    <div className="social-links-form">
      {PLATFORMS.map(({ key, label, placeholder }) => (
        <div key={key} className="social-links-form__field">
          <label htmlFor={`social-${key}`} className="social-links-form__label">
            {label}
          </label>
          <input
            id={`social-${key}`}
            type="url"
            value={values[key]}
            onChange={(e) => handleChange(key, e.target.value)}
            onBlur={() => {
              if (values[key] && !isValidUrl(values[key])) {
                setErrors((prev) => ({ ...prev, [key]: 'Please enter a valid URL starting with http(s)://' }));
              }
            }}
            placeholder={placeholder}
            className={`social-links-form__input ${errors[key] ? 'social-links-form__input--error' : ''}`}
            aria-invalid={Boolean(errors[key])}
            aria-errormessage={errors[key] ? `social-${key}-error` : undefined}
          />
          {errors[key] && (
            <p id={`social-${key}-error`} className="social-links-form__error" role="alert">
              {errors[key]}
            </p>
          )}
        </div>
      ))}
    </div>
  );
};

export default SocialLinksForm;
