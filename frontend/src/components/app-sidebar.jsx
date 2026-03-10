import * as React from 'react';
import { FileCode2, FolderGit2, Sparkles } from 'lucide-react';

import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail
} from '@/components/ui/sidebar';

import { FileTreeNode } from '@/components/sidebar/file-tree-node';

export function AppSidebar({ model, activePath, onSelectFile, project, analysis }) {
  return (
    <Sidebar collapsible="icon" variant="inset" style={{ '--sidebar-width': '20rem' }}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" className="gap-3">
              <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <Sparkles className="size-4" />
              </div>
              <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">Rector UI</span>
                <span className="truncate text-xs text-muted-foreground">
                  {project?.hasRectorAnalysis ? 'Analysis ready' : 'Awaiting Rector setup'}
                </span>
              </div>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupLabel>Latest run</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem>
                <SidebarMenuButton tooltip="Repository status">
                  <FolderGit2 className="size-4" />
                  <span>{project?.isGitRepo ? `Git: ${project.gitStatus}` : 'No git repository'}</span>
                </SidebarMenuButton>
                <SidebarMenuBadge>{analysis?.status || 'idle'}</SidebarMenuBadge>
              </SidebarMenuItem>
              <SidebarMenuItem>
                <SidebarMenuButton tooltip="Changed file count">
                  <FileCode2 className="size-4" />
                  <span>Changed files</span>
                </SidebarMenuButton>
                <SidebarMenuBadge>{model.files.length || analysis?.changedFilesCount || 0}</SidebarMenuBadge>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        <SidebarGroup>
          <SidebarGroupLabel>Changed files</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {model.tree.length > 0 ? (
                model.tree.map((node) => (
                  <FileTreeNode
                    key={node.key}
                    node={node}
                    activePath={activePath}
                    onSelectFile={onSelectFile}
                  />
                ))
              ) : (
                <SidebarMenuItem>
                  <div className="rounded-md border border-dashed border-sidebar-border px-3 py-3 text-sm text-muted-foreground">
                    Run analysis to populate the tree view.
                  </div>
                </SidebarMenuItem>
              )}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      <SidebarRail />
    </Sidebar>
  );
}
