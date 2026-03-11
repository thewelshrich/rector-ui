import {AppSidebar} from '@/components/app-sidebar';
import {ReviewTopbar} from '@/components/review/review-topbar';
import {ReviewWorkspace} from '@/components/review/review-workspace';
import {SidebarInset, SidebarProvider} from '@/components/ui/sidebar';
import {buildAnalysisModel} from '@/lib/rector-analysis';

export function AnalysisShell({
                                  health,
                                  healthError,
                                  project,
                                  projectError,
                                  analysis,
                                  analysisError,
                                  analysisLoading,
                                  selectedFilePath,
                                  onSelectFile,
                                  onRunAnalysis
                              }) {
    const model = buildAnalysisModel(analysis);
    const activePath = selectedFilePath || model.files[0]?.path || null;

    return (
        <SidebarProvider style={{'--sidebar-width': '20rem'}}>
            <AppSidebar
                model={model}
                activePath={activePath}
                onSelectFile={onSelectFile}
                project={project}
                analysis={analysis}
            />
            <SidebarInset className="min-h-screen">
                <ReviewTopbar
                    project={project}
                    activePath={activePath}
                    analysisLoading={analysisLoading}
                    onRunAnalysis={onRunAnalysis}
                />
                <ReviewWorkspace
                    model={model}
                    analysis={analysis}
                    analysisError={analysisError}
                    project={project}
                    activePath={activePath}
                    analysisLoading={analysisLoading}
                    onRunAnalysis={onRunAnalysis}
                />
            </SidebarInset>
        </SidebarProvider>
    );
}
