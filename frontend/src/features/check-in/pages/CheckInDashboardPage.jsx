import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  CheckInStatsDisplay,
  CheckInQRScanner,
  CheckInSearchBar,
  CheckInNavigation,
} from '../components';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';
import EventSelector from '../../analytics/components/EventSelector';
import { api } from '../../../lib/api';
import '../../../styles/shared-pages.css';

const CHECKIN_FIRST_VISIT_KEY = "eventiq-checkin-first-visit";

const CheckInDashboardPage = () => {
  const [searchParams] = useSearchParams();
  const eventId = searchParams.get('eventId');
  const [eventDetails, setEventDetails] = useState(null);
  const [eventLoading, setEventLoading] = useState(false);
  const [eventError, setEventError] = useState('');
  const isOnline = useOfflineSyncStore((state) => state.isOnline);
  const queue = useOfflineSyncStore((state) => state.queue);
  const history = useOfflineSyncStore((state) => state.history);
  const isSyncing = useOfflineSyncStore((state) => state.isSyncing);
  const syncQueue = useOfflineSyncStore((state) => state.syncQueue);
  const clearSyncedHistory = useOfflineSyncStore((state) => state.clearSyncedHistory);
  // Mark first visit as completed after mount
  useEffect(() => {
    localStorage.setItem(CHECKIN_FIRST_VISIT_KEY, "true");
  }, []);
  const [showFirstTimeBanner, setShowFirstTimeBanner] = useState(() => {
    const hasVisited = localStorage.getItem(CHECKIN_FIRST_VISIT_KEY);
    return !hasVisited;
  });

  // Fetch event details for ended-status check and surfacing deleted/missing
  // events. A 404 (deleted or mistyped event) must disable scanning with a
  // clear message — staff should never scan into an event that doesn't exist.
  useEffect(() => {
    if (!eventId) {
      setEventDetails(null);
      setEventLoading(false);
      setEventError('');
      return;
    }
    setEventLoading(true);
    setEventError('');
    api.get(`/events/${eventId}`)
      .then((res) => setEventDetails(res.data?.data || res.data))
      .catch((err) => {
        setEventDetails(null);
        const status = err?.response?.status;
        setEventError(
          status === 404
            ? `Event not found — it may have been deleted or the ID is incorrect.`
            : status === 403
              ? `Access denied — you do not have permission to view this event.`
              : 'Failed to load event details. Please try again.'
        );
      })
      .finally(() => setEventLoading(false));
  }, [eventId]);

  return (
    <div className="spa-page">
      <div className="spa-container" style={{ maxWidth: '1280px' }}>
        
        {/* Connection Status Banner */}
        <div
          className="spa-card"
          style={{
            padding: '16px',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'flex-start',
            justifyContent: 'space-between',
            gap: '12px',
            marginBottom: '32px',
            background: isOnline ? '#f0fdf4' : '#fffbeb',
            borderColor: isOnline ? '#bbf7d0' : '#fde68a',
            color: isOnline ? '#16a34a' : '#d97706',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <span style={{ position: 'relative', display: 'inline-flex', width: '12px', height: '12px' }}>
              <span
                style={{
                  position: 'absolute',
                  display: 'inline-flex',
                  width: '100%',
                  height: '100%',
                  borderRadius: '50%',
                  opacity: 0.75,
                  background: isOnline ? '#16a34a' : '#d97706',
                  animation: 'spaPing 1.5s cubic-bezier(0, 0, 0.2, 1) infinite',
                }}
              />
              <span
                style={{
                  position: 'relative',
                  display: 'inline-flex',
                  width: '12px',
                  height: '12px',
                  borderRadius: '50%',
                  background: isOnline ? '#16a34a' : '#d97706',
                }}
              />
            </span>
            <div>
              <p style={{ fontSize: '14px', fontWeight: 800 }}>
                {isOnline ? 'Connection Status: Online Mode' : 'Connection Status: Offline Buffer Mode'}
              </p>
              <p style={{ fontSize: '12px', opacity: 0.85, marginTop: '2px' }}>
                {isOnline 
                  ? 'Real-time validations are synchronized instantly with the cloud backend.' 
                  : 'Scans are saved securely in local storage and will sync automatically upon reconnection.'}
              </p>
            </div>
          </div>
          
          {queue.length > 0 && isOnline && (
            <button
              onClick={syncQueue}
              disabled={isSyncing}
              className="spa-btn"
              style={{
                background: isSyncing ? '#94a3b8' : '#4f46e5',
                color: '#fff',
                fontSize: '12px',
                padding: '6px 16px',
              }}
            >
              {isSyncing ? (
                <>
                  <span
                    style={{
                      width: '12px',
                      height: '12px',
                      border: '2px solid #fff',
                      borderTopColor: 'transparent',
                      borderRadius: '50%',
                      animation: 'spaSpin 1s linear infinite',
                      display: 'inline-block',
                    }}
                  />
                  Syncing...
                </>
              ) : (
                <>🔄 Sync Now ({queue.length})</>
              )}
            </button>
          )}
        </div>

        {/* Dashboard Header */}
        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'flex-start',
            justifyContent: 'space-between',
            gap: '16px',
            marginBottom: '24px',
          }}
        >
          <div>
            <span
              style={{
                fontSize: '10px',
                fontWeight: 700,
                color: '#4f46e5',
                background: '#eef2ff',
                padding: '4px 10px',
                borderRadius: '999px',
                border: '1px solid #c7d2fe',
                textTransform: 'uppercase',
                letterSpacing: '0.05em',
                display: 'inline-block',
                marginBottom: '8px',
              }}
            >
              On-Site Venue Logistics
            </span>
            <h1 className="spa-page__title" style={{ fontSize: '1.875rem' }}>
              Ticket Check-In Desk
            </h1>
            <p className="spa-page__subtitle">
              Unified check-in desk — scan QR codes, search attendees, view stats, export records & audit history. Select an event above to begin.
            </p>
          </div>
          <div style={{ flexShrink: 0 }}>
            <EventSelector compact selectedEventId={eventId} />
          </div>
        </div>

        {/* Sticky active event banner */}
        {eventId && !eventError && (
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              padding: '8px 16px',
              background: '#eef2ff',
              border: '1px solid #c7d2fe',
              borderRadius: '12px',
              fontSize: '14px',
              color: '#3730a3',
              marginBottom: '24px',
            }}
          >
            <span
              style={{
                width: '8px',
                height: '8px',
                background: '#16a34a',
                borderRadius: '50%',
                animation: 'spaPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                display: 'inline-block',
              }}
            />
            <span style={{ fontWeight: 700 }}>
              {eventLoading ? 'Loading event...' : eventDetails?.name || eventDetails?.title || `Event #${eventId}`}
            </span>
            <span style={{ color: '#6366f1' }}>·</span>
            <Link to="/check-in" style={{ color: '#4f46e5', textDecoration: 'underline', fontSize: '12px' }}>
              Clear
            </Link>
            <span
              style={{ marginLeft: '16px', color: '#6366f1', cursor: 'pointer', fontSize: '12px' }}
              title="Works offline - scans save locally and sync when back online"
            >
              ⓘ
            </span>
          </div>
        )}
        {!eventId && (
          <div
            style={{
              padding: '8px 16px',
              background: '#fffbeb',
              border: '1px solid #fde68a',
              borderRadius: '12px',
              fontSize: '14px',
              color: '#d97706',
              marginBottom: '24px',
            }}
          >
            No event selected — choose an event above to filter stats, search & exports. Queue works offline regardless.
          </div>
        )}

        {/* Event ended guard */}
        {eventDetails && eventDetails.status === 'ended' && (
          <div
            style={{
              padding: '16px',
              background: '#f1f5f9',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              fontSize: '14px',
              color: '#475569',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              marginBottom: '24px',
            }}
          >
            <span>⚠️ This event has ended — check-ins are closed.</span>
            <Link to="/events" style={{ color: '#4f46e5', fontSize: '12px', fontWeight: 500 }}>
              Browse events →
            </Link>
          </div>
        )}

        {/* Event missing / not found guard */}
        {eventError && (
          <div
            style={{
              padding: '16px',
              background: '#fef2f2',
              border: '1px solid #fecaca',
              borderRadius: '12px',
              fontSize: '14px',
              color: '#dc2626',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              marginBottom: '24px',
            }}
            role="alert"
          >
            <span>⚠️ {eventError}</span>
            <Link
              to="/check-in"
              style={{ color: '#dc2626', textDecoration: 'underline', fontSize: '12px', fontWeight: 500, flexShrink: 0, marginLeft: '16px' }}
            >
              Choose a valid event →
            </Link>
          </div>
        )}

        <CheckInNavigation eventId={eventId} />

        {/* Metrics display */}
        <CheckInStatsDisplay total={150} checkedIn={35} />

        {/* Core Layout Grid — disabled when no event selected, event ended, or event not found */}
        <div
          className="spa-grid"
          style={{
            gridTemplateColumns: '1fr',
            gap: '32px',
            marginTop: '32px',
            ...(window.innerWidth >= 1024 ? { gridTemplateColumns: '2fr 1fr' } : {}),
            ...((!eventId || eventError || (eventDetails && eventDetails.status === 'ended'))
              ? { opacity: 0.5, pointerEvents: 'none' }
              : {}),
          }}
        >
          {/* Main scanner/manual input */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
            <CheckInQRScanner eventId={eventId ? Number(eventId) : null} />
            <CheckInSearchBar eventId={eventId ? Number(eventId) : null} />
          </div>

          {/* Sync logs and recent checks side panel */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
            
            {/* Pending Sync Queue — offline scans awaiting upload */}
            <div
              className="spa-card"
              style={{ padding: '20px', borderColor: '#e2e8f0' }}
            >
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: '16px',
                  borderBottom: '1px solid #f8fafc',
                  paddingBottom: '12px',
                }}
              >
                <h3
                  style={{
                    fontWeight: 700,
                    color: '#333',
                    fontSize: '14px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    margin: 0,
                  }}
                >
                  <span>📥 Pending Sync Queue</span>
                  <span
                    className="spa-badge spa-badge--warning"
                  >
                    {queue.length} awaiting upload
                  </span>
                </h3>
                <span style={{ fontSize: '10px', color: '#94a3b8' }}>offline scans</span>
              </div>

              {queue.length === 0 ? (
                <div className="spa-empty" style={{ fontSize: '12px' }}>
                  No pending offline scans in queue
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', maxHeight: '240px', overflowY: 'auto', paddingRight: '4px' }}>
                  {queue.map((item) => (
                    <div
                      key={item.id}
                      style={{
                        padding: '12px',
                        background: '#fffbeb',
                        border: '1px solid #fef3c7',
                        borderRadius: '12px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        fontSize: '12px',
                      }}
                    >
                      <div>
                        <span
                          style={{
                            fontFamily: 'monospace',
                            fontWeight: 700,
                            color: '#92400e',
                            display: 'block',
                          }}
                        >
                          {item.ticketCode}
                        </span>
                        <span style={{ fontSize: '10px', color: '#94a3b8' }}>
                          Scanned at {new Date(item.scannedAt).toLocaleTimeString()}
                        </span>
                      </div>
                      <span
                        className="spa-badge spa-badge--warning"
                        style={{ fontSize: '9px' }}
                      >
                        {item.status}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Recent Scans — synced & failed */}
            <div
              className="spa-card"
              style={{ padding: '20px', borderColor: '#e2e8f0' }}
            >
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: '16px',
                  borderBottom: '1px solid #f8fafc',
                  paddingBottom: '12px',
                }}
              >
                <h3 style={{ fontWeight: 700, color: '#333', fontSize: '14px', margin: 0 }}>
                  Recent Scans — synced & failed
                </h3>
                {history.length > 0 && (
                  <button
                    onClick={clearSyncedHistory}
                    style={{
                      fontSize: '10px',
                      color: '#94a3b8',
                      background: 'none',
                      border: 'none',
                      cursor: 'pointer',
                    }}
                  >
                    Clear Synced
                  </button>
                )}
              </div>

              {history.length === 0 ? (
                <div className="spa-empty">
                  <span style={{ fontSize: '24px', display: 'block', marginBottom: '8px', filter: 'grayscale(100%)' }}>📋</span>
                  <p style={{ fontSize: '12px', color: '#94a3b8' }}>No tickets processed in this session</p>
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', maxHeight: '384px', overflowY: 'auto', paddingRight: '4px' }}>
                  {history.map((item) => (
                    <div
                      key={item.id}
                      style={{
                        padding: '12px',
                        borderRadius: '12px',
                        border: '1px solid',
                        fontSize: '12px',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '6px',
                        background: item.status === 'synced' ? '#f8fafc' : '#fef2f2',
                        borderColor: item.status === 'synced' ? '#e2e8f0' : '#fecaca',
                        color: item.status === 'synced' ? '#333' : '#991b1b',
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                        <span
                          style={{
                            fontFamily: 'monospace',
                            fontWeight: 700,
                            color: '#333',
                          }}
                        >
                          {item.ticketCode}
                        </span>
                        <span
                          className={`spa-badge ${item.status === 'synced' ? 'spa-badge--success' : 'spa-badge--error'}`}
                        >
                          {item.status === 'synced' ? 'Synced' : 'Failed'}
                        </span>
                      </div>
                      
                      {item.error && (
                        <p
                          style={{
                            fontSize: '10px',
                            color: '#dc2626',
                            fontWeight: 500,
                            background: '#fef2f2',
                            padding: '8px',
                            borderRadius: '8px',
                            border: '1px solid #fecaca',
                            margin: 0,
                          }}
                        >
                          ⚠️ {item.error}
                        </p>
                      )}

                      <div
                        style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          fontSize: '9px',
                          color: '#94a3b8',
                        }}
                      >
                        <span>{new Date(item.scannedAt).toLocaleTimeString()}</span>
                        <span>Event ID: {item.eventId}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

          </div>
        </div>

      </div>
    </div>
  );
};

export default CheckInDashboardPage;
