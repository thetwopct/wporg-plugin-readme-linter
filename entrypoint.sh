#!/bin/sh
set -e

# Parse inputs
README_PATH="${1:-readme.txt}"
FORMAT="${2:-annotations}"
FAIL_ON="${3:-error}"
CONFIG="${4:-.wporg-readme-lint.json}"
OUTPUT="${5:-}"
UPLOAD_SARIF="${6:-false}"

# Change to workspace directory
cd "${GITHUB_WORKSPACE}" || exit 1

# Build CLI arguments
ARGS="${README_PATH}"

if [ -n "${FORMAT}" ]; then
  ARGS="${ARGS} --format=${FORMAT}"
fi

if [ -n "${FAIL_ON}" ]; then
  ARGS="${ARGS} --fail-on=${FAIL_ON}"
fi

if [ -f "${CONFIG}" ]; then
  ARGS="${ARGS} --config=${CONFIG}"
fi

# Set output file for SARIF/JSON
if [ "${FORMAT}" = "sarif" ] || [ "${FORMAT}" = "json" ]; then
  if [ -z "${OUTPUT}" ]; then
    OUTPUT="wporg-readme-lint.${FORMAT}"
  fi
  ARGS="${ARGS} --output=${OUTPUT}"
fi

# Run the linter
echo "Running wporg-plugin-readme-linter..."
echo "Command: /app/bin/wporg-plugin-readme-linter ${ARGS}"

EXIT_CODE=0
/app/bin/wporg-plugin-readme-linter ${ARGS} || EXIT_CODE=$?

# Set outputs
if [ "${FORMAT}" = "sarif" ] && [ -f "${OUTPUT}" ]; then
  echo "sarif-file=${OUTPUT}" >> "${GITHUB_OUTPUT}"
fi

# Count violations from JSON output
if [ -f "${OUTPUT}" ] && [ "${FORMAT}" = "json" ]; then
  VIOLATIONS_COUNT=$(grep -o '"total":[0-9]*' "${OUTPUT}" | cut -d: -f2)
  echo "violations-count=${VIOLATIONS_COUNT}" >> "${GITHUB_OUTPUT}"
else
  echo "violations-count=0" >> "${GITHUB_OUTPUT}"
fi

if [ ${EXIT_CODE} -ne 0 ]; then
  echo "failed=true" >> "${GITHUB_OUTPUT}"
else
  echo "failed=false" >> "${GITHUB_OUTPUT}"
fi

# Upload SARIF if requested
if [ "${UPLOAD_SARIF}" = "true" ] && [ "${FORMAT}" = "sarif" ] && [ -f "${OUTPUT}" ]; then
  echo "Uploading SARIF to GitHub Code Scanning..."
  # The actual upload is handled by GitHub's upload-sarif action in the workflow
  # We just ensure the file exists
fi

exit ${EXIT_CODE}
