import React from 'react';
import { Link } from 'react-router-dom';

const StorySection = () => {
  return (
    <section className="story-section">
      <div className="story-background">
        <div className="story-overlay" />
      </div>
      <div className="story-content">
        <h2 className="story-headline">
          Some experiences<br />are meant to be lived.
        </h2>
        <p className="story-subheadline">
          Find something worth showing up for.
        </p>
        <Link to="/events" className="btn-explore">
          Explore events
        </Link>
      </div>
    </section>
  );
};

export default StorySection;
