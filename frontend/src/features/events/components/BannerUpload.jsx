import React, { useState, useEffect, useRef } from 'react';

const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

const BannerUpload = ({ value, onChange, error, onErrorClear, disabled = false }) => {
  const [preview, setPreview] = useState(value || '');
  const [internalError, setInternalError] = useState('');
  const fileInputRef = useRef(null);

  useEffect(() => {
    setPreview(value || '');
  }, [value]);

  useEffect(() => {
    return () => {
      if (preview && preview.startsWith('blob:')) {
        URL.revokeObjectURL(preview);
      }
    };
  }, [preview]);

  const processFile = (file) => {
    setInternalError('');

    if (!file) return;

    if (!ACCEPTED_TYPES.includes(file.type) && !['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(file.name.split('.').pop().toLowerCase())) {
      const msg = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
      setInternalError(msg);
      onErrorClear?.();
      return;
    }

    if (file.size > MAX_FILE_SIZE) {
      const msg = 'File too large. Maximum size is 5MB.';
      setInternalError(msg);
      onErrorClear?.();
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    if (preview && preview.startsWith('blob:')) {
      URL.revokeObjectURL(preview);
    }
    setPreview(objectUrl);
    onChange?.(file, objectUrl);
  };

  const handleChange = (e) => {
    const file = e.target.files?.[0];
    processFile(file);
    e.target.value = '';
  };

  const handleRemove = () => {
    if (preview && preview.startsWith('blob:')) {
      URL.revokeObjectURL(preview);
    }
    setPreview('');
    onChange?.(null, '');
  };

  const displayError = error || internalError;

  return (
    <div className="banner-upload">
      {preview ? (
        <div className="banner-upload__preview">
          <img src={preview} alt="Banner preview" className="banner-upload__image" />
          <button type="button" onClick={handleRemove} className="banner-upload__remove" disabled={disabled}>
            Replace
          </button>
        </div>
      ) : (
        <label className="banner-upload__dropzone">
          <input
            ref={fileInputRef}
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            onChange={handleChange}
            disabled={disabled}
            className="banner-upload__input"
          />
          <div className="banner-upload__placeholder">
            <span className="banner-upload__icon" aria-hidden="true">📷</span>
            <p className="banner-upload__text">Click to upload banner</p>
            <p className="banner-upload__hint">1200×628 JPG/PNG/GIF/WEBP, max 5MB</p>
          </div>
        </label>
      )}

      {displayError && <p className="banner-upload__error" role="alert">{displayError}</p>}
    </div>
  );
};

export default BannerUpload;
