import React, {useEffect, useState} from 'react';
import ReactDOM from 'react-dom/client';
import {Button} from '@/components/ui/button';
import './styles.css';

function App() {
  const [health, setHealth] = useState(null);
  const [healthError, setHealthError] = useState('');
  const [project, setProject] = useState(null);
  const [projectError, setProjectError] = useState('');
  const [analysis, setAnalysis] = useState(null);
  const [analysisError, setAnalysisError] = useState('');
  const [analysisLoading, setAnalysisLoading] = useState(false);

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
          setHealthError(requestError.message || 'Unable to load health status.');
        }
      });

    fetch('/api/project')
      .then((response) => response.json())
      .then((payload) => {
        if (active) {
          setProject(payload);
        }
      })
      .catch((requestError) => {
        if (active) {
          setProjectError(requestError.message || 'Unable to load project context.');
        }
      });

    return () => {
      active = false;
    };
  }, []);

  function runAnalysis() {
    setAnalysisLoading(true);
    setAnalysisError('');

    fetch('/api/analysis', {
      method: 'POST'
    })
      .then((response) => response.json())
      .then((payload) => {
        setAnalysis(payload);
      })
      .catch((requestError) => {
        setAnalysisError(requestError.message || 'Unable to run Rector dry-run.');
      })
      .finally(() => {
        setAnalysisLoading(false);
      });
  }

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
        {healthError ? <p className="error">{healthError}</p> : null}
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
          {projectError ? <p className="error">{projectError}</p> : null}
          {project ? (
            <dl className="project-grid">
              <div className="project-grid-full">
                <dt>Target Path</dt>
                <dd className="path-value">{project.path}</dd>
              </div>
              <div>
                <dt>Git</dt>
                <dd className={statusClass(project.isGitRepo)}>
                  {project.isGitRepo ? `Repository (${project.gitStatus})` : 'Not a git repository'}
                </dd>
              </div>
              <div>
                <dt>composer.json</dt>
                <dd className={statusClass(project.hasComposerJson)}>
                  {project.hasComposerJson ? 'Found' : 'Missing'}
                </dd>
              </div>
              <div>
                <dt>Rector Binary</dt>
                <dd className={statusClass(project.hasRectorBinary)}>
                  {project.hasRectorBinary ? 'Found' : 'Missing'}
                </dd>
              </div>
              <div className="project-grid-full">
                <dt>Rector Config</dt>
                <dd className={statusClass(project.hasRectorConfig)}>
                  {project.hasRectorConfig
                    ? `Found at ${project.rectorConfigPath}`
                    : 'No rector.php or rector.php.dist found'}
                </dd>
              </div>
            </dl>
          ) : (
            <p>Detecting project readiness...</p>
          )}
        </section>
        <section>
          <h2>Analysis</h2>
          {project && !project.hasRectorAnalysis ? (
            <div className="analysis-state">
              <p className="status-missing">
                Analysis is unavailable until this project has both a Rector config and a local
                `vendor/bin/rector` binary.
              </p>
              <Button type="button" className="action-button" disabled>
                Run dry-run
              </Button>
            </div>
          ) : (
            <div className="analysis-state">
              <Button
                type="button"
                className="action-button"
                onClick={runAnalysis}
                disabled={analysisLoading || !project}
              >
                {analysisLoading ? 'Running dry-run...' : 'Run dry-run'}
              </Button>
            </div>
          )}
          {analysisError ? <p className="error">{analysisError}</p> : null}
          {analysis ? (
            <div className="analysis-result">
              <dl className="project-grid">
                <div>
                  <dt>Status</dt>
                  <dd className={statusClass(analysis.status === 'success')}>
                    {analysis.status}
                  </dd>
                </div>
                <div>
                  <dt>Available</dt>
                  <dd className={statusClass(analysis.available)}>
                    {analysis.available ? 'Yes' : 'No'}
                  </dd>
                </div>
                <div>
                  <dt>Changed Files</dt>
                  <dd>{analysis.changedFilesCount ?? 'Unknown'}</dd>
                </div>
                <div>
                  <dt>Exit Code</dt>
                  <dd>{analysis.exitCode ?? 'N/A'}</dd>
                </div>
                <div className="project-grid-full">
                  <dt>Command</dt>
                  <dd className="code-block-inline">{analysis.command || 'Unavailable'}</dd>
                </div>
              </dl>

              <div className="output-block">
                <h3>Raw Output</h3>
                <pre>{analysis.stdout || 'No stdout returned.'}</pre>
              </div>

              {analysis.stderr ? (
                <div className="output-block">
                  <h3>Errors</h3>
                  <pre>{analysis.stderr}</pre>
                </div>
              ) : null}
            </div>
          ) : null}
        </section>
        <section>
          <h2>Review</h2>
          <p>Diff browsing and accept/reject flows will be layered on next.</p>
        </section>
      </main>
    </div>
  );
}

function statusClass(isReady) {
  return isReady ? 'status-good' : 'status-missing';
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
