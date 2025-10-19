# WP.org Plugin Readme Linter

[![Tests](https://github.com/thetwopct/wporg-plugin-readme-linter/actions/workflows/tests.yml/badge.svg)](https://github.com/thetwopct/wporg-plugin-readme-linter/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A comprehensive linter for WordPress.org plugin `readme.txt` files. Helps ensure your plugin readme meets WordPress.org Plugin Directory standards before submission. Lint from your development environment via composer, or include in your GitHub Actions.

## Features

- ✅ **Comprehensive validation** - Checks headers, metadata, sections, and content quality
- 📝 **GitHub Annotations** - Inline PR comments on readme issues (when using GitHub Actions)
- 🎯 **Configurable rules** - Customize severity levels and required sections
- ⚡ **Fast** - Typically runs in under 2 seconds
- 🐳 **Zero setup** - Container action includes all dependencies
- 📊 **Multiple output formats** - Annotations, JSON, or SARIF

## Quick Start

### Use as a GitHub Action

Add this workflow to your repository at `.github/workflows/readme-lint.yml`:

```yaml
name: Lint Readme

on:
  pull_request:
    paths:
      - 'readme.txt'
  push:
    branches:
      - main

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Lint readme.txt
        uses: thetwopct/wporg-plugin-readme-linter@v1
        with:
          path: readme.txt
          fail-on: error
```

or you could add something like the below to your existing workflow action:

```yaml
    - name: Lint readme.txt
      uses: thetwopct/wporg-plugin-readme-linter@main
      with:
        path: readme.txt
        fail-on: error
```

### Use as a Composer Package (CLI)

Install via Composer:

```bash
composer require --dev thetwopct/wporg-plugin-readme-linter
```

Run the linter:

```bash
vendor/bin/wporg-plugin-readme-linter readme.txt
```

By default, the linter uses a human-friendly text format for local development and automatically switches to GitHub Actions annotation format when running in CI.

With options:

```bash
# Use GitHub Actions annotation format (useful for CI)
vendor/bin/wporg-plugin-readme-linter readme.txt --format=annotations

# Output as JSON
vendor/bin/wporg-plugin-readme-linter readme.txt --format=json

# Generate SARIF file
vendor/bin/wporg-plugin-readme-linter readme.txt --format=sarif --output=report.sarif

# Fail on warnings (not just errors)
vendor/bin/wporg-plugin-readme-linter readme.txt --fail-on=warning

# Use custom config
vendor/bin/wporg-plugin-readme-linter readme.txt --config=.wporg-readme-lint.json
```

## Configuration

Create a `.wporg-readme-lint.json` file in your repository root to customize the linter:

```json
{
  "readmePath": "readme.txt",
  "failOn": "error",
  "ignoreRules": ["donate-link"],
  "requiredSections": ["description", "installation", "changelog", "faq"],
  "allowTrunk": false
}
```

### Configuration Options

- **`readmePath`** (string): Path to readme file. Default: `readme.txt`
- **`failOn`** (string): Minimum severity to fail on. Options: `error`, `warning`, `info`. Default: `error`
- **`ignoreRules`** (array): Rule IDs to ignore. Default: `[]`
- **`requiredSections`** (array): Sections that must be present. Default: `["description", "installation", "changelog"]`
- **`allowTrunk`** (boolean): Whether to allow "trunk" as stable tag. Default: `false`

## Rules

### Headers & Metadata

| Rule ID | Description | Level |
|---------|-------------|-------|
| `plugin-name` | Plugin name header must be present, properly formatted, and not use trademarks improperly | Error/Warning |
| `required-fields` | Required metadata fields (Contributors, Tags, Requires at least, Tested up to, Stable tag) | Error |
| `short-description` | Short description must be ≤150 characters | Error/Warning |
| `stable-tag` | Stable tag must be valid semantic version (not "trunk" unless configured) | Error/Warning |
| `license` | License field must be present, valid, and match plugin file header | Error |
| `contributors` | Contributors must use valid usernames and not be restricted/reserved | Error/Warning |
| `tested-up-to` | WordPress version must be current and valid | Error |

### Sections & Structure

| Rule ID | Description | Level |
|---------|-------------|-------|
| `required-sections` | Required sections must be present | Error |
| `heading-levels` | Proper heading levels (=== for title, == for sections) | Error |
| `empty-sections` | Sections should have meaningful content | Warning |

### Content Quality

| Rule ID | Description | Level |
|---------|-------------|-------|
| `file-size` | Readme file size should be reasonable (≤20KB) | Warning/Info |
| `default-text` | Detects unmodified readme template text | Error |
| `trademark` | Detects improper use of trademarked names (WordPress, etc.) | Warning/Info |

### WordPress.org Compliance

| Rule ID | Description | Level |
|---------|-------------|-------|
| `donate-link` | Donate link should use appropriate domains and be properly formatted | Error/Warning/Info |
| `upgrade-notice` | Upgrade notices should be limited in number and length | Warning |

## Action Inputs

| Input | Description | Required | Default |
|-------|-------------|----------|---------|
| `path` | Path to readme.txt file | No | `readme.txt` |
| `format` | Output format: `text`, `annotations`, `sarif`, or `json` | No | Auto-detected (`text` for CLI, `annotations` for CI) |
| `fail-on` | Fail on level: `error`, `warning`, or `info` | No | `error` |
| `config` | Path to configuration file | No | `.wporg-readme-lint.json` |
| `output` | Output file path for SARIF/JSON | No | `''` (stdout) |

## Action Outputs

| Output | Description |
|--------|-------------|
| `sarif-file` | Path to generated SARIF file (if format=sarif) |
| `violations-count` | Total number of violations found |
| `failed` | Whether the linter failed based on fail-on level |

## CLI Exit Codes

- **0** - No issues or issues below fail-on threshold
- **1** - Issues found at or above fail-on threshold
- **2** - Usage error (e.g., file not found, invalid arguments)

## Output Formats

The linter automatically selects the appropriate output format:
- **Text format** (default for local CLI usage) - Human-friendly, colored output grouped by severity
- **Annotations format** (default for CI/GitHub Actions) - GitHub Actions workflow commands for inline PR comments

You can explicitly set the format using the `--format` option.

### Text Format (default for CLI)

Human-friendly output with colors and grouping by severity level:
```
Warnings:
  ⚠ Line 10: [short-description] Short description is approaching maximum length (150 characters, recommended maximum 140)

Info:
  ℹ [donate-link] Consider adding a donate link to support the plugin
  ℹ [file-size] Readme file is getting large (8.5KB)
```

### GitHub Annotations (default for CI)

Produces inline PR comments in GitHub Actions:
```
::error file=readme.txt,line=5::[short-description] Short description is too long (160 characters, maximum 150)
```

### SARIF

Produces SARIF 2.1.0 output for GitHub Code Scanning integration.

### JSON

Produces structured JSON output:
```json
{
  "issues": [
    {
      "ruleId": "short-description",
      "level": "error",
      "message": "Short description is too long",
      "file": "readme.txt",
      "line": 5,
      "column": null
    }
  ],
  "summary": {
    "total": 1,
    "errors": 1,
    "warnings": 0,
    "info": 0
  }
}
```

## Advanced Usage

### GitHub Code Scanning Integration (SARIF)

For teams using GitHub's Code Scanning feature, the linter can output results in SARIF format:

```yaml
name: Lint Readme with Code Scanning

on:
  pull_request:
  push:
    branches:
      - main

jobs:
  lint:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      security-events: write

    steps:
      - uses: actions/checkout@v4

      - name: Lint readme.txt
        uses: thetwopct/wporg-plugin-readme-linter@v1
        with:
          path: readme.txt
          format: sarif
          output: readme-lint.sarif
        continue-on-error: true

      - name: Upload SARIF
        uses: github/codeql-action/upload-sarif@v3
        if: always()
        with:
          sarif_file: readme-lint.sarif
```

This displays linting results in the Security tab alongside other code scanning alerts.

## Comparison with Other Tools

### vs. Plugin Check

[Plugin Check](https://wordpress.org/plugins/plugin-check/) is the official WordPress.org tool and checks the entire plugin including PHP code. This linter focuses exclusively on `readme.txt` validation and is designed for CI integration.

### vs. PHPCS

While PHPCS can check code style, this tool specializes in readme validation with WordPress.org-specific rules and better CI integration.

## Development

### Requirements

- PHP 7.4 or higher
- Composer

### Setup

```bash
git clone https://github.com/thetwopct/wporg-plugin-readme-linter.git
cd wporg-plugin-readme-linter
composer install
```

### Running Tests

```bash
# All tests
composer test

# Unit tests only
vendor/bin/phpunit --testsuite=Unit

# Integration tests only
vendor/bin/phpunit --testsuite=Integration
```

### Code Quality

```bash
# Static analysis
vendor/bin/phpstan analyse

# Code style check
vendor/bin/phpcs

# Fix code style
vendor/bin/phpcbf
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- Built on top of [afragen/wordpress-plugin-readme-parser](https://github.com/afragen/wordpress-plugin-readme-parser)
- Inspired by WordPress.org Plugin Directory requirements

## Support & Feedback

- 🐛 [Issue Tracker](https://github.com/thetwopct/wporg-plugin-readme-linter/issues)
