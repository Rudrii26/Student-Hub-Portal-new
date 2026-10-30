const fs = require('fs');
const path = require('path');

const pagesDir = path.resolve(__dirname, '..', 'pages');
const pages = fs.readdirSync(pagesDir).filter(f => f.endsWith('.html'));

console.log('Inspecting interactive features of pages/*.html:');

for (const p of pages) {
    const full = path.join(pagesDir, p);
    const content = fs.readFileSync(full, 'utf8');

    // Look for script tags
    const scripts = [];
    const scriptMatches = content.matchAll(/<script[^>]*src=["']([^"']+)["'][^>]*>/gi);
    for (const sm of scriptMatches) scripts.push(sm[1]);

    // Look for inline onclick handlers
    const onclicks = [];
    const onclickMatches = content.matchAll(/onclick=["']([^"']+)["']/gi);
    for (const om of onclickMatches) onclicks.push(om[1]);

    // Look for form IDs and inputs
    const formIds = [];
    const formMatches = content.matchAll(/<form[^>]*id=["']([^"']+)["'][^>]*>/gi);
    for (const fm of formMatches) formIds.push(fm[1]);

    // Look for modal IDs
    const modalIds = [];
    const modalMatches = content.matchAll(/<div[^>]*id=["']([^"']+)["'][^>]*class=["'][^"']*modal[^"']*["']/gi);
    for (const mm of modalMatches) modalIds.push(mm[1]);

    console.log(`\n📄 [pages/${p}]`);
    console.log(`   Scripts: ${scripts.join(', ') || 'none'}`);
    console.log(`   Onclick actions count: ${onclicks.length}`);
    if (formIds.length > 0) console.log(`   Forms: ${formIds.join(', ')}`);
    if (modalIds.length > 0) console.log(`   Modals: ${modalIds.join(', ')}`);
}
