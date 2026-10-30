const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pagesDir = path.join(root, 'pages');
const pages = fs.readdirSync(pagesDir).filter(f => f.endsWith('.html'));

// Load all JS files and find defined function names
const jsFiles = [
    path.join(root, 'js', 'portal.js'),
    path.join(root, 'js', 'portal-data.js'),
    path.join(root, 'js', 'events.js'),
    path.join(root, 'js', 'students.js'),
    path.join(root, 'js', 'faqs.js'),
    path.join(root, 'js', 'script.js')
];

const globalDefinedFunctions = new Set([
    'alert', 'prompt', 'confirm', 'console.log', 'history.back', 'window.print', 'location.reload',
    'closeSidebar', 'toggleSidebar', 'logoutUser', 'openGlobalSearch', 'closeGlobalSearch', 'toggleTheme'
]);

function extractFunctions(code, targetSet) {
    // function name(...)
    const fnMatches = code.matchAll(/function\s+([a-zA-Z0-9_$]+)\s*\(/g);
    for (const m of fnMatches) targetSet.add(m[1]);

    // window.name = function or const name = (...) =>
    const varMatches = code.matchAll(/(?:window\.)?([a-zA-Z0-9_$]+)\s*=\s*(?:function|\([^)]*\)\s*=>)/g);
    for (const m of varMatches) targetSet.add(m[1]);

    // window.name = ...
    const winMatches = code.matchAll(/window\.([a-zA-Z0-9_$]+)\s*=/g);
    for (const m of winMatches) targetSet.add(m[1]);
}

for (const jsFile of jsFiles) {
    if (!fs.existsSync(jsFile)) continue;
    const content = fs.readFileSync(jsFile, 'utf8');
    extractFunctions(content, globalDefinedFunctions);
}

console.log(`Loaded ${globalDefinedFunctions.size} defined global JS functions.`);

const missingHandlers = [];

for (const p of pages) {
    const full = path.join(pagesDir, p);
    const content = fs.readFileSync(full, 'utf8');

    // Also extract functions declared in inline <script> tags of this specific page
    const pageFunctions = new Set(globalDefinedFunctions);
    const inlineScriptMatches = content.matchAll(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/gi);
    for (const sm of inlineScriptMatches) {
        extractFunctions(sm[1], pageFunctions);
    }

    const onclickMatches = content.matchAll(/onclick=["']([^"']+)["']/gi);
    for (const om of onclickMatches) {
        const expr = om[1].trim();
        // Extract function call: funcName(...)
        const fnCall = expr.match(/^([a-zA-Z0-9_$]+)\s*\(/);
        if (fnCall) {
            const fnName = fnCall[1];
            if (!pageFunctions.has(fnName)) {
                missingHandlers.push({
                    page: p,
                    expr,
                    fnName
                });
            }
        }
    }
}

if (missingHandlers.length === 0) {
    console.log('✓ All onclick handlers in pages/*.html map to defined functions!');
} else {
    console.log(`❌ Found ${missingHandlers.length} truly missing function handlers:`);
    for (const mh of missingHandlers) {
        console.log(`  [${mh.page}] ${mh.fnName} in: ${mh.expr}`);
    }
}
