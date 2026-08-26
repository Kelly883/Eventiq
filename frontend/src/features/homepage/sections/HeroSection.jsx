import React from 'react';
import { Link } from 'react-router-dom';

const HeroSection = () => {
  return (
    <section className="hero">
      <div className="hero-background">
        <div className="hero-overlay" />
      </div>
      <div className="hero-content">
        <h1 className="hero-headline">
          Unforgettable moments<br />wait for you.
        </h1>
        <p className="hero-subheadline">
          Discover concerts, festivals, comedy, sports, conferences and experiences happening around you.
        </p>
      </div>
    </section>
  );
};

export default HeroSection;
