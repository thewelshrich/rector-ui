import {AlertCircle, FileCode2, Sparkles} from 'lucide-react';

import {DiffCard} from '@/components/review/diff-card';
import {Badge} from '@/components/ui/badge';
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/components/ui/card';

function EmptyState({project, analysisLoading, onRunAnalysis}) {
    return (
        <Card className="border-dashed">
            <CardHeader>
                <CardTitle>No Rector output loaded</CardTitle>
                <CardDescription>
                    Run the dry-run to populate the sidebar tree and generate clean diff cards in this workspace.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Sparkles className="size-4"/>
                    {project?.hasRectorAnalysis
                        ? analysisLoading
                            ? 'Rector is running now.'
                            : 'Analysis is ready to run.'
                        : 'Analysis is blocked until the target project has a Rector config and binary.'}
                </div>
            </CardContent>
        </Card>
    );
}

function SummaryCards({model, visibleFiles}) {
    const items = [
        {
            label: 'Visible files',
            value: visibleFiles.length
        },
        {
            label: 'Blocks',
            value: visibleFiles.reduce((count, file) => count + file.blocks.length, 0)
        },
        {
            label: 'Line movement',
            value: `${model.totalAdditions}+ / ${model.totalDeletions}-`
        }
    ];

    return (
        <div className="grid gap-4 md:grid-cols-3">
            {items.map((item) => (
                <Card key={item.label} className="shadow-sm">
                    <CardHeader className="gap-1 pb-3">
                        <CardDescription>{item.label}</CardDescription>
                        <CardTitle className="text-2xl">{item.value}</CardTitle>
                    </CardHeader>
                </Card>
            ))}
        </div>
    );
}

export function ReviewWorkspace({
                                    model,
                                    analysis,
                                    analysisError,
                                    project,
                                    activePath,
                                    analysisLoading,
                                    onRunAnalysis
                                }) {
    const visibleFiles = activePath ? model.files.filter((file) => file.path === activePath) : model.files;

    if (!analysis || (analysis.available && model.files.length === 0)) {
        return (
            <div className="p-6">
                <EmptyState project={project} analysisLoading={analysisLoading} onRunAnalysis={onRunAnalysis}/>
            </div>
        );
    }

    return (
        <div className="flex flex-1 flex-col gap-6 p-6">
            {analysisError ? (
                <Card className="border-destructive/40">
                    <CardContent className="flex items-center gap-3 px-6 py-4 text-sm text-destructive">
                        <AlertCircle className="size-4"/>
                        {analysisError}
                    </CardContent>
                </Card>
            ) : null}

            <div className="space-y-2">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">Review workspace</Badge>
                    <Badge variant="secondary">{analysis.status}</Badge>
                    {activePath ? <Badge variant="secondary">{activePath}</Badge> : null}
                </div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    {activePath || 'Rector output'}
                </h1>
                <p className="text-sm text-muted-foreground">
                    Clean diff cards for the current Rector run, scoped by the selected file in the sidebar.
                </p>
            </div>

            <SummaryCards model={model} visibleFiles={visibleFiles}/>

            <div className="space-y-6">
                {visibleFiles.map((file) => {
                    const blocks =
                        file.blocks.length > 0
                            ? file.blocks
                            : [
                                {
                                    id: `${file.path}-raw`,
                                    title: 'Full diff',
                                    header: 'Fallback block',
                                    preview: file.diff || 'No diff payload returned for this file.'
                                }
                            ];

                    return (
                        <section key={file.path} className="space-y-4">
                            <div className="flex flex-wrap items-center gap-3">
                                <div
                                    className="inline-flex items-center gap-2 rounded-md border bg-card px-3 py-2 text-sm">
                                    <FileCode2 className="size-4 text-muted-foreground"/>
                                    <span className="font-medium">{file.path}</span>
                                </div>
                                <Badge variant="outline">+{file.additions}</Badge>
                                <Badge variant="outline">-{file.deletions}</Badge>
                                {file.appliedRectors.slice(0, 3).map((rector) => (
                                    <Badge key={rector} variant="secondary">
                                        {rector}
                                    </Badge>
                                ))}
                            </div>

                            <div className="space-y-4">
                                {blocks.map((block) => (
                                    <DiffCard key={`${file.path}-${block.id}`} file={file} block={block}/>
                                ))}
                            </div>
                        </section>
                    );
                })}
            </div>
        </div>
    );
}
