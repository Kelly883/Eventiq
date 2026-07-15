#!/usr/bin/env node
/**
 * Reads MJML markup from stdin, writes JSON { html, errors } to stdout.
 * Invoked by Laravel via Process (see App\Services\MjmlRenderer).
 *
 * Usage: node render.js < template.mjml
 */
const mjml2html = require('mjml');

let input = '';
process.stdin.setEncoding('utf8');
process.stdin.on('data', (chunk) => { input += chunk; });
process.stdin.on('end', async () => {
  try {
    const result = await mjml2html(input, { validationLevel: 'soft' });
    process.stdout.write(JSON.stringify({
      html: result.html,
      errors: (result.errors || []).map((e) => e.formattedMessage || e.message),
    }));
    process.exit(0);
  } catch (err) {
    process.stdout.write(JSON.stringify({ html: null, errors: [err.message] }));
    process.exit(1);
  }
});
