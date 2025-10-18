# Examples

This directory contains example configurations and usage scenarios for the WP.org Plugin Readme Linter.

## Custom Configuration

The `custom-config` directory shows how to use a custom configuration file to:
- Require additional sections (like FAQ)
- Ignore specific rules
- Allow "trunk" as a stable tag
- Fail on warnings instead of just errors

## Usage in CI/CD

See `.github/workflows/example-readme-lint.yml.example` in the repository root for complete workflow examples.

## Local Development

To test the linter locally with a custom config:

```bash
composer require --dev thetwopct/wporg-plugin-readme-linter
vendor/bin/wporg-plugin-readme-linter readme.txt --config=examples/custom-config/.wporg-readme-lint.json
```

## Testing Different Output Formats

### Annotations (for CI)
```bash
vendor/bin/wporg-plugin-readme-linter readme.txt
```

### JSON (for custom processing)
```bash
vendor/bin/wporg-plugin-readme-linter readme.txt --format=json --output=report.json
```

### SARIF (for code scanning)
```bash
vendor/bin/wporg-plugin-readme-linter readme.txt --format=sarif --output=report.sarif
```
