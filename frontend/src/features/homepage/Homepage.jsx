import React from 'react';
import Header from './Header';
import Footer from './Footer';
import HeroSection from './sections/HeroSection';
import DiscoverySection from './sections/DiscoverySection';
import TrendingSection from './sections/TrendingSection';
import CategorySection from './sections/CategorySection';
import UpcomingEventsSection from './sections/UpcomingEventsSection';
import OrganizerCTASection from './sections/OrganizerCTASection';
import TrustSection from './sections/TrustSection';
import NewsletterSection from './sections/NewsletterSection';

const Homepage = () => {
  return (
    <div className="homepage">
      <Header />
      <main>
        <HeroSection />
        <DiscoverySection />
        <TrendingSection />
        <CategorySection />
        <UpcomingEventsSection />
        <OrganizerCTASection />
        <TrustSection />
        <NewsletterSection />
      </main>
      <Footer />
    </div>
  );
};

export default Homepage;
