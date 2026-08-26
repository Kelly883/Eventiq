import React from 'react';
import Header from './Header';
import Footer from './Footer';
import HeroSection from './sections/HeroSection';
import SearchSection from './sections/SearchSection';
import QuickFilters from './sections/QuickFilters';
import TrendingSection from './sections/TrendingSection';
import CategorySection from './sections/CategorySection';
import UpcomingEventsSection from './sections/UpcomingEventsSection';
import StorySection from './sections/StorySection';
import TrustSection from './sections/TrustSection';
import OrganizerCTASection from './sections/OrganizerCTASection';
import NewsletterSection from './sections/NewsletterSection';

const Homepage = () => {
  return (
    <div className="homepage">
      <Header />
      <main>
        <HeroSection />
        <SearchSection />
        <QuickFilters />
        <TrendingSection />
        <CategorySection />
        <UpcomingEventsSection />
        <StorySection />
        <TrustSection />
        <OrganizerCTASection />
        <NewsletterSection />
      </main>
      <Footer />
    </div>
  );
};

export default Homepage;
