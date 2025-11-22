# Easy English WP (Lite) build notes

- This plugin is intended to be drop-in without a build step.
- Future build scripts (npm/composer) could live here if needed for packaging to WordPress.org.
- To package the Free version manually, zip the contents of the `easy-english-wp` folder (excluding version control and tests) and upload to WordPress.
- If adding tooling later, keep it optional so the plugin still activates without dependencies.

<!-- Suggested placeholders:
{
  "scripts": {
    "build": "echo \"Add build steps here (optional)\"",
    "zip": "zip -r ../easy-english-wp.zip . -x \"*.git*\" \"tests/*\" \"build/*\""
  }
}
-->
