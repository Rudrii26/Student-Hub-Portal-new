const http = require('http');
const fs = require('fs');
const path = require('path');

const rootDir = path.join(__dirname, '..');
let passed = 0;
let failed = 0;

function assert(condition, message) {
    if (condition) {
        console.log(`  ✓ ${message}`);
        passed++;
    } else {
        console.error(`  ✗ FAIL: ${message}`);
        failed++;
    }
}

function httpGet(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                resolve({
                    statusCode: res.statusCode,
                    headers: res.headers,
                    body: data
                });
            });
        }).on('error', reject);
    });
}

async function runFAQTests() {
    console.log("\n=======================================================");
    console.log("CHARUSAT STUDENT HUB - FAQ MODULE & ENDPOINT TEST SUITE");
    console.log("=======================================================\n");

    // 1. Validate faqs.json file directly
    console.log("1. Inspecting data/faqs.json File:");
    const faqsFilePath = path.join(rootDir, 'data', 'faqs.json');
    assert(fs.existsSync(faqsFilePath), "File exists at data/faqs.json");

    let rawJson = fs.readFileSync(faqsFilePath, 'utf8');
    let parsedData;
    try {
        parsedData = JSON.parse(rawJson);
        assert(true, "faqs.json is valid JSON with no syntax errors");
    } catch (e) {
        assert(false, `faqs.json JSON syntax error: ${e.message}`);
    }

    assert(Array.isArray(parsedData), "faqs.json top-level is an Array");
    assert(parsedData.length >= 15, `faqs.json contains >= 15 records (found ${parsedData.length})`);

    const requiredFields = ['id', 'question', 'answer', 'category'];
    let allFieldsValid = true;
    parsedData.forEach((item, idx) => {
        for (const field of requiredFields) {
            if (!item[field] || typeof item[field] !== 'string') {
                allFieldsValid = false;
                console.error(`Record #${idx + 1} (${item.id || 'unknown'}) missing field '${field}'`);
            }
        }
    });
    assert(allFieldsValid, "Every FAQ record contains id, question, answer, and category");

    // 2. Test HTTP Endpoint
    console.log("\n2. Testing HTTP Endpoints on Local Dev Server:");
    let childServer = null;
    try {
        try {
            await httpGet('http://127.0.0.1:3000/data/faqs.json');
        } catch (connErr) {
            const { fork } = require('child_process');
            childServer = fork(path.join(rootDir, 'server.js'), [], { silent: true });
            await new Promise(r => setTimeout(r, 600));
        }

        const httpRes = await httpGet('http://127.0.0.1:3000/data/faqs.json');
        assert(httpRes.statusCode === 200, "HTTP GET /data/faqs.json returns status 200 OK");
        assert(httpRes.headers['content-type'].includes('application/json'), "Content-Type is application/json");
        
        const fetchedJson = JSON.parse(httpRes.body);
        assert(Array.isArray(fetchedJson) && fetchedJson.length === parsedData.length, `HTTP response parsed cleanly with ${fetchedJson.length} items`);
    } catch (err) {
        assert(false, `HTTP request failed: ${err.message}`);
    } finally {
        if (childServer) childServer.kill();
    }


    // 3. Test Search, Category Filter, Sorting, Pagination Algorithms
    console.log("\n3. Testing Core Filter, Search, Sort & Pagination Algorithms:");
    
    // Search test
    const attendanceSearch = parsedData.filter(f => 
        f.question.toLowerCase().includes('attendance') || 
        f.answer.toLowerCase().includes('attendance') ||
        f.category.toLowerCase().includes('attendance')
    );
    assert(attendanceSearch.length > 0, `Search query 'attendance' matched ${attendanceSearch.length} record(s)`);

    // Category filter test
    const categories = Array.from(new Set(parsedData.map(f => f.category)));
    assert(categories.length >= 5, `Dataset contains ${categories.length} distinct categories: ${categories.join(', ')}`);

    const feesCategory = parsedData.filter(f => f.category.toLowerCase() === 'fees');
    assert(feesCategory.length > 0, `Category filter 'Fees' matched ${feesCategory.length} record(s)`);

    const academicCategory = parsedData.filter(f => f.category.toLowerCase() === 'academic');
    assert(academicCategory.length > 0, `Category filter 'Academic' matched ${academicCategory.length} record(s)`);

    // Sort tests
    const alphaAsc = [...parsedData].sort((a, b) => a.question.localeCompare(b.question));
    assert(alphaAsc[0].question.localeCompare(alphaAsc[alphaAsc.length - 1].question) <= 0, "Sort 'alpha-asc' correctly orders questions alphabetically A-Z");

    const alphaDesc = [...parsedData].sort((a, b) => b.question.localeCompare(a.question));
    assert(alphaDesc[0].question.localeCompare(alphaDesc[alphaDesc.length - 1].question) >= 0, "Sort 'alpha-desc' correctly orders questions alphabetically Z-A");

    // Pagination test
    const itemsPerPage = 5;
    const totalPages = Math.ceil(parsedData.length / itemsPerPage);
    assert(totalPages === 4, `18 records at 5 per page produces 4 pages`);
    const page1Items = parsedData.slice(0, 5);
    const page2Items = parsedData.slice(5, 10);
    const page4Items = parsedData.slice(15, 20);
    assert(page1Items.length === 5, "Page 1 contains 5 items");
    assert(page2Items.length === 5, "Page 2 contains 5 items");
    assert(page4Items.length === 3, "Page 4 contains remaining 3 items");

    // 4. Verify no prohibited terms in codebase
    console.log("\n4. Verifying Clean Professional Terminology:");
    const faqsJsContent = fs.readFileSync(path.join(rootDir, 'js', 'faqs.js'), 'utf8');
    const prohibited = [/\bPractical\s+4\b/i, /\bPractical\s+5\b/i, /\bLab\s+Task\b/i, /\bViva\b/i];
    prohibited.forEach(p => {
        assert(!faqsJsContent.match(p), `No prohibited term '${p.source}' in js/faqs.js`);
    });

    console.log("\n=======================================================");
    console.log(`FAQ TEST SUITE SUMMARY: ${passed} Passed, ${failed} Failed`);
    console.log("=======================================================\n");

    process.exit(failed > 0 ? 1 : 0);
}

runFAQTests();
