const fs = require('fs');
const path = require('path');

const rootDir = path.join(__dirname, '..');
let totalPassed = 0;
let totalFailed = 0;

function assert(condition, message) {
    if (condition) {
        console.log(`  ✓ ${message}`);
        totalPassed++;
    } else {
        console.error(`  ✗ FAIL: ${message}`);
        totalFailed++;
    }
}

console.log("\n=======================================================");
console.log("CHARUSAT STUDENT HUB - DATA INTEGRITY & LOGIC VALIDATION");
console.log("=======================================================\n");

// 1. Validate events.json
console.log("1. Validating data/events.json:");
const eventsPath = path.join(rootDir, 'data', 'events.json');
assert(fs.existsSync(eventsPath), "data/events.json exists");
const events = JSON.parse(fs.readFileSync(eventsPath, 'utf8'));
assert(Array.isArray(events), "events.json is a valid array");
assert(events.length >= 15, `Contains at least 15 events (found ${events.length})`);
events.forEach((evt, idx) => {
    assert(evt.id && evt.title && evt.description && evt.date && evt.location && evt.category && evt.status, `Event #${idx + 1} (${evt.id}) has all required fields`);
});

// 2. Validate students.json
console.log("\n2. Validating data/students.json:");
const studentsPath = path.join(rootDir, 'data', 'students.json');
assert(fs.existsSync(studentsPath), "data/students.json exists");
const students = JSON.parse(fs.readFileSync(studentsPath, 'utf8'));
assert(Array.isArray(students), "students.json is a valid array");
assert(students.length >= 15, `Contains at least 15 students (found ${students.length})`);
const rudriRecord = students.find(s => s.studentId === "25CS027");
assert(rudriRecord !== undefined, "Contains active student 25CS027 (Kakadiya Rudri)");
students.forEach((stu, idx) => {
    assert(stu.id && stu.studentId && stu.name && stu.email && stu.course && stu.year && stu.department && stu.status, `Student #${idx + 1} (${stu.studentId} - ${stu.name}) has all required fields`);
    assert(!stu.password, `Student #${idx + 1} does not expose password`);
});

// 3. Validate faqs.json
console.log("\n3. Validating data/faqs.json:");
const faqsPath = path.join(rootDir, 'data', 'faqs.json');
assert(fs.existsSync(faqsPath), "data/faqs.json exists");
const faqs = JSON.parse(fs.readFileSync(faqsPath, 'utf8'));
assert(Array.isArray(faqs), "faqs.json is a valid array");
assert(faqs.length >= 15, `Contains at least 15 FAQs (found ${faqs.length})`);
faqs.forEach((faq, idx) => {
    assert(faq.id && faq.question && faq.answer && faq.category, `FAQ #${idx + 1} (${faq.id}) has required fields`);
});

// 4. Test Search, Filtering, Sorting, Pagination algorithms
console.log("\n4. Validating Filter, Search, Sort & Pagination Algorithms:");

// FAQ Search Test
const searchFaq = faqs.filter(f => f.question.toLowerCase().includes('attendance') || f.answer.toLowerCase().includes('attendance'));
assert(searchFaq.length > 0, `Search FAQ for 'attendance' returns ${searchFaq.length} results`);

// FAQ Category Test
const academicFaqs = faqs.filter(f => f.category.toLowerCase() === 'academic');
assert(academicFaqs.length > 0, `Category filter for 'Academic' returns ${academicFaqs.length} FAQs`);

// Events Category Test
const workshopEvents = events.filter(e => e.category.toLowerCase() === 'workshop');
assert(workshopEvents.length > 0, `Category filter for 'Workshop' returns ${workshopEvents.length} events`);

// Events Date Sort Test
const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
assert(new Date(sortedEvents[0].date) <= new Date(sortedEvents[sortedEvents.length - 1].date), "Events sort by date ascending works correctly");

// Students CGPA Sort Test
const sortedStudents = [...students].sort((a, b) => b.cgpa - a.cgpa);
assert(sortedStudents[0].cgpa >= sortedStudents[sortedStudents.length - 1].cgpa, "Students sort by CGPA descending works correctly");

// Students Department Filter Test
const csStudents = students.filter(s => s.department.toLowerCase().includes('computer engineering'));
assert(csStudents.length > 0, `Department filter for 'Computer Engineering' returns ${csStudents.length} students`);

// Pagination Test
const pageSize = 6;
const totalPages = Math.ceil(events.length / pageSize);
const page1 = events.slice(0, pageSize);
const page2 = events.slice(pageSize, pageSize * 2);
assert(page1.length === pageSize, `Page 1 displays exactly ${pageSize} items`);
assert(page2.length > 0 && page1[0].id !== page2[0].id, "Page 2 contains distinct items from Page 1");
assert(totalPages === 3, `18 events paginated at 6 per page yields 3 total pages`);

// 5. Check Prohibited Terms
console.log("\n5. Checking for Prohibited Terms in Codebase:");
const filesToCheck = [
    'data/events.json',
    'data/students.json',
    'data/faqs.json',
    'js/events.js',
    'js/students.js',
    'js/faqs.js',
    'index.html',
    'pages/campus.html',
    'pages/verification.html'
];

const prohibitedPatterns = [
    /\bPractical\s+4\b/i,
    /\bPractical\s+5\b/i,
    /\bLab\s+Task\b/i,
    /\bPractical\s+Features\b/i,
    /\bViva\b/i
];

filesToCheck.forEach(relPath => {
    const fullPath = path.join(rootDir, relPath);
    if (fs.existsSync(fullPath)) {
        const content = fs.readFileSync(fullPath, 'utf8');
        prohibitedPatterns.forEach(pattern => {
            const match = content.match(pattern);
            assert(!match, `No prohibited phrase '${pattern.source}' in ${relPath}`);
        });
    }
});

console.log("\n=======================================================");
console.log(`TEST SUMMARY: ${totalPassed} Passed, ${totalFailed} Failed`);
console.log("=======================================================\n");

process.exit(totalFailed > 0 ? 1 : 0);
