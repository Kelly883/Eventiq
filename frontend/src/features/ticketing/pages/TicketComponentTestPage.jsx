import React, { useState, useCallback } from 'react';
import TicketTierCard from '../../events/components/TicketTierCard';
import TicketTierForm from '../../events/components/TicketTierForm';
import PricingComparison from '../components/PricingComparison';
import EarlyBirdBadge from '../components/EarlyBirdBadge';
import BenefitsDisplay from '../components/BenefitsDisplay';
import './TicketComponentTestPage.css';

const SAMPLE_TIER_WITH_EARLY_BIRD = {
  id: '1',
  name: 'Early Bird Special',
  price: '50.00',
  earlyBirdPrice: '35.00',
  quantity: '100',
  startDate: '2026-01-01',
  endDate: '2026-12-31',
  limits: { min: 1, max: 4 },
  benefits: 'Includes access to all main stage sessions, networking events, and the expo hall. Early bird pricing available until March 31st.',
  image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&h=225&fit=crop'
};

const SAMPLE_TIER_NO_EARLY_BIRD = {
  id: '2',
  name: 'Regular',
  price: '75.00',
  earlyBirdPrice: null,
  quantity: '200',
  startDate: '2026-04-01',
  endDate: '2026-12-31',
  limits: { min: 1, max: 10 },
  benefits: 'Standard admission with access to all sessions and expo hall.',
  image: 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&h=225&fit=crop'
};

const SAMPLE_TIER_VIP = {
  id: '3',
  name: 'VIP',
  price: '199.00',
  earlyBirdPrice: '149.00',
  quantity: '50',
  startDate: '2026-01-01',
  endDate: '2026-12-31',
  limits: { min: 1, max: 2 },
  benefits: 'VIP access includes: Priority seating at all sessions, exclusive VIP lounge access, complimentary meals and beverages, meet & greet with speakers, premium swag bag, and reserved parking.',
  image: 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&h=225&fit=crop'
};

const LONG_BENEFITS = `This is a very long benefits description that should be truncated and show an expand button. It contains detailed information about all the perks and features included with this ticket tier. You get access to exclusive networking sessions with industry leaders, premium seating in the front rows, complimentary catered lunch each day, access to the speaker lounge for private conversations, a professional photo session with our event photographer, early access to the expo hall before it opens to the public, a dedicated concierge service throughout the event, complimentary access to all workshop sessions (normally $50 each), a premium swag bag with high-value items worth over $200, and invitation to the exclusive after-party with live entertainment.`;

const TicketComponentTestPage = () => {
  const [logs, setLogs] = useState([]);
  const [formTiers, setFormTiers] = useState([
    { ...SAMPLE_TIER_WITH_EARLY_BIRD, price: '50', earlyBirdPrice: '35', quantity: '100' },
    { ...SAMPLE_TIER_NO_EARLY_BIRD, price: '75', quantity: '200' },
  ]);
  const [showFormErrors, setShowFormErrors] = useState(false);
  const [pricingTiers] = useState([SAMPLE_TIER_WITH_EARLY_BIRD, SAMPLE_TIER_NO_EARLY_BIRD, SAMPLE_TIER_VIP]);
  const [loading, setLoading] = useState(false);

  const addLog = useCallback((message) => {
    setLogs((prev) => [...prev.slice(-19), `${new Date().toLocaleTimeString()} — ${message}`]);
  }, []);

  const handleFormChange = useCallback((updatedTiers) => {
    setFormTiers(updatedTiers);
    addLog(`Form updated: ${updatedTiers.length} tiers`);
  }, [addLog]);

  const handleFormSubmit = useCallback((e) => {
    e.preventDefault();
    addLog('Form submitted with: ' + JSON.stringify(formTiers.map(t => ({ name: t.name, price: t.price, earlyBirdPrice: t.earlyBirdPrice }))));
  }, [addLog, formTiers]);

  const testValidation = useCallback(() => {
    setFormTiers([
      { ...SAMPLE_TIER_WITH_EARLY_BIRD, price: '0', earlyBirdPrice: '50', quantity: '0', endDate: '2025-01-01' },
      { ...SAMPLE_TIER_NO_EARLY_BIRD, price: '-10', quantity: '-5' },
    ]);
    addLog('Set invalid data for validation testing');
  }, [addLog]);

  const resetForm = useCallback(() => {
    setFormTiers([
      { ...SAMPLE_TIER_WITH_EARLY_BIRD, price: '50', earlyBirdPrice: '35', quantity: '100' },
      { ...SAMPLE_TIER_NO_EARLY_BIRD, price: '75', quantity: '200' },
    ]);
    addLog('Form reset to valid data');
  }, [addLog]);

  return (
    <div className="test-page">
      <header className="test-header">
        <h1>Ticket Component Test Page</h1>
        <p>Test all 5 ticket components: TicketTierCard, TicketTierForm, PricingComparison, EarlyBirdBadge, BenefitsDisplay</p>
      </header>

      <main className="test-main">
        {/* Section 1: TicketTierCard */}
        <section className="test-section">
          <h2>1. TicketTierCard</h2>
          <div className="test-controls">
            <label>
              <input
                type="checkbox"
                checked={true}
                onChange={() => {}}
                disabled
              />
              Early Bird Badge Active (hardcoded in this test)
            </label>
            <label>
              <input
                type="checkbox"
                checked={false}
                onChange={() => setLoading(!loading)}
              />
              Loading State
            </label>
          </div>
          
          <div className="test-grid">
            <div className="test-card">
              <h3>With Early Bird Active</h3>
              <TicketTierCard
                tier={SAMPLE_TIER_WITH_EARLY_BIRD}
                index={0}
                onUpdate={() => {}}
                onRemove={() => {}}
                showEarlyBirdBadge={true}
                loading={loading}
                currencySymbol="$"
              />
            </div>
            
            <div className="test-card">
              <h3>Without Early Bird</h3>
              <TicketTierCard
                tier={SAMPLE_TIER_NO_EARLY_BIRD}
                index={1}
                onUpdate={() => {}}
                onRemove={() => {}}
                showEarlyBirdBadge={false}
                loading={loading}
                currencySymbol="$"
              />
            </div>

            <div className="test-card">
              <h3>Missing Image (Placeholder)</h3>
              <TicketTierCard
                tier={{ ...SAMPLE_TIER_WITH_EARLY_BIRD, image: null, name: 'No Image Tier' }}
                index={2}
                onUpdate={() => {}}
                onRemove={() => {}}
                showEarlyBirdBadge={true}
                loading={loading}
                currencySymbol="$"
              />
            </div>

            <div className="test-card">
              <h3>Null Dates</h3>
              <TicketTierCard
                tier={{ ...SAMPLE_TIER_WITH_EARLY_BIRD, startDate: null, endDate: null, name: 'No Dates Tier' }}
                index={3}
                onUpdate={() => {}}
                onRemove={() => {}}
                showEarlyBirdBadge={true}
                loading={loading}
                currencySymbol="$"
              />
            </div>
          </div>
        </section>

        {/* Section 2: TicketTierForm */}
        <section className="test-section">
          <h2>2. TicketTierForm - Validation Tests</h2>
          <div className="test-controls">
            <button onClick={testValidation} className="test-btn test-btn--danger">Load Invalid Data (Test Validation)</button>
            <button onClick={resetForm} className="test-btn">Reset to Valid Data</button>
            <label>
              <input
                type="checkbox"
                checked={loading}
                onChange={() => setLoading(!loading)}
              />
              Loading State
            </label>
          </div>
          
          <div className="test-form-wrapper">
            <TicketTierForm
              tiers={formTiers}
              onChange={handleFormChange}
              disabled={false}
              loading={loading}
              currencySymbol="$"
            />
          </div>
          
          <div className="test-form-actions">
            <button onClick={handleFormSubmit} className="test-btn test-btn--primary">Submit Form (Check Console)</button>
          </div>
        </section>

        {/* Section 3: PricingComparison */}
        <section className="test-section">
          <h2>3. PricingComparison (3 Tiers Side-by-Side)</h2>
          <div className="test-controls">
            <label>
              <input
                type="checkbox"
                checked={loading}
                onChange={() => setLoading(!loading)}
              />
              Loading State
            </label>
          </div>
          <div className="test-comparison-wrapper">
            <PricingComparison
              tiers={pricingTiers}
              loading={loading}
              currencySymbol="$"
            />
          </div>
        </section>

        {/* Section 4: EarlyBirdBadge */}
        <section className="test-section">
          <h2>4. EarlyBirdBadge - Discount Calculation</h2>
          <div className="test-controls">
            <label>
              <input
                type="checkbox"
                checked={loading}
                onChange={() => setLoading(!loading)}
              />
              Loading State
            </label>
          </div>
          
          <div className="test-badge-grid">
            <div className="test-badge-item">
              <h4>30% Off ($50 → $35)</h4>
              <EarlyBirdBadge regularPrice={50} earlyBirdPrice={35} currencySymbol="$" loading={loading} />
            </div>
            <div className="test-badge-item">
              <h4>25% Off ($75 → $56.25)</h4>
              <EarlyBirdBadge regularPrice={75} earlyBirdPrice={56.25} currencySymbol="$" loading={loading} />
            </div>
            <div className="test-badge-item">
              <h4>50% Off ($100 → $50)</h4>
              <EarlyBirdBadge regularPrice={100} earlyBirdPrice={50} currencySymbol="$" loading={loading} />
            </div>
            <div className="test-badge-item">
              <h4>Invalid (early &gt;= regular)</h4>
              <EarlyBirdBadge regularPrice={50} earlyBirdPrice={50} currencySymbol="$" loading={loading} />
            </div>
            <div className="test-badge-item">
              <h4>Invalid (early &gt; regular)</h4>
              <EarlyBirdBadge regularPrice={50} earlyBirdPrice={60} currencySymbol="$" loading={loading} />
            </div>
            <div className="test-badge-item">
              <h4>Invalid (zero price)</h4>
              <EarlyBirdBadge regularPrice={0} earlyBirdPrice={0} currencySymbol="$" loading={loading} />
            </div>
          </div>
        </section>

        {/* Section 5: BenefitsDisplay */}
        <section className="test-section">
          <h2>5. BenefitsDisplay - Truncation & Expand</h2>
          <div className="test-controls">
            <label>
              <input
                type="checkbox"
                checked={loading}
                onChange={() => setLoading(!loading)}
              />
              Loading State
            </label>
          </div>
          
          <div className="test-benefits-grid">
            <div className="test-card">
              <h3>Short Benefits (No Expand)</h3>
              <BenefitsDisplay benefits="Access to all sessions and expo hall." showExpand={true} loading={loading} />
            </div>
            
            <div className="test-card">
              <h3>Long Benefits (Truncated + Expand)</h3>
              <BenefitsDisplay benefits={LONG_BENEFITS} showExpand={true} maxLength={120} loading={loading} />
            </div>
            
            <div className="test-card">
              <h3>Very Long Benefits (200 char limit)</h3>
              <BenefitsDisplay benefits={LONG_BENEFITS} showExpand={true} maxLength={200} loading={loading} />
            </div>
            
            <div className="test-card">
              <h3>Empty Benefits</h3>
              <BenefitsDisplay benefits="" showExpand={true} loading={loading} />
            </div>
          </div>
        </section>

        {/* Section 6: Responsive Test */}
        <section className="test-section">
          <h2>6. Responsive Layout Test</h2>
          <p className="test-note">Resize the browser window to test mobile (&lt;768px) and desktop (&gt;=768px) layouts.</p>
          <div className="test-grid">
            <div className="test-card">
              <h3>TicketTierCard on Mobile</h3>
              <p>Single column layout, all fields stacked</p>
              <TicketTierCard
                tier={SAMPLE_TIER_WITH_EARLY_BIRD}
                index={10}
                onUpdate={() => {}}
                onRemove={() => {}}
                showEarlyBirdBadge={true}
                currencySymbol="$"
              />
            </div>
            <div className="test-card">
              <h3>PricingComparison Mobile</h3>
              <p>Single column, stacked cards</p>
              <PricingComparison
                tiers={[SAMPLE_TIER_WITH_EARLY_BIRD, SAMPLE_TIER_NO_EARLY_BIRD]}
                currencySymbol="$"
              />
            </div>
          </div>
        </section>

        {/* Event Log */}
        <section className="test-section test-log-section">
          <h2>Event Log</h2>
          <div className="test-log">
            {logs.length === 0 && <span className="test-log-empty">Interact with components to see events…</span>}
            {logs.map((log, i) => (
              <div key={i} className="test-log-entry">{log}</div>
            ))}
          </div>
        </section>
      </main>
    </div>
  );
};

export default TicketComponentTestPage;