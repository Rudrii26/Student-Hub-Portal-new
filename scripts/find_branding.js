const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const filesToScan = [];

function walk(dir) {
    const list = fs.readdirSync(dir);
    for (const file of list) {
        if (file === '.git' || file === 'node_modules' || file === 'backups' || file === 'scratch_bak' || file === 'tests') continue;
        const full = path.join(dir, file);
        const stat = fs.statSync(full);
        if (stat.isDirectory()) {
            walk(full);
        } else if (file.endsWith('.html') || file.endsWith('.php') || file.endsWith('.js') || file.endsWith('.css') || file.endsWith('.json')) {
            filesToScan.push(full);
        }
    }
}
walk(root);

console.log('Total files to scan for branding:', filesToScan.length);

const patterns = [
    /\bPractical\s*\d*\b/gi,
    /CHARUSAT\s+e-Governance/gi,
    /e-Governance/gi,
    /\bExperiment\s*\d*\b/gi,
    /\bLab\s+Task\b/gi
];

const matches = [];

for (const file of filesToScan) {
    const content = fs.readFileSync(file, 'utf8');
    const rel = path.relative(root, file);
    const lines = content.split('\n');
    lines.forEach((line, index) => {
        // Skip comment lines in JS or PHP that are purely internal author notes if not in UI
        for (const pat of patterns) {
            if (pat.test(line)) {
                matches.push({
                    file: rel,
                    lineNum: index + 1,
                    line: line.trim()
                });
                break;
            }
        }
    });
}

console.log(`Found ${matches.length} matches:`);
matches.forEach(m => {
    console.log(`[${m.file}:${m.lineNum}] ${m.line}`);
});
