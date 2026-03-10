import * as React from 'react';
import { ChevronRight, FileCode2, Folder } from 'lucide-react';

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub
} from '@/components/ui/sidebar';

export function FileTreeNode({ node, activePath, onSelectFile }) {
  const isFolder = node.type === 'folder';

  if (!isFolder) {
    return (
      <SidebarMenuButton
        isActive={activePath === node.path}
        onClick={() => onSelectFile(node.path)}
        tooltip={node.path}
        className="gap-2"
      >
        <FileCode2 className="size-4" />
        <span>{node.name}</span>
      </SidebarMenuButton>
    );
  }

  return (
    <SidebarMenuItem>
      <Collapsible
        defaultOpen
        className="group/collapsible [&[data-state=open]>button>svg:first-child]:rotate-90"
      >
        <CollapsibleTrigger asChild>
          <SidebarMenuButton>
            <ChevronRight className="transition-transform" />
            <Folder className="size-4" />
            <span>{node.name}</span>
          </SidebarMenuButton>
        </CollapsibleTrigger>
        <CollapsibleContent>
          <SidebarMenuSub>
            {node.children.map((child) => (
              <FileTreeNode
                key={child.key}
                node={child}
                activePath={activePath}
                onSelectFile={onSelectFile}
              />
            ))}
          </SidebarMenuSub>
        </CollapsibleContent>
      </Collapsible>
    </SidebarMenuItem>
  );
}
