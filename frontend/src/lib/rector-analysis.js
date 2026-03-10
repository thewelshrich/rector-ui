function safeJsonParse(value) {
  if (typeof value !== 'string' || value.trim() === '') {
    return null;
  }

  try {
    return JSON.parse(value);
  } catch (error) {
    return null;
  }
}

function normalizeRectorLabels(appliedRectors) {
  if (!Array.isArray(appliedRectors)) {
    return [];
  }

  return appliedRectors
    .map((item) => {
      if (typeof item === 'string') {
        return item.split('\\').pop() || item;
      }

      if (item && typeof item === 'object') {
        const candidate = item.class || item.name || item.rule || item.rector;
        if (typeof candidate === 'string' && candidate.length > 0) {
          return candidate.split('\\').pop() || candidate;
        }
      }

      return null;
    })
    .filter(Boolean);
}

function summarizeDiff(diff) {
  const lines = diff.split('\n');
  let additions = 0;
  let deletions = 0;

  for (const line of lines) {
    if (line.startsWith('+++') || line.startsWith('---')) {
      continue;
    }

    if (line.startsWith('+')) {
      additions += 1;
    } else if (line.startsWith('-')) {
      deletions += 1;
    }
  }

  return {
    additions,
    deletions
  };
}

function splitDiffIntoBlocks(diff) {
  if (typeof diff !== 'string' || diff.trim() === '') {
    return [];
  }

  const lines = diff.split('\n');
  const fileHeader = [];
  const blocks = [];
  let currentBlock = null;

  for (const line of lines) {
    if (line.startsWith('@@')) {
      if (currentBlock) {
        blocks.push(currentBlock);
      }

      currentBlock = {
        header: line,
        lines: []
      };
      continue;
    }

    if (!currentBlock) {
      fileHeader.push(line);
      continue;
    }

    currentBlock.lines.push(line);
  }

  if (currentBlock) {
    blocks.push(currentBlock);
  }

  if (blocks.length === 0) {
    return [
      {
        id: '1',
        title: 'Change block 01',
        header: 'Full diff',
        preview: lines.join('\n')
      }
    ];
  }

  return blocks.map((block, index) => ({
    id: `${index + 1}`,
    title: `Change block ${String(index + 1).padStart(2, '0')}`,
    preview: [...fileHeader, block.header, ...block.lines].filter(Boolean).join('\n')
  }));
}

function getFilePath(item) {
  if (typeof item === 'string') {
    return item;
  }

  if (!item || typeof item !== 'object') {
    return null;
  }

  return (
    item.file ||
    item.file_path ||
    item.relative_file_path ||
    item.relativePath ||
    item.path ||
    null
  );
}

function buildTree(paths) {
  const root = [];
  const folders = new Map();

  function getChildren(key) {
    if (!folders.has(key)) {
      folders.set(key, []);
    }

    return folders.get(key);
  }

  for (const path of paths) {
    const segments = path.split('/').filter(Boolean);
    let parentKey = '';
    let siblingList = root;

    segments.forEach((segment, index) => {
      const currentKey = parentKey ? `${parentKey}/${segment}` : segment;
      const existing = siblingList.find((node) => node.key === currentKey);
      const isLeaf = index === segments.length - 1;

      if (existing) {
        siblingList = existing.children || [];
        parentKey = currentKey;
        return;
      }

      const nextNode = {
        key: currentKey,
        name: segment,
        path: currentKey,
        type: isLeaf ? 'file' : 'folder',
        children: isLeaf ? [] : getChildren(currentKey)
      };

      siblingList.push(nextNode);
      siblingList = nextNode.children;
      parentKey = currentKey;
    });
  }

  function sortNodes(nodes) {
    nodes.sort((left, right) => {
      if (left.type !== right.type) {
        return left.type === 'folder' ? -1 : 1;
      }

      return left.name.localeCompare(right.name);
    });

    nodes.forEach((node) => {
      if (node.children.length > 0) {
        sortNodes(node.children);
      }
    });
  }

  sortNodes(root);
  return root;
}

function buildRulesTree(files) {
  const rulesMap = new Map();

  for (const file of files) {
    for (const rule of file.appliedRectors) {
      if (!rulesMap.has(rule)) {
        rulesMap.set(rule, []);
      }
      rulesMap.get(rule).push({
        path: file.path,
        additions: file.additions,
        deletions: file.deletions
      });
    }
  }

  const rulesTree = Array.from(rulesMap.entries())
    .map(([rule, ruleFiles]) => ({
      rule,
      files: ruleFiles.sort((a, b) => a.path.localeCompare(b.path))
    }))
    .sort((a, b) => b.files.length - a.files.length);

  return rulesTree;
}

export function buildAnalysisModel(analysis) {
  const payload = safeJsonParse(analysis?.stdout);
  const diffEntries = Array.isArray(payload?.file_diffs) ? payload.file_diffs : [];
  const changedFiles = Array.isArray(payload?.changed_files) ? payload.changed_files : [];
  const filesByPath = new Map();

  for (const changedFile of changedFiles) {
    const path = getFilePath(changedFile);

    if (!path) {
      continue;
    }

    filesByPath.set(path, {
      path,
      diff: '',
      blocks: [],
      additions: 0,
      deletions: 0,
      appliedRectors: []
    });
  }

  for (const diffEntry of diffEntries) {
    const path = getFilePath(diffEntry);

    if (!path) {
      continue;
    }

    const diff =
      diffEntry.diff ||
      diffEntry.diff_string ||
      diffEntry.diffString ||
      diffEntry.patch ||
      '';
    const appliedRectors = normalizeRectorLabels(
      diffEntry.applied_rectors || diffEntry.appliedRectors || diffEntry.rectors
    );
    const summary = summarizeDiff(diff);

    filesByPath.set(path, {
      path,
      diff,
      blocks: splitDiffIntoBlocks(diff),
      additions: summary.additions,
      deletions: summary.deletions,
      appliedRectors
    });
  }

  const files = Array.from(filesByPath.values()).sort((left, right) => left.path.localeCompare(right.path));
  const totalBlocks = files.reduce((count, file) => count + file.blocks.length, 0);
  const totalAdditions = files.reduce((count, file) => count + file.additions, 0);
  const totalDeletions = files.reduce((count, file) => count + file.deletions, 0);

  return {
    payload,
    files,
    tree: buildTree(files.map((file) => file.path)),
    rulesTree: buildRulesTree(files),
    parseFailed: Boolean(analysis?.stdout) && payload === null,
    totalBlocks,
    totalAdditions,
    totalDeletions
  };
}
