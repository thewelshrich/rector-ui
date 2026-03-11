import {Badge} from '@/components/ui/badge';
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/components/ui/card';

export function DiffCard({file, block}) {
    return (
        <Card className="overflow-hidden border-border/70 shadow-sm">
            <CardHeader className="gap-3 border-b bg-muted/30">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">{block.title}</Badge>
                    <Badge variant="outline" className="font-mono text-[11px]">
                        {file.path}
                    </Badge>
                </div>
                <div className="flex flex-wrap items-center gap-2 text-sm">
                    <CardTitle className="text-base">{file.path.split('/').pop()}</CardTitle>
                    <CardDescription
                        className="font-mono text-[11px]">{block.header || 'Diff segment'}</CardDescription>
                </div>
            </CardHeader>
            <CardContent className="p-0">
        <pre className="overflow-x-auto bg-slate-950 px-4 py-4 text-[12px] leading-6 text-slate-100 dark:bg-slate-950">
          {block.preview}
        </pre>
            </CardContent>
        </Card>
    );
}
