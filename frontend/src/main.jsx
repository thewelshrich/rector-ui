import React from 'react';
import ReactDOM from 'react-dom/client';
import { useEffect, useState } from 'react';
import './styles.css';

function App() {
  const [health, setHealth] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;

    fetch('/api/health')
      .then((response) => response.json())
      .then((payload) => {
        if (active) {
          setHealth(payload);
        }
      })
      .catch((requestError) => {
        if (active) {
          setError(requestError.message || 'Unable to load health status.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="app-shell">
      <header className="hero">
        <p className="eyebrow">Localhost upgrade workflow</p>
        <h1>Rector UI</h1>
        <p className="lede">
          A browser-based scaffold for reviewing Rector-driven modernization work!
        </p>
      </header>

      <section className="status-card">
        <h2>Server Status</h2>
        {error ? <p className="error">{error}</p> : null}
        {health ? (
          <dl className="status-grid">
            <div>
              <dt>Status</dt>
              <dd>{health.status}</dd>
            </div>
            <div>
              <dt>PHP</dt>
              <dd>{health.phpVersion}</dd>
            </div>
            <div>
              <dt>Package</dt>
              <dd>{health.appVersion}</dd>
            </div>
          </dl>
        ) : (
          <p>Checking local server health...</p>
        )}
      </section>

      <main className="placeholder-grid">
        <section>
          <h2>Repository</h2>
          <p>Project selection and runtime detection will live here.</p>
        </section>
        <section>
          <h2>Analysis</h2>
          <p>Rector dry-run orchestration will be added after the scaffold milestone.</p>
        </section>
        <section>
          <h2>Review</h2>
          <p>Diff browsing and accept/reject flows will be layered on next.</p>
        </section>
      </main>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
