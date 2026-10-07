import React, { useCallback, useState, useEffect, useRef } from 'react';
import ReactCrop, { cropToCanvas } from 'react-image-crop';
import 'react-image-crop/dist/ReactCrop.css';
import './OrganizerProfileComponents.css';

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

const centerAspectCrop = (mediaWidth, mediaHeight, aspect = 1) => {
  const size = Math.min(mediaWidth, mediaHeight) * 0.8;
  const x = (mediaWidth - size) / 2;
  const y = (mediaHeight - size) / 2;
  return { unit: 'px', x, y, width: size, height: size };
};

const AvatarUploadField = ({ currentAvatarUrl, onUpload, isLoading = false }) => {
  const [preview, setPreview] = useState(currentAvatarUrl || null);
  const [error, setError] = useState('');
  const [dragActive, setDragActive] = useState(false);
  const [dragCounter, setDragCounter] = useState(0);
  const [cropModalSrc, setCropModalSrc] = useState(null);
  const [crop, setCrop] = useState(null);
  const imgRef = useRef(null);
  const canvasRef = useRef(null);

  useEffect(() => {
    return () => {
      if (preview && preview !== currentAvatarUrl) {
        URL.revokeObjectURL(preview);
      }
      if (cropModalSrc && cropModalSrc !== currentAvatarUrl) {
        URL.revokeObjectURL(cropModalSrc);
      }
    };
  }, [preview, currentAvatarUrl, cropModalSrc]);

  const processFile = useCallback((file) => {
    setError('');

    if (!file) return;

    if (!ACCEPTED_TYPES.includes(file.type)) {
      setError('Invalid file type. Please upload an image (JPEG, PNG, WebP, GIF).');
      return;
    }

    if (file.size > MAX_FILE_SIZE) {
      setError('File too large. Maximum size is 5MB.');
      return;
    }

    const objectUrl = URL.createObjectURL(file);
    setCropModalSrc(objectUrl);
    setCrop(null);
  }, []);

  const handleDragOver = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!isLoading) {
      setDragActive(true);
    }
  };

  const handleDragEnter = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!isLoading) {
      setDragCounter((prev) => prev + 1);
      setDragActive(true);
    }
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragCounter((prev) => {
      const next = prev - 1;
      if (next <= 0) {
        setDragActive(false);
        return 0;
      }
      return next;
    });
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    setDragCounter(0);

    if (isLoading) return;

    const file = e.dataTransfer?.files?.[0];
    processFile(file);
  };

  const handleFileSelect = (e) => {
    const file = e.target.files?.[0];
    processFile(file);
  };

  const onImageLoad = (e) => {
    const { width, height } = e.currentTarget;
    setCrop(centerAspectCrop(width, height, 1));
  };

  const handleConfirmCrop = async () => {
    if (!crop || !imgRef.current || !canvasRef.current) return;

    try {
      await cropToCanvas(imgRef.current, canvasRef.current, crop);
      canvasRef.current.toBlob((blob) => {
        if (!blob) {
          setError('Failed to process image. Please try again.');
          return;
        }
        if (preview && preview !== currentAvatarUrl) {
          URL.revokeObjectURL(preview);
        }
        if (cropModalSrc && cropModalSrc !== currentAvatarUrl) {
          URL.revokeObjectURL(cropModalSrc);
        }
        const croppedFile = new File([blob], 'avatar.jpg', { type: blob.type || 'image/jpeg' });
        const previewUrl = URL.createObjectURL(blob);
        setPreview(previewUrl);
        onUpload?.(croppedFile);
        setCropModalSrc(null);
        setCrop(null);
      }, 'image/jpeg', 0.9);
    } catch {
      setError('Failed to crop image. Please try again.');
    }
  };

  const handleCancelCrop = () => {
    if (cropModalSrc) {
      URL.revokeObjectURL(cropModalSrc);
    }
    setCropModalSrc(null);
    setCrop(null);
  };

  return (
    <div className="avatar-upload">
      <div
        className={`avatar-upload__dropzone ${dragActive ? 'avatar-upload__dropzone--active' : ''} ${isLoading ? 'avatar-upload__dropzone--disabled' : ''}`}
        onDragOver={handleDragOver}
        onDragEnter={handleDragEnter}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        role="button"
        tabIndex={0}
        aria-label="Upload avatar image"
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            document.getElementById('avatar-file-input')?.click();
          }
        }}
      >
        <div className="avatar-upload__preview-wrapper">
          {preview ? (
            <img src={preview} alt="Avatar preview" className="avatar-upload__preview" />
          ) : (
            <div className="avatar-upload__preview-placeholder">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" y1="3" x2="12" y2="15" />
              </svg>
            </div>
          )}
        </div>
        <div className="avatar-upload__content">
          <p className="avatar-upload__text">
            {dragActive ? 'Drop your image here' : 'Drag & drop an image, or click to browse'}
          </p>
          <p className="avatar-upload__hint">JPEG, PNG, WebP or GIF. Max 5MB.</p>
        </div>
        <input
          id="avatar-file-input"
          type="file"
          accept="image/*"
          onChange={handleFileSelect}
          className="avatar-upload__input"
          disabled={isLoading}
        />
      </div>
      {isLoading && (
        <div className="avatar-upload__progress" role="status" aria-label="Uploading avatar">
          <div className="avatar-upload__progress-bar" />
          <span className="avatar-upload__progress-text">Uploading…</span>
        </div>
      )}
      {error && (
        <p className="avatar-upload__error" role="alert">
          {error}
        </p>
      )}

      {cropModalSrc && (
        <div className="avatar-upload__crop-modal" role="dialog" aria-modal="true" aria-label="Crop avatar">
          <div className="avatar-upload__crop-content">
            <ReactCrop
              crop={crop}
              onChange={setCrop}
              aspect={1}
              circularCrop
              className="avatar-upload__crop"
            >
              <img
                ref={imgRef}
                src={cropModalSrc}
                alt="Crop preview"
                onLoad={onImageLoad}
              />
            </ReactCrop>
            <canvas ref={canvasRef} style={{ display: 'none' }} />
            <div className="avatar-upload__crop-actions">
              <button type="button" onClick={handleCancelCrop} className="avatar-upload__crop-cancel">Cancel</button>
              <button type="button" onClick={handleConfirmCrop} className="avatar-upload__crop-confirm">Confirm Crop</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AvatarUploadField;
