import React from 'react';
import Icon from './Icon';

const QuickActions = ({ actions = [], onActionClick }) => {
  return (
    <section className="dashboard-section" aria-label="Quick actions">
      <h2 className="section-heading">Quick Actions</h2>
      <div className="quick-actions-grid">
        {actions.map((action) => {
          const content = (
            <>
              <div className="action-icon">
                <Icon name={action.icon} size={22} />
              </div>
              <div className="action-content">
                <h3 className="action-title">{action.title}</h3>
                <p className="action-description">{action.description}</p>
              </div>
              <Icon name="arrow-right" size={18} className="action-chevron" />
            </>
          );

          if (action.to) {
            return (
              <a
                key={action.to}
                href={action.to}
                className="action-card"
                onClick={() => onActionClick?.(action)}
              >
                {content}
              </a>
            );
          }

          return (
            <button
              key={action.title}
              type="button"
              className="action-card action-card-btn"
              onClick={() => onActionClick?.(action)}
            >
              {content}
            </button>
          );
        })}
      </div>
    </section>
  );
};

export default QuickActions;
