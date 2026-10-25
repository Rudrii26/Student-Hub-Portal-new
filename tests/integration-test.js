const http = require('http');
const fs = require('fs');
const path = require('path');

const rootDir = path.join(__dirname, '..');
let passed = 0;
let failed = 0;

function assert(condition, desc) {
    if (condition) {
        console.log(`  ✓ ${desc}`);
        passed++;
    } else {
        console.error(`  ✗ FAIL: ${desc}`);
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

async function runIntegrationTests() {
    console.log("\n=======================================================");
    console.log("CHARUSAT STUDENT HUB - HTTP & END-TO-END INTEGRATION TEST");
    console.log("=======================================================\n");

    // 1. Test Server Endpoint Deliveries
    console.log("1. HTTP Endpoints over local server (http://127.0.0.1:3000/):");
    let childServer = null;
    try {
        try {
            await httpGet('http://127.0.0.1:3000/index.html');
        } catch (connErr) {
            const { fork } = require('child_process');
            childServer = fork(path.join(rootDir, 'server.js'), [], { silent: true });
            await new Promise(r => setTimeout(r, 600));
        }

        const indexRes = await httpGet('http://127.0.0.1:3000/index.html');
        assert(indexRes.statusCode === 200, "GET /index.html returns 200 OK");
        assert(indexRes.body.includes('id="dynamicFaqContainer"'), "index.html contains dynamicFaqContainer target element");
        assert(indexRes.body.includes('id="faqSearchInput"'), "index.html contains faqSearchInput");
        assert(indexRes.body.includes('id="faqPaginationContainer"'), "index.html contains faqPaginationContainer");

        const campusRes = await httpGet('http://127.0.0.1:3000/pages/campus.html');
        assert(campusRes.statusCode === 200, "GET /pages/campus.html returns 200 OK");
        assert(campusRes.body.includes('id="campusEventsGrid"'), "campus.html contains campusEventsGrid target container");
        assert(campusRes.body.includes('id="eventSearchInput"'), "campus.html contains eventSearchInput");
        assert(campusRes.body.includes('id="eventCategorySelect"'), "campus.html contains eventCategorySelect");
        assert(campusRes.body.includes('id="eventStatusSelect"'), "campus.html contains eventStatusSelect");
        assert(campusRes.body.includes('id="eventSortSelect"'), "campus.html contains eventSortSelect");
        assert(campusRes.body.includes('id="eventsPaginationContainer"'), "campus.html contains eventsPaginationContainer");

        const verifRes = await httpGet('http://127.0.0.1:3000/pages/verification.html');
        assert(verifRes.statusCode === 200, "GET /pages/verification.html returns 200 OK");
        assert(verifRes.body.includes('id="studentDirectoryTableBody"'), "verification.html contains studentDirectoryTableBody target tbody");
        assert(verifRes.body.includes('id="studentSearchInput"'), "verification.html contains studentSearchInput");
        assert(verifRes.body.includes('id="studentDeptSelect"'), "verification.html contains studentDeptSelect");
        assert(verifRes.body.includes('id="studentYearSelect"'), "verification.html contains studentYearSelect");
        assert(verifRes.body.includes('id="studentSortSelect"'), "verification.html contains studentSortSelect");
        assert(verifRes.body.includes('id="studentPaginationContainer"'), "verification.html contains studentPaginationContainer");

        // 2. Test JSON Delivery & Parsing over HTTP
        console.log("\n2. JSON Data API Fetch Testing:");
        const eventsRes = await httpGet('http://127.0.0.1:3000/data/events.json');
        assert(eventsRes.statusCode === 200, "GET /data/events.json returns 200 OK");
        assert(eventsRes.headers['content-type'].includes('json'), "Content-Type is application/json");
        const eventsData = JSON.parse(eventsRes.body);
        assert(Array.isArray(eventsData) && eventsData.length >= 15, `events.json parsed successfully (${eventsData.length} records)`);

        const studentsRes = await httpGet('http://127.0.0.1:3000/data/students.json');
        assert(studentsRes.statusCode === 200, "GET /data/students.json returns 200 OK");
        const studentsData = JSON.parse(studentsRes.body);
        assert(Array.isArray(studentsData) && studentsData.length >= 15, `students.json parsed successfully (${studentsData.length} records)`);

        const faqsRes = await httpGet('http://127.0.0.1:3000/data/faqs.json');
        assert(faqsRes.statusCode === 200, "GET /data/faqs.json returns 200 OK");
        const faqsData = JSON.parse(faqsRes.body);
        assert(Array.isArray(faqsData) && faqsData.length >= 15, `faqs.json parsed successfully (${faqsData.length} records)`);

        // 3. Test Script Assets Delivery
        console.log("\n3. Modular JavaScript Files Delivery:");
        const eventsJs = await httpGet('http://127.0.0.1:3000/js/events.js');
        assert(eventsJs.statusCode === 200 && eventsJs.body.includes('fetchEvents'), "js/events.js delivered and includes fetchEvents()");

        const studentsJs = await httpGet('http://127.0.0.1:3000/js/students.js');
        assert(studentsJs.statusCode === 200 && studentsJs.body.includes('fetchStudents'), "js/students.js delivered and includes fetchStudents()");

        const faqsJs = await httpGet('http://127.0.0.1:3000/js/faqs.js');
        assert(faqsJs.statusCode === 200 && faqsJs.body.includes('fetchFAQs'), "js/faqs.js delivered and includes fetchFAQs()");

        // 4. Test Logic: Multi-filter combinations
        console.log("\n4. Combined Multi-Filter & Search Logic Testing:");

        // Test combined events search + filter + sort
        const filteredEvents = eventsData
            .filter(e => e.category === 'Technical')
            .filter(e => e.status === 'Upcoming' || e.status === 'Open for Registration')
            .filter(e => e.title.toLowerCase().includes('hackathon') || e.description.toLowerCase().includes('hackathon'))
            .sort((a, b) => new Date(a.date) - new Date(b.date));

        assert(filteredEvents.length > 0, `Filtered Technical Hackathon events count: ${filteredEvents.length}`);
        assert(filteredEvents[0].title.includes('CodeForge'), "Found matching CodeForge hackathon");

        // Test combined students search + dept + sort
        const filteredStudents = studentsData
            .filter(s => s.department === 'Computer Engineering')
            .filter(s => s.year === '2nd Year')
            .sort((a, b) => b.cgpa - a.cgpa);

        assert(filteredStudents.length > 0, `2nd Year Computer Engineering students count: ${filteredStudents.length}`);
        assert(filteredStudents[0].cgpa >= 8.5, "Top student in subset has CGPA >= 8.5");

    } catch (err) {
        console.error("HTTP Integration test failed with error:", err);
        failed++;
    } finally {
        if (childServer) childServer.kill();
    }

    console.log("\n=======================================================");
    console.log(`INTEGRATION TEST SUMMARY: ${passed} Passed, ${failed} Failed`);
    console.log("=======================================================\n");

    process.exit(failed > 0 ? 1 : 0);
}

runIntegrationTests();
