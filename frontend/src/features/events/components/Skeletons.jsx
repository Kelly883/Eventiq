import React from 'react';

const skeletonBase = 'bg-slate-200 animate-pulse rounded';

const Skeleton = ({
  variant = 'text',
  className = '',
  count = 1,
  width,
  height,
}) => {
  const getStyle = () => {
    const style = {};
    if (width) style.width = width;
    if (height) style.height = height;
    return style;
  };

  if (variant === 'card') {
    return (
      <div className={`p-5 bg-white border border-slate-100 rounded-xl space-y-4 shadow-sm ${className}`}>
        <div className="flex items-center gap-3">
          <div className={`${skeletonBase} h-10 w-10 rounded-full`} />
          <div className="space-y-2 flex-1">
            <div className={`${skeletonBase} h-4 w-1/3`} />
            <div className={`${skeletonBase} h-3 w-1/4`} />
          </div>
        </div>
        <div className="space-y-2 pt-2">
          <div className={`${skeletonBase} h-3 w-full`} />
          <div className={`${skeletonBase} h-3 w-5/6`} />
          <div className={`${skeletonBase} h-3 w-2/3`} />
        </div>
      </div>
    );
  }

  if (variant === 'list') {
    return (
      <div className={`space-y-3 ${className}`}>
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-xl shadow-sm">
            <div className="flex items-center gap-3 flex-1">
              <div className={`${skeletonBase} h-8 w-8 rounded-lg`} />
              <div className="space-y-1.5 flex-1">
                <div className={`${skeletonBase} h-3.5 w-1/4`} />
                <div className={`${skeletonBase} h-2.5 w-1/6`} />
              </div>
            </div>
            <div className={`${skeletonBase} h-6 w-16 rounded`} />
          </div>
        ))}
      </div>
    );
  }

  if (variant === 'title') {
    return (
      <div
        className={`skeleton-title ${className || ''}`
        style={getStyle()}
      } />
    );
  }

  if (variant === 'title-lg') {
    return (
      <div
        className={`skeleton-title skeleton-title-lg ${className || ''}`
        style={{
          ...getStyle(),
          marginBottom: '8px',
          width: 'calc(100% - 40px)',
        }}
      />
    );
  }

  if (variant === 'text-sm') {
    return (
      <div
        className={`skeleton-text skeleton-text-sm ${className || ''}`
        style={getStyle()}
      />
    );
  }

  if (variant === 'text-xs') {
    return (
      <div
        className={`skeleton-text skeleton-text-xs ${className || ''}`}
        style={getStyle()}
      />
    );
  }

  if (variant === 'circle') {
    return (
      <div
        className={`skeleton ${className || ''}`}
        style={getStyle()}
        style={{
          ...getStyle(),
          borderRadius: '50%',
        }}
      />
    );
  }

  if (variant === 'rect') {
    return (
      <div
        className={`skeleton ${className || ''}`}
        style={getStyle()}
      />
    );
  }

  if (variant === 'card-lg') {
    return (
      <div
        className={`p-4 bg-white border border-slate-100 rounded-lg shadow-sm ${className}`}
        style={getStyle()}
      />
    );
  }

  if (variant === 'image-lg') {
    return (
      <div
        className={`skeleton-image skeleton-image-lg ${className || ''}`}
        style={getStyle()}
        style={{
          ...getStyle(),
          borderRadius: '8px',
          height: '120px',
          width: 'full',
        }}
      />
    );
  }

  if (variant === 'image') {
    return (
      <div
        className={`skeleton-image ${className || ''}`}
        style={getStyle()}
        style={{
          ...getStyle(),
          borderRadius: '8px',
          height: '80px',
          width: 'full',
        }}
      />
    );
  }

  if (variant === 'title') {
    return (
      <div
        className={`skeleton-title ${className || ''}`}
        style={getStyle()}
      />
    );
  }

  if (variant === 'title-lg') {
    return (
      <div
        className={`skeleton-title skeleton-title-lg ${className || ''}`}
        style={{
          ...getStyle(),
          marginBottom: '8px',
          width: 'calc(100% - 40px)',
        }}
      />
    );
  }

  if (variant === 'text-sm') {
    return (
      <div
        className={`skeleton-text skeleton-text-sm ${className || ''}`}
        style={getStyle()}
      />
    );
  }

  if (variant === 'text-xs') {
    return (
      <div
        className={`skeleton-text skeleton-text-xs ${className || ''}`}
        style={getStyle()}
      />
    );
  }

  return (
    <div className="space-y-2">
      {Array.from({ length: count }).map((_, i) => (
        <div
          key={i}
          className={`${baseClass} ${
            variant === 'circle' ? 'rounded-full' : ''
          } ${className}`}
          style={getStyle()}
        />
      ))}
    </div>
  );
};

const SkeletonWithImage = ({
  width,
  height,
  radius = '8px',
  className = '',
}) => {
  const baseClass = 'skeleton-image bg-slate-200 animate-pulse rounded';
  return (
    <div
      className={`${baseClass} ${className || ''}`}
      style={{
        width,
        height,
        borderRadius: radius,
      }}
    />
  );
};

const SkeletonTitle = ({
  width,
  height,
  marginBottom = '8px',
  className = '',
}) => {
  const baseClass = 'skeleton-title bg-slate-200 animate-pulse rounded';
  return (
    <div
      className={`${baseClass} ${className || ''}`}
      style={{
        ...(width ? { width } : {}),
        ...(height ? { height } : {}),
        marginBottom,
      }}
    />
  );
};

const SkeletonText = ({
  width,
  height,
  className = '',
  size = 'text',
}) => {
  const sizeMap = {
    text: 'skeleton-text bg-slate-200 animate-pulse rounded',
    'text-sm': 'skeleton-text skeleton-text-sm bg-slate-200 animate-pulse rounded',
    'text-xs': 'skeleton-text skeleton-text-xs bg-slate-200 animate-pulse rounded',
  };
  const combinedClass = (sizeMap[size] || sizeMap.text) + ' ' + (className || '');

  return (
    <div className={combinedClass} style={{ ...(width ? { width } : {}), ...(height ? { height } : {}) }} />
  );
};

export { Skeleton, SkeletonWithImage, SkeletonTitle, SkeletonText };
export default Skeleton;