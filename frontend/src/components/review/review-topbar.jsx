import {Play, Sparkles} from 'lucide-react';

import {ThemeToggle} from '@/components/theme-toggle';
import {Button} from '@/components/ui/button';
import {SidebarTrigger} from '@/components/ui/sidebar';

export function ReviewTopbar({project, activePath, analysisLoading, onRunAnalysis}) {
    return (
        <header
            className="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-background/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger/>
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold">
                        {activePath || project?.path || 'Rector review workspace'}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                        Changed files in the left rail, diff cards in the workspace
                    </p>
                </div>
            </div>

            <div className="flex items-center gap-2">
                <ThemeToggle/>
                <Button type="button" disabled={analysisLoading || !project?.hasRectorAnalysis} onClick={onRunAnalysis}>
                    {analysisLoading ? <Sparkles className="size-4 animate-pulse"/> : <Play className="size-4"/>}
                    {analysisLoading ? 'Running dry-run...' : 'Run dry-run'}
                </Button>
            </div>
        </header>
    );
}
