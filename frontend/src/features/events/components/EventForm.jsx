import React, { useMemo } from 'react';

const EventForm = ({ form, tiers, bannerFile, bannerPreview, errors, saving, onFieldChange, onBlur, onTierChange, onTierRemove, onTierAdd, onBannerChange, onBannerRemove, onSubmit, submitLabel, children }) => {
  const characterCount = form.description?.length ?? 0;

  return (
    <form onSubmit={onSubmit} noValidate>
      {children}
    </form>
  );
};

export default EventForm;
