import React from 'react';
import Icon from './Icon';

const GettingStarted = ({ steps = [], onDismiss }) => {
  const completedCount = steps.filter((s) => s.completed).length;
  const totalCount = steps.length;
  const progressPercent = totalCount > 0 ? (completedCount / totalCount) * 100 : 0;

  return (
    <section className="dashboard-section" aria-label="Getting started">
      <div className="onboarding-card">
        <div className="onboarding-header">
          <h2 className="section-heading">Getting Started</h2>
          {onDismiss && (
            <button
              type="button"
              className="onboarding-dismiss"
              onClick={onDismiss}
              aria-label="Dismiss getting started"
            >
              <Icon name="close" size={18} />
            </button>
          )}
        </div>
        <div className="onboarding-progress">
          <div className="onboarding-progress-bar">
            <div
              className="onboarding-progress-fill"
              style={{ width: `${progressPercent}%` }}
            />
          </div>
          <span className="onboarding-progress-text">
            {completedCount} of {totalCount} completed
            </span>
        </div>
        <ul className="onboarding-steps">
          {steps.map((step, index) => (
            <li
              key={index}
              className={`onboarding-step ${step.completed ? 'completed' : ''}`}
            >
              <span className="step-icon">
                {step.completed ? (
                  <Icon name="check-circle" size={20} />
                ) : (
                  <span className="step-dot" aria-hidden="true" />
                )}
              </span>
              <div className="step-content">
                <h3 className="step-title">{step.title}</h3>
                <p className="step-description">{step.description}</p>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
};

export default GettingStarted;
