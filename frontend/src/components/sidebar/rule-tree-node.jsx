import * as React from 'react';
import {ChevronRight, FileCode2, Wrench} from 'lucide-react';

import {Collapsible, CollapsibleContent, CollapsibleTrigger} from '@/components/ui/collapsible';
import {
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton
} from '@/components/ui/sidebar';

export function RuleTreeNode({rule, files, activePath, onSelectFile}) {
    return (
        <SidebarMenuItem>
            <Collapsible
                defaultOpen
                className="group/collapsible [&[data-state=open]>button>svg:first-child]:rotate-90"
            >
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton>
                        <ChevronRight className="transition-transform"/>
                        <Wrench className="size-4"/>
                        <span className="truncate">{rule}</span>
                        <SidebarMenuBadge>{files.length}</SidebarMenuBadge>
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {files.map((file) => (
                            <SidebarMenuSubButton
                                key={file.path}
                                isActive={activePath === file.path}
                                onClick={() => onSelectFile(file.path)}
                                size="sm"
                            >
                                <FileCode2 className="size-4"/>
                                <span className="truncate">{file.path}</span>
                            </SidebarMenuSubButton>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </Collapsible>
        </SidebarMenuItem>
    );
}
