const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const htmlAndPhp = [];

function walk(dir) {
    const list = fs.readdirSync(dir);
    for (const file of list) {
        if (file === '.git' || file === 'node_modules' || file === 'backups' || file === 'scratch_bak') continue;
        const full = path.join(dir, file);
        const stat = fs.statSync(full);
        if (stat.isDirectory()) {
            walk(full);
        } else if (file.endsWith('.html') || (file.endsWith('.php') && !file.includes('.bak'))) {
            htmlAndPhp.push(full);
        }
    }
}
walk(root);

console.log('Total HTML & PHP files to inspect:', htmlAndPhp.length);

const inventory = [];
let totalBrokenLinks = 0;

for (const file of htmlAndPhp) {
    const content = fs.readFileSync(file, 'utf8');
    const rel = path.relative(root, file);

    const titleMatch = content.match(/<title>([^<]*)<\/title>/i);
    const title = titleMatch ? titleMatch[1].trim() : '(No title)';

    // Form actions
    const forms = [];
    const formMatches = content.matchAll(/<form[^>]*action=["']([^"']*)["'][^>]*>/gi);
    for (const fm of formMatches) {
        forms.push(fm[1]);
    }

    // Scripts
    const scripts = [];
    const scriptMatches = content.matchAll(/<script[^>]*src=["']([^"']+)["'][^>]*>/gi);
    for (const sm of scriptMatches) {
        scripts.push(sm[1]);
    }

    // Stylesheets
    const styles = [];
    const styleMatches = content.matchAll(/<link[^>]*href=["']([^"']+)["'][^>]*rel=["']stylesheet["']|<link[^>]*rel=["']stylesheet["'][^>]*href=["']([^"']+)["']/gi);
    for (const stm of styleMatches) {
        styles.push(stm[1] || stm[2]);
    }

    // Check all hrefs
    const brokenLinks = [];
    const hrefMatches = content.matchAll(/href=["']([^"'#][^"']*)["']/gi);
    const fileDir = path.dirname(file);

    for (const hm of hrefMatches) {
        const link = hm[1];
        if (link.startsWith('http://') || link.startsWith('https://') || link.startsWith('mailto:') || link.startsWith('tel:') || link.startsWith('javascript:')) {
            continue;
        }
        const cleanLink = link.split('?')[0];
        const targetPath = path.resolve(fileDir, cleanLink);
        if (!fs.existsSync(targetPath)) {
            brokenLinks.push(link);
            totalBrokenLinks++;
        }
    }

    // Check broken script src
    const brokenScripts = [];
    for (const s of scripts) {
        if (s.startsWith('http://') || s.startsWith('https://')) continue;
        const cleanScript = s.split('?')[0];
        const targetScript = path.resolve(fileDir, cleanScript);
        if (!fs.existsSync(targetScript)) {
            brokenScripts.push(s);
        }
    }

    // Check broken style href
    const brokenStyles = [];
    for (const st of styles) {
        if (st.startsWith('http://') || st.startsWith('https://')) continue;
        const cleanStyle = st.split('?')[0];
        const targetStyle = path.resolve(fileDir, cleanStyle);
        if (!fs.existsSync(targetStyle)) {
            brokenStyles.push(st);
        }
    }

    // Count interactive elements
    const buttons = (content.match(/<button/gi) || []).length;
    const inputs = (content.match(/<input/gi) || []).length;
    const selects = (content.match(/<select/gi) || []).length;
    const modals = (content.match(/class=["'][^"']*modal[^"']*["']/gi) || []).length;

    inventory.push({
        rel,
        title,
        forms,
        scriptsCount: scripts.length,
        brokenLinks,
        brokenScripts,
        brokenStyles,
        buttons,
        inputs,
        selects,
        modals
    });
}

console.log('\n================ PAGE INVENTORY & AUDIT ================');
for (const item of inventory) {
    console.log(`\nPAGE: [${item.rel}]`);
    console.log(`   Title: "${item.title}"`);
    console.log(`   Interactive: ${item.buttons} buttons, ${item.inputs} inputs, ${item.selects} selects, ${item.modals} modal elements`);
    if (item.forms.length > 0) console.log(`   Forms: ${item.forms.join(', ')}`);
    if (item.brokenLinks.length > 0) console.log(`   BROKEN LINKS (${item.brokenLinks.length}): ${item.brokenLinks.join(', ')}`);
    if (item.brokenScripts.length > 0) console.log(`   BROKEN SCRIPTS: ${item.brokenScripts.join(', ')}`);
    if (item.brokenStyles.length > 0) console.log(`   BROKEN STYLES: ${item.brokenStyles.join(', ')}`);
}

console.log(`\nTotal broken links across site: ${totalBrokenLinks}`);
