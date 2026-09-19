import React from 'react';
import Icon from './Icon';

const RecentTickets = ({ tickets = [], loading = false, onViewAll }) => {
  if (loading) {
    return (
      <section className="dashboard-section" aria-label="Recent tickets">
        <h2 className="section-heading">Recent Tickets</h2>
        <div className="tickets-list">
          {[1, 2].map((i) => (
            <div key={i} className="ticket-card-skeleton" aria-hidden="true" />
          ))}
        </div>
      </section>
    );
  }

  if (!tickets || tickets.length === 0) {
    return (
      <section className="dashboard-section" aria-label="Recent tickets">
        <h2 className="section-heading">Recent Tickets</h2>
        <div className="empty-state empty-state-sm">
          <Icon name="ticket" size={40} className="empty-state-icon" />
          <h3 className="empty-state-title">No tickets yet</h3>
          <p className="empty-state-text">
            Your purchased tickets will appear here once you book an event.
          </p>
        </div>
      </section>
    );
  }

  return (
    <section className="dashboard-section" aria-label="Recent tickets">
      <h2 className="section-heading">Recent Tickets</h2>
      <div className="tickets-list">
        {tickets.map((ticket) => (
          <article key={ticket.id} className="ticket-card">
            <div className="ticket-card-row">
              <div className="ticket-info">
                <h3 className="ticket-title">{ticket.eventTitle}</h3>
                <div className="ticket-meta">
                  <span className="ticket-meta-item">
                    <Icon name="calendar" size={13} />
                    <time dateTime={ticket.eventDate}>
                      {new Date(ticket.eventDate).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                      })}
                    </time>
                  </span>
                  <span className="ticket-meta-item ticket-reference">
                    #{ticket.reference || ticket.id?.slice(-8)}
                  </span>
                </div>
              </div>
              <span className={`status-badge status-${ticket.status || 'confirmed'}`}>
                {ticket.status === 'confirmed' ? 'Confirmed' : `Status`}
              </span>
            </div>
          </article>
        ))}
      </div>
      {tickets.length > 0 && onViewAll && (
        <button type="button" className="btn-text view-all-btn" onClick={onViewAll}>
          View All Tickets
          <Icon name="arrow-right" size={16} />
        </button>
      )}
    </section>
  );
};

export default RecentTickets;
