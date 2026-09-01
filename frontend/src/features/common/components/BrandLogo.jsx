const LOGO_SOURCES = {
  light: '/brand/eventiq-logo-light.svg',
  transparent: '/brand/eventiq-logo-transparent.svg',
};

const BrandLogo = ({ variant = 'light', className = '' }) => (
  <img
    className={`brand-logo ${className}`.trim()}
    src={LOGO_SOURCES[variant]}
    alt="eventIQ — Tickets, Events, Experiences"
  />
);

export default BrandLogo;
