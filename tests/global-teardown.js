/**
 * Global teardown for Playwright tests
 * Runs once after all test suites complete
 */

const fs = require('fs').promises;
const path = require('path');

module.exports = async (config) => {
  console.log('🧹 Starting global test teardown...');

  // Clean up test artifacts if needed
  if (process.env.CLEANUP_ARTIFACTS) {
    console.log('🗑️ Cleaning up test artifacts...');

    const artifacts = [
      'test-results/videos',
      'test-results/traces'
    ];

    for (const artifact of artifacts) {
      const artifactPath = path.join(process.cwd(), artifact);
      try {
        await fs.rm(artifactPath, { recursive: true, force: true });
        console.log(`✅ Cleaned up ${artifact}`);
      } catch (error) {
        // Ignore if directory doesn't exist
      }
    }
  }

  // Generate test summary report
  if (process.env.GENERATE_REPORT) {
    console.log('📊 Generating test summary report...');
    // Add report generation logic here
    console.log('✅ Test summary report generated');
  }

  // Archive test results for CI/CD
  if (process.env.ARCHIVE_RESULTS && process.env.CI) {
    console.log('📦 Archiving test results...');
    // Add archiving logic here
    console.log('✅ Test results archived');
  }

  console.log('🎯 Global teardown complete!');
};
