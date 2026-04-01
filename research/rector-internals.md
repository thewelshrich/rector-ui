# Rector PHP Internals: Comprehensive Research Report

> Research conducted for the rector-ui project — a visual IDE for Rector.
> Covers Rector 1.x / 2.x (current main branch as of April 2026).

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Programmatic API](#2-programmatic-api)
3. [Configuration System](#3-configuration-system)
4. [Available Rules & Sets](#4-available-rules--sets)
5. [Output Formats](#5-output-formats)
6. [PHP Version Support](#6-php-version-support)
7. [Composer Integration](#7-composer-integration)
8. [Atomic Commit Strategy](#8-atomic-commit-strategy)
9. [Key Findings for rector-ui](#9-key-findings-for-rector-ui)

---

## 1. Architecture Overview

### 1.1 How Rector Works (3-Step Lifecycle)

Rector operates in three phases: **Find**, **Reconstruct**, and **Report**.

**Step 1: Discovery**
- Finds all files in the configured source paths
- Loads rules (called "Rectors") from `rector.php` or `--config` flag
- A "Rector" is a single PHP class responsible for one specific code transformation

**Step 2: Parse and Reconstruct**
- Uses `nikic/php-parser` to parse each file into an AST (Abstract Syntax Tree)
- The `StandaloneTraverseNodeTraverser` adds context metadata to nodes (parent class name, current method, namespace) via `$node->setAttribute()`
- For each node, checks if it matches the types defined in `$rector->getNodeTypes()`
- If matched, calls `$rector->refactor($node)` to modify the node
- **Node order**: Follows natural traversal (e.g., `Class_` before `ClassMethod`)
- **Rule order**: Rectors run in the order listed in the configuration file

**Step 3: Save/Diff**
- **Standard run**: Saves modified AST back to the file
- **Dry run** (`--dry-run`): Generates a git-like diff using `GeckoDiffOutputBuilder` instead of saving
- Reports a summary of all changed files

### 1.2 Core Source Code Structure (rector-src)

The development repository is at `rectorphp/rector-src`. The published package is at `rectorphp/rector` (scoper-prefixed).

**Key Directories and Classes:**

```
src/
├── Application/
│   ├── ApplicationFileProcessor.php    # Orchestrates file-level processing
│   ├── FileProcessor.php               # Processes individual files through rules
│   ├── ChangedNodeScopeRefresher.php   # Refreshes PHPStan scope after node changes
│   ├── NodeAttributeReIndexer.php      # Re-indexes node attributes after modifications
│   ├── VersionResolver.php             # Resolves Rector version
│   └── Provider/                       # Application-level service providers
├── Configuration/
│   ├── Option.php                      # All CLI/config option constants
│   ├── ConfigurationFactory.php        # Creates configuration from rector.php
│   ├── ConfigurationRuleFilter.php     # Filters rules based on config
│   ├── RectorConfigBuilder.php         # Builder for RectorConfig
│   ├── ConfigInitializer.php           # Initializes config when none found
│   ├── OnlyRuleResolver.php            # Resolves --only CLI flag to rule class
│   ├── PhpLevelSetResolver.php         # Resolves PHP level sets
│   ├── Levels/                         # Level definitions (type-coverage, dead-code, etc.)
│   └── Parameter/                      # Parameter definitions
├── ChangesReporting/
│   ├── Contract/Output/
│   │   └── OutputFormatterInterface.php  # Interface for output formatters
│   ├── Output/
│   │   ├── ConsoleOutputFormatter.php    # Default CLI diff output
│   │   ├── JsonOutputFormatter.php       # JSON structured output
│   │   ├── GitHubOutputFormatter.php     # GitHub Actions annotations
│   │   ├── GitlabOutputFormatter.php     # GitLab code quality reports
│   │   └── JUnitOutputFormatter.php      # JUnit XML format
│   ├── ValueObject/
│   │   └── RectorWithLineChange.php      # Per-rule, per-line change info
│   └── ValueObjectFactory/              # Creates value objects from diffs
├── Console/Command/
│   ├── ProcessCommand.php              # Main "process" command
│   ├── ListRulesCommand.php            # "list-rules" command
│   ├── WorkerCommand.php               # Parallel processing worker
│   ├── SetupCICommand.php              # "setup-ci" command
│   └── CustomRuleCommand.php           # "custom-rule" generator
├── Config/
│   └── RectorConfig.php                # Main configuration class (public API)
├── Rector/
│   └── AbstractRector.php              # Base class for all rules
├── ValueObject/
│   └── Reporting/
│       └── FileDiff.php                # Represents a file diff (implements JsonSerializable)
└── DependencyInjection/
    └── LazyContainerFactory.php        # Creates Symfony DI container
```

### 1.3 Key Infrastructure

- **Built on Symfony Console**: Rector uses `symfony/console` for its CLI, `symfony/dependency-injection` for service container
- **Uses PHPStan's static reflection**: Rector loads `phpstan.neon(.dist)` automatically and uses PHPStan's reflection engine to understand code without executing it
- **Parallel processing**: Enabled by default, uses `symfony/process` for multi-processing with a `WorkerCommand`
- **Caching**: Caches parsed/reflected files; uses in-memory cache by default, configurable to filesystem via `withCache()`

### 1.4 The Rector Rule Contract

Every rule extends `AbstractRector` and implements:

```php
// Which AST node types this rule targets
public function getNodeTypes(): array;

// The transformation logic
public function refactor(Node $node): ?Node;
```

Rules return a modified node or `null` (no change). Rules can also return `NodeTraverser::REMOVE_NODE` or `NodeTraverser::DONT_TRAVERSE_CHILDREN`.

---

## 2. Programmatic API

### 2.1 No Official Programmatic PHP API

**Critical finding**: Rector does NOT have a documented public PHP API for running without the CLI. It is designed as a CLI tool built on Symfony Console. There is no `Rector::run()` or similar class.

However, there are several strategies to invoke Rector programmatically:

### 2.2 Strategy 1: CLI Wrapper (Recommended for rector-ui)

The most reliable approach is to invoke the CLI binary programmatically:

```php
use Symfony\Component\Process\Process;

$process = new Process([
    'vendor/bin/rector',
    'process',
    $sourcePath,
    '--config', $configPath,
    '--dry-run',
    '--output-format', 'json',
    '--no-parallel',
    '--clear-cache',
]);
$process->setWorkingDirectory($projectPath);
$process->run();

if ($process->isSuccessful()) {
    $output = $process->getOutput();
    $data = json_decode($output, true);
    // Process structured diff data
}
```

**Advantages**: Stable, well-tested, handles all bootstrapping internally.

### 2.3 Strategy 2: Generate Dynamic Config Files

Create temporary `rector.php` config files to control what Rector does:

```php
$configContent = <<<'PHP'
<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withRules([SimplifyIfReturnBoolRector::class])
    ->withoutParallel();
PHP;

$tempConfig = tempnam(sys_get_temp_dir(), 'rector_') . '.php';
file_put_contents($tempConfig, $configContent);

// Then run: vendor/bin/rector process src --config $tempConfig --dry-run --output-format json
```

This is the approach recommended by Tomas Votruba (Rector maintainer) for custom binaries (see GitHub Discussion #7026).

### 2.4 Strategy 3: Symfony Console Application Bootstrap

Since Rector is a Symfony Console application, you can technically bootstrap it:

```php
// This is fragile and NOT recommended — internal API may change
require_once __DIR__ . '/vendor/autoload.php';

// Rector's bin/rector entry point essentially does:
// 1. Loads the autoloader
// 2. Bootstraps Rector's kernel/container
// 3. Runs the Symfony Console Application

// The entry point is: bin/rector (a PHP file)
// It creates a RectorKernel (extends Symfony HttpKernel)
// The kernel boots a container with all services
```

**Note**: The RectorKernel class is an internal Symfony kernel. Direct usage is fragile and not supported. The CLI wrapper approach (Strategy 1) is strongly preferred.

### 2.5 Key CLI Options for Programmatic Use

| Option | Description |
|--------|-------------|
| `process <paths>` | Main command — process files/directories |
| `--config <file>` | Path to rector.php config file |
| `--dry-run` | Preview changes without modifying files |
| `--output-format <format>` | Output format: `text`, `json`, `console`, `github`, `gitlab`, `junit` |
| `--only <RuleClass>` | Run only a specific rule (must be registered in config) |
| `--no-parallel` | Disable parallel processing (important for predictable output) |
| `--clear-cache` | Clear the cache before running |
| `--debug` | Show detailed error traces |
| `--xdebug` | Enable Xdebug for step debugging |
| `list-rules` | List all configured rules (supports `--output-format json`) |

### 2.6 Dry Run + JSON Output (The Key Combination)

For rector-ui, the primary workflow will be:

```bash
vendor/bin/rector process src/SomeFile.php \
    --config rector.php \
    --dry-run \
    --output-format json \
    --no-parallel
```

This returns structured JSON with file diffs without modifying any files.

---

## 3. Configuration System

### 3.1 Configuration File: `rector.php`

Rector looks for `rector.php` in the project root by default. Custom path via `--config`.

The config file returns a configured `RectorConfig` instance:

```php
<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    // === Paths ===
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()                    // Include root *.php files
    ->withFileExtensions(['php'])        // Default: php only

    // === PHP Version ===
    ->withPhpVersion(PhpVersion::PHP_82) // Target PHP version

    // === Rule Sets ===
    ->withSets([                         // Load predefined rule sets
        SetList::PHP_82,
        SetList::CODE_QUALITY,
    ])
    ->withPhpSets()                      // Auto-detect from composer.json
    ->withPhpSets(php80: true)           // Or enable specific versions
    ->withPreparedSets(                  // Prepared rule groups
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        naming: true,
        privatization: true,
        typeDeclarations: true,
        earlyReturn: true,
        strictBooleans: true,
        rectorPreset: true,
    )

    // === Individual Rules ===
    ->withRules([
        SomeSpecificRule::class,
    ])
    ->rule(SomeOtherRule::class)         // Single rule shorthand
    ->ruleWithConfiguration(             // Rule with config
        RenameMethodRector::class,
        [new MethodCallRename('Foo', 'oldMethod', 'newMethod')]
    )

    // === Skip / Excludes ===
    ->withSkip([
        __DIR__ . '/src/Legacy/*',      // Skip directory
        SomeRule::class,                 // Skip rule everywhere
        OtherRule::class => [            // Skip rule for specific files
            __DIR__ . '/src/SpecialCase.php',
        ],
    ])

    // === Behavior ===
    ->withTreatClassesAsFinal()          // Allow aggressive refactoring
    ->withoutParallel()                  // Disable parallel processing
    ->withImportNames()                  // Auto-manage use statements

    // === Performance ===
    ->withCache(__DIR__ . '/var/rector') // Custom cache directory
    ->withAutoloadPaths([...])           // Additional autoload paths
    ->withBootstrapFiles([...])          // Files to include before processing

    // === Advanced ===
    ->withPHPStanConfigs([...])          // Additional PHPStan configs
    ->withSymfonyContainerXml(...)       // Symfony container metadata
    ->withFluentCallNewLine()            // Multi-line fluent calls
    ->withIndent(indentChar: ' ', indentSize: 4)

    // === Levels (incremental refactoring) ===
    ->withTypeCoverageLevel(0)           // Start at level 0, increment
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withCodingStyleLevel(0)
    ->withPhpLevel(5)                    // PHP upgrade levels
;
```

### 3.2 Configuration Constants (`Option` class)

The `Rector\Core\Configuration\Option` class defines all configuration option names as string constants. These are used internally and map to both CLI flags and config methods. Key constants include:

```
OPTION_DRY_RUN
OPTION_CONFIG
OPTION_OUTPUT_FORMAT
OPTION_ONLY
OPTION_NO_PARALLEL
OPTION_CLEAR_CACHE
OPTION_DEBUG
OPTION_PATHS
OPTION_AUTOLOAD_PATHS
OPTION_BOOTSTRAP_FILES
OPTION_PHP_VERSION_FEATURES
OPTION_CACHE_DIR
...
```

### 3.3 Configuration Builder Pattern

`RectorConfig::configure()` returns a `RectorConfigBuilder` instance that uses a fluent interface. The builder compiles the configuration into Symfony DI container parameters when the kernel boots.

### 3.4 Set Files as Config

Sets can be PHP files that return a closure modifying the config:

```php
// vendor/rector/rector/config/set/php80.php
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules([
        StrContainsRector::class,
        StrStartsWithRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        // ... more PHP 8.0 rules
    ]);
};
```

These are loaded via `->withSets([SetList::PHP_80])` or `->import($path)`.

---

## 4. Available Rules & Sets

### 4.1 Rule Categories

Rector organizes rules into these main categories:

| Category | Namespace | Description |
|----------|-----------|-------------|
| **PHP Version** | `Rector\Php5x` to `Rector\Php85` | Upgrade code to specific PHP version features |
| **Code Quality** | `Rector\CodeQuality` | Improve code quality, simplify logic |
| **Dead Code** | `Rector\DeadCode` | Remove unused code |
| **Type Declarations** | `Rector\TypeDeclaration` | Add missing type hints |
| **Coding Style** | `Rector\CodingStyle` | Modernize coding patterns |
| **Naming** | `Rector\Naming` | Rename to follow naming conventions |
| **Privatization** | `Rector\Privatization` | Make properties/methods private where possible |
| **Early Return** | `Rector\EarlyReturn` | Simplify conditions to early returns |
| **Strict Booleans** | `Rector\StrictCode` / strict sets | Strict boolean comparisons |
| **Renaming** | `Rector\Renaming` | Rename classes, methods, properties |
| **Arguments** | `Rector\Arguments` | Add/remove/reorder method arguments |
| **Instanceof_** | `Rector\Instanceof_` | Simplify instanceof chains |
| **Doctrine** | `Rector\Doctrine` | Doctrine-specific upgrades |
| **Symfony** | `Rector\Symfony` | Symfony-specific upgrades |
| **PHPUnit** | `Rector\PHPUnit` | PHPUnit-specific upgrades |
| **Laravel** | (external: `rector-laravel`) | Laravel-specific upgrades |

### 4.2 PHP Version Sets

| Constant | Description |
|----------|-------------|
| `SetList::PHP_53` | Upgrade from PHP 5.3 features |
| `SetList::PHP_54` | Upgrade from PHP 5.4 features |
| `SetList::PHP_55` | Upgrade from PHP 5.5 features |
| `SetList::PHP_56` | Upgrade from PHP 5.6 features |
| `SetList::PHP_70` | Upgrade from PHP 7.0 features |
| `SetList::PHP_71` | Upgrade from PHP 7.1 features |
| `SetList::PHP_72` | Upgrade from PHP 7.2 features |
| `SetList::PHP_73` | Upgrade from PHP 7.3 features |
| `SetList::PHP_74` | Upgrade from PHP 7.4 features |
| `SetList::PHP_80` | Upgrade from PHP 8.0 features |
| `SetList::PHP_81` | Upgrade from PHP 8.1 features |
| `SetList::PHP_82` | Upgrade from PHP 8.2 features |
| `SetList::PHP_83` | Upgrade from PHP 8.3 features |
| `SetList::PHP_84` | Upgrade from PHP 8.4 features |
| `SetList::PHP_85` | Upgrade from PHP 8.5 features |

### 4.3 Prepared Sets (Named Arguments)

```php
->withPreparedSets(
    deadCode: true,              // Remove unused code
    codeQuality: true,           // Improve code quality
    codingStyle: true,           // Modernize coding style
    naming: true,                // Better naming conventions
    privatization: true,         // Reduce visibility where possible
    typeDeclarations: true,      // Add type declarations
    typeDeclarationDocblocks: true, // Add docblock type hints
    earlyReturn: true,           // Simplify to early returns
    strictBooleans: true,        // Strict boolean handling
    instanceof_: true,           // Simplify instanceof checks
    rectorPreset: true,          // Rector's own recommended preset
);
```

### 4.4 Level-Based Sets (Incremental)

Instead of applying all rules at once, Rector provides "levels" that progressively apply rules:

```php
->withTypeCoverageLevel(0)      // Type declarations (0 = safest/easiest)
->withTypeCoverageLevel(5)      // ...up to higher levels
->withDeadCodeLevel(0)
->withCodeQualityLevel(0)
->withCodingStyleLevel(0)
->withPhpLevel(5)               // PHP upgrade levels
```

### 4.5 Composer-Based Sets

Automatically detect installed packages and apply relevant rules:

```php
->withComposerBased(
    symfony: true,
    doctrine: true,
    phpunit: true,
    // ... more detected packages
);
```

### 4.6 Typical Upgrade Set Example (PHP 7.3 → 8.2)

A gradual upgrade approach:

```php
// Step 1: Upgrade 7.3 → 7.4
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withSets([SetList::PHP_74]);

// Step 2: Upgrade 7.4 → 8.0
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withSets([SetList::PHP_80]);

// Step 3: Upgrade 8.0 → 8.1
// Step 4: Upgrade 8.1 → 8.2
```

Or using `withPhpSets()` with specific version flags:

```php
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withPhpSets(php74: true, php80: true, php81: true, php82: true);
```

### 4.7 Discovering Rules

- **Find rules**: https://getrector.com/find-rule (searchable web interface)
- **List configured rules**: `vendor/bin/rector list-rules` (or `--output-format json`)
- **Run single rule**: `vendor/bin/rector process src --only="Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector"`

---

## 5. Output Formats

### 5.1 Available Output Formatters

Rector supports multiple output formats via `--output-format`:

| Format | Class | Use Case |
|--------|-------|----------|
| `console` (default) | `ConsoleOutputFormatter` | Human-readable CLI diff output |
| `json` | `JsonOutputFormatter` | Machine-readable structured data |
| `github` | `GitHubOutputFormatter` | GitHub Actions annotations |
| `gitlab` | `GitlabOutputFormatter` | GitLab code quality reports |
| `junit` | `JUnitOutputFormatter` | JUnit XML test format |

### 5.2 Console Output (Default)

```
1/1 [===========================] 100%
1 file with changes

==================
1) src/old-php.php:3
---------- begin diff ----------
@@ @@
     private const THE_ANSWER = 42;
-    private $theQuestion;
-
-    public function __construct(string $theQuestion)
-    {
-        $this->theQuestion = $theQuestion;
+    public function __construct(private string $theQuestion)
+    {
     }
----------- end diff -----------

Applied rules:
* ClassPropertyAssignToConstructorPromotionRector
```

### 5.3 JSON Output

```bash
vendor/bin/rector process src --dry-run --output-format json
```

The JSON output contains an array of file diffs. Each `FileDiff` object (class: `Rector\Core\ValueObject\Reporting\FileDiff`) is JSON-serialized with these properties:

- `file` — Absolute file path
- `diff` — Unified diff string
- `applied_rectors` — Array of rule class names that caused changes

**Important note**: Issue #6888 requested adding `$originalContent` and `$newContent` to the JSON output. As of the latest version, the JSON output primarily contains the diff and metadata, not full file contents. For rector-ui, we may need to read the original file content ourselves and apply the diff.

### 5.4 JSON Output Structure (Inferred)

```json
[
    {
        "file": "/absolute/path/to/File.php",
        "diff": "--- Original\n+++ New\n@@ @@\n-old code\n+new code\n",
        "applied_rectors": [
            "Rector\\CodeQuality\\Rector\\If_\\SimplifyIfReturnBoolRector",
            "Rector\\TypeDeclaration\\Rector\\Property\\TypedPropertyFromAssignsRector"
        ]
    }
]
```

### 5.5 Custom Output Formatters

You can create custom output formatters by implementing `OutputFormatterInterface`:

```php
namespace Rector\Core\ChangesReporting\Contract\Output;

interface OutputFormatterInterface
{
    public function getName(): string;
    public function report(array $fileDiffs): void;
}
```

This is a DI service that can be registered in Rector's container. This could be useful for rector-ui to create a custom formatter that streams changes back to the frontend.

---

## 6. PHP Version Support

### 6.1 Rector's Own PHP Requirement

- **Rector 1.x**: Requires PHP 8.1+ (as a runtime to execute Rector itself)
- **Rector 0.x**: Required PHP 7.3+
- Rector can process/analyze code targeting PHP 5.3 through 8.5 regardless of the runtime PHP version

### 6.2 Target PHP Version Configuration

Rector determines the target PHP version from multiple sources (in order of priority):

1. `withPhpVersion(PhpVersion::PHP_82)` in `rector.php`
2. `composer.json` → `require.php` (e.g., `"php": "^7.4"`)
3. `composer.json` → `config.platform.php`
4. Current PHP runtime version

### 6.3 PHP Version Behavior

Rector uses the target PHP version to:
- Decide which features are safe to apply (e.g., won't add attributes on PHP 7.x targets)
- Filter rules that are only relevant for the target version
- Ensure output code is compatible with the target version

```php
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_82);
```

Available `PhpVersion` constants:
`PHP_53`, `PHP_54`, `PHP_55`, `PHP_56`, `PHP_70`, `PHP_71`, `PHP_72`, `PHP_73`, `PHP_74`, `PHP_80`, `PHP_81`, `PHP_82`, `PHP_83`, `PHP_84`, `PHP_85`

### 6.4 Upgrade Strategy: Version by Version

The recommended approach is to upgrade one PHP version at a time:

```php
// Step 1: Apply PHP 7.4 rules
->withSets([SetList::PHP_74])
// Run, review, commit

// Step 2: Apply PHP 8.0 rules
->withSets([SetList::PHP_80])
// Run, review, commit

// etc.
```

Or use the level system for even finer granularity:
```php
->withPhpLevel(5)  // Increments through all PHP version rules in small batches
```

---

## 7. Composer Integration

### 7.1 Installing Rector

```bash
# Standard: install in project
composer require rector/rector --dev

# Standalone: install in separate directory (for old projects)
mkdir rector-standalone && cd rector-standalone
composer require rector/rector --dev
```

### 7.2 Rector's Composer Dependencies

Rector depends on:
- `nikic/php-parser` (4.x) — AST parsing
- `phpstan/phpstan` — Static reflection and analysis
- `symfony/console` — CLI framework
- `symfony/dependency-injection` — Service container
- `symfony/process` — Parallel processing
- `webmozart/assert` — Assertions
- `sebastian/diff` — Diff generation (used by `GeckoDiffOutputBuilder`)

### 7.3 Platform Requirement Strategy

**For rector-ui**, the key question is: can we run Rector on a project that targets PHP 7.3+ while Rector itself requires PHP 8.1+?

**Answer: Yes**, using one of these strategies:

1. **Standalone installation**: Install Rector in a separate directory that uses PHP 8.1+, and point it at the target project. This is Rector's official recommendation for old projects.

2. **Docker container**: Run Rector in a Docker container with PHP 8.1+ that has access to the target project's source code.

3. **Platform override**: In `composer.json`, use `config.platform.php` to set the target PHP version while having a newer PHP runtime:
   ```json
   {
       "require": {
           "php": "^8.1"
       },
       "config": {
           "platform": {
               "php": "7.4"
           }
       }
   }
   ```

4. **Composer `--ignore-platform-reqs`**: When installing Rector alongside old dependencies.

**For rector-ui's architecture**: The app itself will run on PHP 8.1+ (Node.js actually, but the PHP wrapper needs PHP 8.1+). The target project's PHP version is configured via `rector.php` → `withPhpVersion()`, which controls what rules are applied and what output code is generated.

---

## 8. Atomic Commit Strategy

### 8.1 The Problem

Running a full PHP upgrade set (e.g., PHP 7.4 → 8.2) applies dozens of rules at once, producing massive changes that are hard to review and revert.

The paid Rector offering (rector-ci) was designed to solve this with per-rule atomic commits. This was archived in 2021.

### 8.2 Running One Rule at a Time

**CLI Approach (using `--only`)**:

```bash
# Run only a specific rule
vendor/bin/rector process src --dry-run --only="Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector"

# Get JSON output for that single rule
vendor/bin/rector process src --dry-run --only="Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector" --output-format json --no-parallel
```

**Important limitation**: The `--only` flag requires the rule to be registered in `rector.php`. You can't use `--only` with a rule that isn't in the config.

**Config Approach (dynamic config per rule)**:

```php
// Generate a temporary config with only one rule
$config = <<<'PHP'
<?php
use Rector\Config\RectorConfig;
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withRules([\Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class])
    ->withoutParallel();
PHP;
```

### 8.3 Getting a List of All Rules First

```bash
# Get all rules that WOULD be applied (in JSON)
vendor/bin/rector list-rules --output-format json
```

This returns the list of all configured rules. Then iterate over them one at a time.

### 8.4 Recommended Atomic Strategy for rector-ui

1. **Discovery phase**: Run `list-rules --output-format json` to get all configured rules
2. **Per-rule analysis**: For each rule, create a temporary config with only that rule
3. **Dry run per rule**: Run `process --dry-run --output-format json` for each rule
4. **Collect diffs**: Store each rule's diff separately
5. **Present to user**: Show rule-by-rule changes for selective application
6. **Apply selectively**: Run `process` (without `--dry-run`) for approved rules only

```php
// Pseudocode for atomic workflow
$rules = getRulesFromListCommand($configPath);
$atomicChanges = [];

foreach ($rules as $rule) {
    $tempConfig = generateSingleRuleConfig($rule, $projectPath, $sourcePaths);
    $diff = runRectorDryRun($tempConfig, $sourcePaths);
    if (!empty($diff)) {
        $atomicChanges[$rule] = $diff;
    }
}

// Present $atomicChanges to user for selection
// Apply only selected rules
```

### 8.5 Per-File Granularity

You can also limit processing to specific files:

```bash
vendor/bin/rector process src/SomeFile.php --config rector.php --dry-run --output-format json
```

### 8.6 Understanding Which Rules Applied

The JSON output includes `applied_rectors` per file, showing exactly which rules caused changes. This is crucial for the atomic commit strategy — you can see which rules actually affected each file.

---

## 9. Key Findings for rector-ui

### 9.1 Recommended Architecture for rector-ui

1. **PHP Backend Service**: A PHP service (Laravel/Slim/Symfony) that wraps Rector CLI calls
2. **Process-based invocation**: Use `symfony/process` to call `vendor/bin/rector` — this is the most stable approach
3. **Dynamic config generation**: Generate temporary `rector.php` files to control behavior
4. **JSON output parsing**: Use `--output-format json` for structured data exchange

### 9.2 Critical API Patterns

```php
// === Core workflow ===

// 1. Get list of available rules
runRector('list-rules', ['--output-format' => 'json']);

// 2. Dry run with specific rules
runRector('process', [
    'src/',
    '--config' => $configPath,
    '--dry-run',
    '--output-format' => 'json',
    '--no-parallel',
]);

// 3. Apply specific rules
runRector('process', [
    'src/',
    '--config' => $configPath,
    '--output-format' => 'json',
    '--no-parallel',
]);

// 4. Run single rule
runRector('process', [
    'src/',
    '--config' => $singleRuleConfigPath,
    '--dry-run',
    '--output-format' => 'json',
    '--no-parallel',
]);
```

### 9.3 Gotchas and Caveats

1. **No public PHP API**: Must use CLI wrapper — no `Rector::run()` equivalent
2. **`--only` requires config registration**: Rules must be in `rector.php` to use `--only`
3. **Parallel processing**: Must use `--no-parallel` for predictable output order
4. **Cache**: Must `--clear-cache` between runs with different configs, or use separate cache directories
5. **JSON output lacks full content**: JSON has diffs and metadata, not original/new file content — must read files separately
6. **Bootstrapping time**: Rector takes several seconds to boot (loading PHPStan, parsing project) — consider caching/warming
7. **Static reflection**: Rector needs to understand the project's classes — ensure autoloading is correct
8. **Error handling**: Rector can crash on certain code patterns — always wrap in try/catch with `--debug` for diagnostics

### 9.4 FileDiff Value Object

The `Rector\Core\ValueObject\Reporting\FileDiff` class is the core data structure for changes. Key properties:

```php
class FileDiff implements JsonSerializable
{
    private string $filePath;           // Absolute path to the file
    private string $diff;               // Unified diff string
    private string $content;            // New file content (when applied)
    private array $appliedRectors;      // List of rule class names
    private ?string $errorMessage;      // Error if processing failed
    private array $rectorWithLineChanges; // Per-rule, per-line changes
    private int $fileHash;              // Hash for cache invalidation
}
```

### 9.5 Suggested rector-ui Feature Mapping

| rector-ui Feature | Rector Mechanism |
|-------------------|-----------------|
| Browse rules | `list-rules --output-format json` |
| Preview changes | `process --dry-run --output-format json` |
| Apply changes | `process --output-format json` (no --dry-run) |
| Single rule preview | Dynamic config + `process --dry-run` |
| Atomic commits | Iterate rules from `list-rules`, run each separately |
| File-level changes | `process src/SpecificFile.php` |
| PHP version upgrade | Dynamic config with `SetList::PHP_8x` |
| Undo/rollback | Git integration (Rector doesn't support undo natively) |
| Rule search | https://getrector.com/find-rule API or local rule listing |

### 9.6 References

- Official docs: https://getrector.com/documentation
- GitHub (published): https://github.com/rectorphp/rector
- GitHub (dev): https://github.com/rectorphp/rector-src
- Rule search: https://getrector.com/find-rule
- Demo: https://getrector.com/demo
- Book: https://leanpub.com/rector-the-power-of-automated-refactoring
- Programmatic bootstrap discussion: https://github.com/rectorphp/rector/discussions/7026
- JSON output enhancement request: https://github.com/rectorphp/rector/issues/6888
- Single rule feature: https://github.com/rectorphp/rector/issues/8899
- Per-rule atomic commits: https://github.com/deprecated-packages/rector-ci/issues/42
