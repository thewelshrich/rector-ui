import React from 'react';
import ReactDOM from 'react-dom/client';
import {useEffect, useState} from 'react';

import {AnalysisShell} from '@/components/analysis-shell';
import {ThemeProvider} from '@/components/theme-provider';
import './styles.css';

function App() {
  const [health, setHealth] = useState(null);
  const [healthError, setHealthError] = useState('');
  const [project, setProject] = useState(null);
  const [projectError, setProjectError] = useState('');
  const [analysis, setAnalysis] = useState(null);
  const [analysisError, setAnalysisError] = useState('');
  const [analysisLoading, setAnalysisLoading] = useState(false);
  const [selectedFilePath, setSelectedFilePath] = useState(null);

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
    setSelectedFilePath(null);

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
      <ThemeProvider>
        <AnalysisShell
            health={health}
            healthError={healthError}
            project={project}
            projectError={projectError}
            analysis={analysis}
            analysisError={analysisError}
            analysisLoading={analysisLoading}
            selectedFilePath={selectedFilePath}
            onSelectFile={setSelectedFilePath}
            onRunAnalysis={runAnalysis}
        />
      </ThemeProvider>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
