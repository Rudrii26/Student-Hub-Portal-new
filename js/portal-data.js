/* ==========================================================================
   CHARUSAT UNIVERSITY - STUDENT HUB PORTAL DATA ENGINE
   Charotar University of Science and Technology (CHARUSAT), Changa, Gujarat
   Official Student Hub Portal Integration Model & Curriculum Data
   Student Record: Kakadiya Rudri Alpeshbhai (25CS027) • CSPIT Computer Engineering
   ========================================================================== */

const PORTAL_DATA_VERSION = "2026.4.0_25CS027";

const DATA_SOURCE_TIERS = {
    OFFICIAL: { key: "official", label: "CHARUSAT Official", badgeClass: "badge-source-official", icon: "🏷️" },
    IMPORTED: { key: "imported", label: "Imported Record", badgeClass: "badge-source-imported", icon: "📥" },
    MANUAL: { key: "manual", label: "User Entered", badgeClass: "badge-source-manual", icon: "✏️" },
    CALCULATED: { key: "calculated", label: "Calculated by Career OS", badgeClass: "badge-source-calculated", icon: "🧮" },
    AI_GENERATED: { key: "ai", label: "AI Recommendation", badgeClass: "badge-source-ai", icon: "✨" }
};

const DEFAULT_PORTAL_DATA = {
    version: PORTAL_DATA_VERSION,
    metadata: {
        source: "CHARUSAT",
        sourceType: "official_record",
        lastUpdated: "2026-10-02 21:00",
        lastSyncDate: "October 2, 2026",
        verified: true,
        freshness: "Live Synchronized"
    },

    university: {
        name: "Charotar University of Science and Technology",
        shortName: "CHARUSAT",
        accreditation: "NAAC A+ Accredited University • State Private University",
        campus: "Education Campus, Changa - 388421, Ta. Petlad, Dist. Anand, Gujarat, India",
        website: "https://www.charusat.ac.in",
        egovPortal: "https://support.charusat.edu.in/egov/home.aspx",
        examResultUrl: "https://support.charusat.edu.in/UniExamResult/",
        feesPaymentUrl: "https://support.charusat.edu.in/feespaymentapp/",
        transcriptsUrl: "https://support.charusat.edu.in/Transcript/",
        helpdeskEmail: "convener.itcell@charusat.ac.in",
        contactNumber: "+91 2697 265011 / 265021"
    },

    student: {
        id: "25CS027",
        enrollmentNo: "25CS027",
        name: "Kakadiya Rudri Alpeshbhai",
        shortName: "Rudri Kakadiya",
        initials: "KR",
        dob: "2007-09-15",
        dobFormatted: "15 September 2007",
        gender: "Female",
        bloodGroup: "NA",
        email: "25cs027@charusat.edu.in",
        alternateEmail: "rudrikakadiya26@gmail.com",
        phone: "+91 92745 04643",
        alternatePhone: "+91 98242 40351",
        institute: "CSPIT - Chandubhai S. Patel Institute of Technology",
        instituteCode: "CSPIT",
        degree: "B.Tech (Computer Engineering)",
        branch: "Computer Engineering",
        department: "Computer Engineering",
        semester: "Semester 3",
        semesterNum: 3,
        academicYear: "2026 - 2027",
        batch: "2025 - 2029",
        section: "CS-1",
        rollNo: "25CS027",
        mentor: "Prof. Vaibhavi Patel (Faculty Mentor, CSPIT)",
        cgpa: 8.53,
        sgpa: 8.56,
        classRank: 5,
        creditsCompleted: 44,
        totalCredits: 164,
        hostel: "Tej Smruti Girls Hostel (H5), CHARUSAT",
        room: "Room 505-H5 (Tej Smruti)",
        permanentAddress: "Shukan Sky, Dabholi Link Road, Katargam, Surat, Gujarat, India - 395004",
        currentAddress: "Room 505-H5, Tej Smruti Girls Hostel, CHARUSAT Campus, Changa - 388421",
        sourceMeta: { source: "CHARUSAT", sourceType: "official_import", verified: true, updated: "2026-10-02" }
    },

    parents: {
        father: {
            name: "Alpeshbhai Kakadiya",
            relation: "Father",
            occupation: "Business Executive",
            phone: "+91 98242 40351",
            email: "alpeshbhai.kakadiya@gmail.com",
            address: "Shukan Sky, Dabholi Link Road, Katargam, Surat, Gujarat - 395004"
        },
        mother: {
            name: "Paritaben Kakadiya",
            relation: "Mother",
            occupation: "Homemaker",
            phone: "+91 70693 83741",
            email: "paritaben.kakadiya@gmail.com",
            address: "Shukan Sky, Dabholi Link Road, Katargam, Surat, Gujarat - 395004"
        },
        guardian: {
            name: "Alpeshbhai Kakadiya",
            relation: "Primary Guardian (Father)",
            occupation: "Business Executive",
            phone: "+91 98242 40351",
            email: "alpeshbhai.kakadiya@gmail.com",
            address: "Shukan Sky, Dabholi Link Road, Katargam, Surat, Gujarat - 395004"
        },
        emergencyContact: {
            name: "Alpeshbhai Kakadiya (Father)",
            phone: "+91 98242 40351",
            relation: "Primary Guardian"
        },
        sourceMeta: { source: "User", sourceType: "user_authorized", verified: true, updated: "2026-10-02" }
    },

    stats: {
        attendanceRate: 89.0,
        currentCGPA: 8.53,
        currentSGPA: 8.56,
        pendingCollegeFees: 35000,
        pendingHostelFees: 12000,
        pendingAssignments: 3,
        upcomingExams: 5,
        sourceMeta: { source: "CHARUSAT", sourceType: "official_calculation", verified: true }
    },

    notifications: [
        { id: 1, title: "CHARUSAT Mid-Sem Exam Schedule Released", desc: "Mid-Semester theory exams commence from 20th October 2026 for 3rd Sem CS. Check under Exams & Results.", time: "1 hour ago", unread: true, link: "exams-results.html" },
        { id: 2, title: "Semester 3 Tuition Fee Clearance", desc: "Balance installment of ₹35,000 for Sem 3 is due by 15th October 2026.", time: "3 hours ago", unread: true, link: "college-fees.html" },
        { id: 3, title: "CS201 Data Structures Assignment 2 Due", desc: "Binary Search Trees & AVL Balancing submission deadline is tomorrow at 11:59 PM.", time: "5 hours ago", unread: true, link: "assignments.html" },
        { id: 4, title: "SPOURAL 2026 - CHARUSAT Annual Fest", desc: "Registration open for Technical & Cultural competitions at CSPIT Auditorium.", time: "Yesterday", unread: false, link: "campus.html" }
    ],

    courses: [
        {
            code: "CS201",
            name: "Data Structures & Algorithms",
            type: "Theory + Practical (Core)",
            credits: 4,
            faculty: "Prof. Vaibhavi Patel (Faculty Mentor)",
            attendance: "92.5%",
            description: "Fundamental data structures, asymptotic notation, Arrays, Stacks, Queues, Linked Lists, Trees (BST, AVL, B-Trees), Heaps, Graphs, and algorithm analysis with dynamic programming.",
            units: [
                { unit: "Unit 1", title: "Linear Data Structures & Analysis", topics: "Time and Space Complexity, Big-O Notation, Arrays, Stacks, Applications of Stacks (Infix to Postfix, Recursion), Queue & Circular Queues" },
                { unit: "Unit 2", title: "Linked Lists & Advanced Variations", topics: "Singly, Doubly and Circular Linked Lists, Polynomial Representation, Sparse Matrix representation and manipulation" },
                { unit: "Unit 3", title: "Non-Linear Structures: Trees & Graphs", topics: "Binary Trees, BST Operations, Tree Traversals, AVL Tree rotations, Red-Black Trees, Graph Traversals (BFS, DFS), Shortest Path (Dijkstra, Prim, Kruskal)" },
                { unit: "Unit 4", title: "Searching, Sorting & Hashing", topics: "Divide and Conquer (Merge Sort, Quick Sort), Heap Sort, Hash Tables, Collision Resolution Techniques, Dynamic Programming introduction" }
            ]
        },
        {
            code: "CS202",
            name: "Database Management Systems",
            type: "Theory + Practical (Core)",
            credits: 4,
            faculty: "Prof. Hiren Mewada (Assistant Professor)",
            attendance: "89.5%",
            description: "Relational database concepts, ER modeling, Relational Algebra, SQL DDL/DML, Normalization (1NF to BCNF), Transaction Processing, ACID properties, Concurrency Control, and NoSQL basics.",
            units: [
                { unit: "Unit 1", title: "Database Architecture & ER Modeling", topics: "Data abstraction, 3-tier DBMS architecture, Data models, Entity-Relationship (ER) & Extended ER diagrams, Constraints" },
                { unit: "Unit 2", title: "Relational Model & SQL Query Language", topics: "Relational Algebra operations, SQL DDL, DML, Complex Joins, Nested Subqueries, Views, Triggers, and Stored Procedures" },
                { unit: "Unit 3", title: "Relational Design & Normalization", topics: "Functional Dependencies, Lossless Decomposition, Normal forms: 1NF, 2NF, 3NF, BCNF, Multi-valued dependencies & 4NF" },
                { unit: "Unit 4", title: "Transactions & Concurrency Control", topics: "ACID properties, Serializability, Lock-based protocols, Two-Phase Locking (2PL), Deadlock handling, Write-Ahead Logging" }
            ]
        },
        {
            code: "CS203",
            name: "Object-Oriented Programming with Java",
            type: "Theory + Practical (Core)",
            credits: 4,
            faculty: "Prof. Parth Goel (Assistant Professor)",
            attendance: "91.7%",
            description: "Object-oriented paradigms, JVM internals, Classes, Inheritance, Polymorphism, Abstract classes, Interfaces, Exception Handling, Multi-threading, Java Collections, and Stream API.",
            units: [
                { unit: "Unit 1", title: "Java OOP Fundamentals & Class Design", topics: "Bytecode, JVM architecture, Primitive types, Class constructors, this and super keywords, Method overloading, Garbage Collection" },
                { unit: "Unit 2", title: "Inheritance, Interfaces & Packages", topics: "Single, Multi-level, Hierarchical inheritance, Dynamic method dispatch, Abstract classes, Interface design, Custom packages" },
                { unit: "Unit 3", title: "Exception Handling & Multi-Threading", topics: "Try-catch-finally blocks, Custom exceptions, Checked vs Unchecked exceptions, Thread lifecycle, Synchronization, Inter-thread communication" },
                { unit: "Unit 4", title: "Java Collections Framework & I/O", topics: "ArrayList, LinkedList, HashSet, HashMap, Iterators, Generics, Stream API filters, File I/O streams, Serialization" }
            ]
        },
        {
            code: "CS204",
            name: "Digital Logic & Computer Organization",
            type: "Theory + Practical (Core)",
            credits: 4,
            faculty: "Dr. Dweepna Garg (Associate Professor)",
            attendance: "87.5%",
            description: "Number systems, Boolean algebra, Combinational and Sequential circuit design, Instruction Set Architecture (ISA), ALU design, Control unit design, Memory hierarchy, and Pipelining.",
            units: [
                { unit: "Unit 1", title: "Boolean Algebra & Combinational Circuits", topics: "Karnaugh Maps, Quine-McCluskey minimization, Adders, Subtractors, Multiplexers, Demultiplexers, Encoders, Decoders" },
                { unit: "Unit 2", title: "Sequential Circuits & Memory Elements", topics: "Latches, Flip-Flops (SR, JK, D, T), Master-Slave configurations, State diagrams, Synchronous & Asynchronous counters, Shift Registers" },
                { unit: "Unit 3", title: "Register Transfer & Computer Arithmetic", topics: "Bus and memory transfers, Arithmetic Logic Shift Unit (ALU), Booth's multiplication algorithm, Restoring & Non-restoring division" },
                { unit: "Unit 4", title: "Memory Hierarchy & Pipeline Architecture", topics: "Cache memory mapping (Direct, Associative, Set-Associative), Virtual memory, Pipelining hazards (Data, Structural, Control)" }
            ]
        },
        {
            code: "MA201",
            name: "Discrete Mathematics & Linear Algebra",
            type: "Theory (Foundation)",
            credits: 4,
            faculty: "Dr. Bharat Patel (Professor, Math Dept)",
            attendance: "86.1%",
            description: "Propositional logic, Predicate calculus, Set theory, Relations, Functions, Recurrence relations, Graph theory (Eulerian, Hamiltonian graphs, Trees), Vector spaces, Eigenvalues and Eigenvectors.",
            units: [
                { unit: "Unit 1", title: "Mathematical Logic & Proof Techniques", topics: "Propositional equivalence, Predicates & Quantifiers, Rules of Inference, Direct Proof, Proof by Contradiction, Mathematical Induction" },
                { unit: "Unit 2", title: "Sets, Relations, Functions & Recurrence", topics: "Set operations, Equivalence relations, Partial Order Sets (Posets), Hasse diagrams, Lattices, Solving linear recurrence relations" },
                { unit: "Unit 3", title: "Graph Theory & Tree Structures", topics: "Graph representations (Adjacency Matrix/List), Isomorphism, Connectivity, Euler and Hamiltonian paths, Planar graphs, Graph coloring" },
                { unit: "Unit 4", title: "Linear Algebra: Vector Spaces & Matrices", topics: "Vector spaces, Subspaces, Linear independence, Basis and Dimension, Linear transformations, Eigenvalues, Eigenvectors, Cayley-Hamilton" }
            ]
        },
        {
            code: "CS205",
            name: "Design Engineering & Professional Ethics",
            type: "Practical / Project (Foundation)",
            credits: 2,
            faculty: "Prof. Snehal Sathwara (Assistant Professor)",
            attendance: "85.7%",
            description: "Design Thinking methodology, Empathy canvas, Ideation, Mind-mapping, Rapid Prototyping, Validation, Professional ethics, Intellectual Property Rights (IPR), and Cyber Laws.",
            units: [
                { unit: "Unit 1", title: "Design Thinking Methodology & Empathy", topics: "Human-centered design, Observation techniques, AEIOU framework, Empathy mapping, Problem definition canvas" },
                { unit: "Unit 2", title: "Ideation, Canvas & Product Development", topics: "Ideation canvases, SCAMPER technique, Prioritization matrix, Product development canvas (PDC), Minimal Viable Product (MVP)" },
                { unit: "Unit 3", title: "Prototyping, Testing & Validation", topics: "Low-fidelity and High-fidelity prototypes, Usability testing, User feedback loops, Iterative design enhancement" },
                { unit: "Unit 4", title: "Engineering Ethics & Cyber Law", topics: "Code of ethics, Safety and risk responsibility, Intellectual Property Rights (Patents, Copyrights), Cyber ethics and data privacy" }
            ]
        }
    ],

    timetableToday: [
        { time: "09:15 AM - 10:15 AM", subject: "Data Structures & Algorithms", code: "CS201", faculty: "Prof. Vaibhavi Patel", room: "CSPIT LT-104", type: "Lecture", status: "completed" },
        { time: "10:15 AM - 11:15 AM", subject: "Database Management Systems", code: "CS202", faculty: "Prof. Hiren Mewada", room: "CSPIT LT-104", type: "Lecture", status: "active" },
        { time: "11:30 AM - 01:30 PM", subject: "Data Structures Lab (CS-1)", code: "CS201P", faculty: "Prof. Vaibhavi Patel", room: "CSPIT Lab-04", type: "Practical", status: "upcoming" },
        { time: "02:15 PM - 03:15 PM", subject: "Object-Oriented Programming (Java)", code: "CS203", faculty: "Prof. Parth Goel", room: "CSPIT LT-104", type: "Lecture", status: "upcoming" },
        { time: "03:15 PM - 04:15 PM", subject: "Discrete Mathematics", code: "MA201", faculty: "Dr. Bharat Patel", room: "CSPIT LT-104", type: "Lecture", status: "upcoming" }
    ],

    weeklyTimetable: {
        monday: [
            { time: "09:15 AM - 10:15 AM", subject: "Data Structures & Algorithms", faculty: "Prof. Vaibhavi Patel", room: "LT-104" },
            { time: "10:15 AM - 11:15 AM", subject: "Database Management Systems", faculty: "Prof. Hiren Mewada", room: "LT-104" },
            { time: "11:30 AM - 01:30 PM", subject: "Data Structures Lab", faculty: "Prof. Vaibhavi Patel", room: "Lab-04" },
            { time: "02:15 PM - 03:15 PM", subject: "OOP with Java", faculty: "Prof. Parth Goel", room: "LT-104" },
            { time: "03:15 PM - 04:15 PM", subject: "Discrete Mathematics", faculty: "Dr. Bharat Patel", room: "LT-104" }
        ],
        tuesday: [
            { time: "09:15 AM - 10:15 AM", subject: "Digital Logic & Computer Org", faculty: "Dr. Dweepna Garg", room: "LT-104" },
            { time: "10:15 AM - 11:15 AM", subject: "Data Structures & Algorithms", faculty: "Prof. Vaibhavi Patel", room: "LT-104" },
            { time: "11:30 AM - 01:30 PM", subject: "DBMS Lab", faculty: "Prof. Hiren Mewada", room: "Lab-02" },
            { time: "02:15 PM - 03:15 PM", subject: "Design Engineering & Ethics", faculty: "Prof. Snehal Sathwara", room: "LT-104" },
            { time: "03:15 PM - 04:15 PM", subject: "Mentoring & Counseling Hour", faculty: "Prof. Vaibhavi Patel", room: "LT-104" }
        ],
        wednesday: [
            { time: "09:15 AM - 10:15 AM", subject: "OOP with Java", faculty: "Prof. Parth Goel", room: "LT-104" },
            { time: "10:15 AM - 11:15 AM", subject: "Database Management Systems", faculty: "Prof. Hiren Mewada", room: "LT-104" },
            { time: "11:30 AM - 01:30 PM", subject: "Java Programming Lab", faculty: "Prof. Parth Goel", room: "Lab-06" },
            { time: "02:15 PM - 03:15 PM", subject: "Discrete Mathematics", faculty: "Dr. Bharat Patel", room: "LT-104" },
            { time: "03:15 PM - 04:15 PM", subject: "Universal Human Values", faculty: "Prof. Snehal Sathwara", room: "LT-104" }
        ],
        thursday: [
            { time: "09:15 AM - 10:15 AM", subject: "Digital Logic & Computer Org", faculty: "Dr. Dweepna Garg", room: "LT-104" },
            { time: "10:15 AM - 11:15 AM", subject: "Data Structures & Algorithms", faculty: "Prof. Vaibhavi Patel", room: "LT-104" },
            { time: "11:30 AM - 01:30 PM", subject: "Digital Logic & Hardware Lab", faculty: "Dr. Dweepna Garg", room: "HW Lab-1" },
            { time: "02:15 PM - 03:15 PM", subject: "Discrete Mathematics", faculty: "Dr. Bharat Patel", room: "LT-104" },
            { time: "03:15 PM - 04:15 PM", subject: "Design Thinking Studio", faculty: "Prof. Snehal Sathwara", room: "Design Lab" }
        ],
        friday: [
            { time: "09:15 AM - 10:15 AM", subject: "OOP with Java", faculty: "Prof. Parth Goel", room: "LT-104" },
            { time: "10:15 AM - 11:15 AM", subject: "Database Management Systems", faculty: "Prof. Hiren Mewada", room: "LT-104" },
            { time: "11:30 AM - 01:30 PM", subject: "Competitive Coding & DSA Practice", faculty: "Prof. Vaibhavi Patel", room: "Lab-04" },
            { time: "02:15 PM - 03:15 PM", subject: "Digital Logic & Computer Org", faculty: "Dr. Dweepna Garg", room: "LT-104" },
            { time: "03:15 PM - 04:15 PM", subject: "CSI / ACM Student Chapter Club", faculty: "Faculty Coordinator", room: "SAC Hall" }
        ],
        saturday: [
            { time: "09:15 AM - 11:15 AM", subject: "Industry Expert Tech Webinar", faculty: "Guest Speaker", room: "Auditorium" },
            { time: "11:30 AM - 01:30 PM", subject: "Project Ideation & Open Lab", faculty: "Project Panel", room: "Lab-03" }
        ]
    },

    attendanceData: {
        overall: 89.0,
        totalConducted: 210,
        totalAttended: 187,
        safeThreshold: 75.0,
        safeMarginClasses: 28,
        sourceMeta: { source: "CHARUSAT", sourceType: "official_import", verified: true, updated: "2026-10-02" },
        monthly: [
            { month: "July 2026", classes: 52, attended: 48, rate: "92.3%" },
            { month: "August 2026", classes: 68, attended: 61, rate: "89.7%" },
            { month: "September 2026", classes: 64, attended: 57, rate: "89.0%" },
            { month: "October 2026 (Ongoing)", classes: 26, attended: 21, rate: "80.8%" }
        ],
        subjects: [
            { code: "CS201", name: "Data Structures & Algorithms", faculty: "Prof. Vaibhavi Patel", total: 40, present: 37, absent: 3, percentage: 92.5, status: "Safe" },
            { code: "CS202", name: "Database Management Systems", faculty: "Prof. Hiren Mewada", total: 38, present: 34, absent: 4, percentage: 89.5, status: "Safe" },
            { code: "CS203", name: "OOP with Java", faculty: "Prof. Parth Goel", total: 36, present: 33, absent: 3, percentage: 91.7, status: "Safe" },
            { code: "CS204", name: "Digital Logic & Computer Org", faculty: "Dr. Dweepna Garg", total: 32, present: 28, absent: 4, percentage: 87.5, status: "Safe" },
            { code: "MA201", name: "Discrete Mathematics", faculty: "Dr. Bharat Patel", total: 36, present: 31, absent: 5, percentage: 86.1, status: "Safe" },
            { code: "CS205", name: "Design Engineering & Ethics", faculty: "Prof. Snehal Sathwara", total: 28, present: 24, absent: 4, percentage: 85.7, status: "Safe" }
        ]
    },

    collegeFees: {
        academicYear: "2026 - 2027 (Semester 3)",
        totalAmount: 75000,
        paidAmount: 40000,
        pendingAmount: 35000,
        dueDate: "15 October 2026",
        status: "Partial Paid",
        sourceMeta: { source: "CHARUSAT", sourceType: "official_fee_portal", verified: true, updated: "2026-10-02" },
        breakdown: [
            { head: "Semester Tuition Fee (CSPIT - CE)", total: 65000, paid: 35000, pending: 30000, status: "Due" },
            { head: "CHARUSAT Examination & Assessment Fee", total: 3500, paid: 3500, pending: 0, status: "Paid" },
            { head: "Central Library & Digital Database Access", total: 2500, paid: 1500, pending: 1000, status: "Due" },
            { head: "High Performance Computing Lab & Server Fee", total: 4000, paid: 0, pending: 4000, status: "Due" }
        ],
        history: [
            { txnId: "CHARUSAT-FEE-2026-01", date: "2026-07-20", installment: "Sem 3 Installment 1", amount: 40000, method: "HDFC Payment Gateway (UPI)", status: "Success" },
            { txnId: "CHARUSAT-FEE-2026-02", date: "2026-01-18", installment: "Sem 2 Full Tuition", amount: 72000, method: "NetBanking (SBI)", status: "Success" },
            { txnId: "CHARUSAT-FEE-2025-01", date: "2025-07-15", installment: "Sem 1 Full Tuition", amount: 70000, method: "Debit Card (HDFC)", status: "Success" }
        ]
    },

    hostelData: {
        hostelName: "Tej Smruti Girls Hostel (H5), CHARUSAT",
        roomNo: "505-H5",
        block: "H5 (Fifth Floor - Room 505)",
        roomType: "Double Occupancy (AC)",
        wardenName: "Dr. Gitaben Patel",
        wardenPhone: "+91 2697 265140",
        wardenEmail: "warden.tejsmruti@charusat.ac.in",
        fees: {
            totalAmount: 48000,
            paidAmount: 36000,
            pendingAmount: 12000,
            dueDate: "20 October 2026",
            status: "Partial Paid"
        },
        messMenu: {
            todaySpecial: "Paneer Butter Masala, Dal Makhani, Tandoori Roti & Gulab Jamun",
            monday: { b: "Poha, Tea/Coffee, Sprouts", l: "Sev Tameta, Gujarati Dal, Steamed Rice, Roti, Buttermilk", d: "Paneer Butter Masala, Dal Makhani, Tandoori Roti, Gulab Jamun" },
            tuesday: { b: "Idli Sambar, Chutney, Tea", l: "Chole Masala, Bhature / Puri, Jeera Rice, Curd", d: "Bhindi Masala, Kadhi, Khichdi, Phulka Roti" },
            wednesday: { b: "Methi Thepla, Curd, Pickle, Tea", l: "Rajma Masala, Steamed Rice, Roti, Salad", d: "Veg Biryani, Boondi Raita, Pav Bhaji Special" },
            thursday: { b: "Aloo Paratha, Curd, Tea/Coffee", l: "Kadhai Veg, Moong Dal, Rice, Chapati, Chhas", d: "Dal Baati Churma (Special Gujarati Thali)" },
            friday: { b: "Upma, Coconut Chutney, Tea", l: "Baingan Bharta, Gujarati Kadhi, Khichdi, Rotla", d: "Dum Aloo, Veg Pulav, Roti, Kheer" },
            saturday: { b: "Club Sandwich, Milk / Tea", l: "Mix Veg, Dal Tadka, Jeera Rice, Phulka Roti", d: "Chinese Platter: Hakka Noodles, Manchurian, Fried Rice" },
            sunday: { b: "Puri Bhaji, Tea/Coffee, Sweet", l: "Grand Sunday Feast (Paneer Tikka, Naan, Rice, Sweet)", d: "Light Khichdi, Kadhi, Aloo Sukhi Bhaji, Papad" }
        }
    },

    assignments: [
        { id: 1, code: "CS201", subject: "Data Structures & Algorithms", title: "Assignment 2: Binary Search Trees & AVL Balancing", dueDate: "Tomorrow, 11:59 PM", maxMarks: 30, status: "pending" },
        { id: 2, code: "CS202", subject: "Database Management Systems", title: "Lab Project: Hospital Management ER Model & SQL Schema", dueDate: "18 Oct 2026", maxMarks: 50, status: "pending" },
        { id: 3, code: "CS203", subject: "Object-Oriented Programming (Java)", title: "Assignment 2: Multi-Threading & Collections Framework", dueDate: "22 Oct 2026", maxMarks: 25, status: "pending" },
        { id: 4, code: "CS204", subject: "Digital Logic & Computer Org", title: "Assignment 1: ALU Design & Register Transfer Simulation", dueDate: "28 Sep 2026", maxMarks: 25, status: "submitted", marksObtained: 24 },
        { id: 5, code: "MA201", subject: "Discrete Mathematics", title: "Problem Set 1: Graph Theory & Recurrence Relations", dueDate: "20 Sep 2026", maxMarks: 30, status: "submitted", marksObtained: 29 }
    ],

    exams: [
        { code: "CS201", subject: "Data Structures & Algorithms", date: "20-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)", seat: "25CS027-01" },
        { code: "CS202", subject: "Database Management Systems", date: "22-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)", seat: "25CS027-01" },
        { code: "CS203", subject: "Object-Oriented Programming (Java)", date: "24-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)", seat: "25CS027-01" },
        { code: "CS204", subject: "Digital Logic & Computer Organization", date: "27-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)", seat: "25CS027-01" },
        { code: "MA201", subject: "Discrete Mathematics & Linear Algebra", date: "29-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)", seat: "25CS027-01" }
    ],

    resultsHistory: [
        { semester: "Semester 1", examSession: "Dec 2025", sgpa: 8.50, credits: 22, status: "DISTINCTION" },
        { semester: "Semester 2", examSession: "May 2026", sgpa: 8.56, credits: 22, status: "DISTINCTION" }
    ],

    sem2SubjectGrades: [
        { code: "CS102", name: "Object Oriented Concepts with C++", credits: 4, grade: "AA", gradePoints: 10, marks: "91/100" },
        { code: "MA102", name: "Engineering Mathematics II", credits: 4, grade: "AB", gradePoints: 9, marks: "86/100" },
        { code: "CS103", name: "Basic Electronics & Digital Systems", credits: 4, grade: "AA", gradePoints: 10, marks: "88/100" },
        { code: "PY101", name: "Applied Physics for Engineers", credits: 4, grade: "BB", gradePoints: 8, marks: "82/100" },
        { code: "HS102", name: "Professional Communication & Ethics", credits: 3, grade: "AA", gradePoints: 10, marks: "94/100" },
        { code: "CS104", name: "Python Programming & Data Analysis Lab", credits: 3, grade: "AA", gradePoints: 10, marks: "92/100" }
    ],

    examsData: {
        semesters: [
            { sem: "Semester 1", session: "Dec 2025", sgpa: 8.50, cgpa: 8.50, totalCredits: 22, earnedCredits: 22, result: "DISTINCTION" },
            { sem: "Semester 2", session: "May 2026", sgpa: 8.56, cgpa: 8.53, totalCredits: 22, earnedCredits: 22, result: "DISTINCTION" }
        ],
        upcomingSchedule: [
            { code: "CS201", subject: "Data Structures & Algorithms", date: "20-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)" },
            { code: "CS202", subject: "Database Management Systems", date: "22-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)" },
            { code: "CS203", subject: "Object-Oriented Programming (Java)", date: "24-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)" },
            { code: "CS204", subject: "Digital Logic & Computer Organization", date: "27-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)" },
            { code: "MA201", subject: "Discrete Mathematics & Linear Algebra", date: "29-10-2026", time: "10:00 AM - 01:00 PM", room: "CSPIT Block-A (Hall 104)" }
        ]
    },

    materials: [
        { id: 1, code: "CS201", subject: "Data Structures & Algorithms", unit: "Unit 3", title: "Unit 3: Binary Search Trees, AVL Trees & Graph Algorithms Manual", type: "PDF", size: "7.8 MB", date: "28 Sep 2026", author: "Prof. Vaibhavi Patel", url: "#" },
        { id: 2, code: "CS202", subject: "Database Management Systems", unit: "Unit 2", title: "Unit 2: SQL Advanced Joins, Relational Algebra & Query Optimization", type: "PDF", size: "10.4 MB", date: "24 Sep 2026", author: "Prof. Hiren Mewada", url: "#" },
        { id: 3, code: "CS203", subject: "Object-Oriented Programming (Java)", unit: "Unit 2", title: "Java 17 Collections, Streams API & Multi-threading Handbook", type: "PDF", size: "8.1 MB", date: "20 Sep 2026", author: "Prof. Parth Goel", url: "#" },
        { id: 4, code: "CS204", subject: "Digital Logic & Computer Org", unit: "Unit 1", title: "Instruction Set Architecture, ALU & Pipeline Design Lecture Slides", type: "PPTX", size: "13.2 MB", date: "15 Sep 2026", author: "Dr. Dweepna Garg", url: "#" },
        { id: 5, code: "MA201", subject: "Discrete Mathematics", unit: "Unit 4", title: "Graph Theory, Propositional Logic & Algebraic Structures Notes", type: "PDF", size: "6.5 MB", date: "10 Sep 2026", author: "Dr. Bharat Patel", url: "#" }
    ],

    studyMaterials: [
        { id: 1, code: "CS201", subject: "Data Structures & Algorithms", unit: "Unit 3", title: "Unit 3: Binary Search Trees, AVL Trees & Graph Algorithms Manual", type: "PDF", size: "7.8 MB", date: "28 Sep 2026", author: "Prof. Vaibhavi Patel", url: "#" },
        { id: 2, code: "CS202", subject: "Database Management Systems", unit: "Unit 2", title: "Unit 2: SQL Advanced Joins, Relational Algebra & Query Optimization", type: "PDF", size: "10.4 MB", date: "24 Sep 2026", author: "Prof. Hiren Mewada", url: "#" },
        { id: 3, code: "CS203", subject: "Object-Oriented Programming (Java)", unit: "Unit 2", title: "Java 17 Collections, Streams API & Multi-threading Handbook", type: "PDF", size: "8.1 MB", date: "20 Sep 2026", author: "Prof. Parth Goel", url: "#" },
        { id: 4, code: "CS204", subject: "Digital Logic & Computer Org", unit: "Unit 1", title: "Instruction Set Architecture, ALU & Pipeline Design Lecture Slides", type: "PPTX", size: "13.2 MB", date: "15 Sep 2026", author: "Dr. Dweepna Garg", url: "#" },
        { id: 5, code: "MA201", subject: "Discrete Mathematics", unit: "Unit 4", title: "Graph Theory, Propositional Logic & Algebraic Structures Notes", type: "PDF", size: "6.5 MB", date: "10 Sep 2026", author: "Dr. Bharat Patel", url: "#" }
    ],

    documents: [
        { id: "DOC-01", title: "Semester 2 Official Grade Card (SGPA: 8.56, CGPA: 8.53)", type: "Grade Card", issued: "15 Jun 2026", status: "Verified", certNo: "CHARUSAT/CSPIT/GC/2026/027" },
        { id: "DOC-02", title: "Official Bonafide Certificate (2026-27)", type: "Bonafide", issued: "10 Aug 2026", status: "Active", certNo: "CHARUSAT/REG/BN/2026/092" },
        { id: "DOC-03", title: "Digital Student ID Card (RFID Verified - 25CS027)", type: "Identity", issued: "01 Jul 2025", status: "Active", certNo: "CHARUSAT/ID/25CS027" },
        { id: "DOC-04", title: "Official Transcript of Records (Sem 1 & 2)", type: "Transcript", issued: "05 Jul 2026", status: "Verified", certNo: "CHARUSAT/TR/2026/041" }
    ],

    applications: [
        { id: "APP-9821", type: "Academic Leave Application", subject: "Leave for Technical Hackathon Representation (3 Days)", submittedDate: "28 Sep 2026", status: "Approved", remarks: "Approved by Faculty Mentor (Prof. Vaibhavi Patel)" },
        { id: "APP-9410", type: "Certificate Request", subject: "Issuance of Official Transcript for Higher Studies", submittedDate: "15 Jul 2026", status: "Completed", remarks: "Dispatched & Digital Copy available in Documents" },
        { id: "APP-8902", type: "Hostel Room Change Request", subject: "AC Room Reallocation to H5 Tej Smruti Room 505", submittedDate: "10 Jul 2026", status: "Approved", remarks: "Approved & Allotted by Warden (Dr. Gitaben Patel)" }
    ],

    paymentsLedger: [
        { txnId: "CHARUSAT-FEE-2026-01", date: "2026-07-20", category: "College Fees", desc: "Sem 3 Tuition Installment 1", amount: 40000, method: "HDFC Payment Gateway (UPI)", status: "Success" },
        { txnId: "CHARUSAT-HST-2026-01", date: "2026-07-22", category: "Hostel & Mess", desc: "Tej Smruti Hostel Sem 3 Installment 1", amount: 36000, method: "UPI (Google Pay)", status: "Success" },
        { txnId: "CHARUSAT-EXM-2026-01", date: "2026-06-15", category: "Exam Fee", desc: "Mid-Sem & End-Sem Examination Assessment", amount: 3500, method: "NetBanking", status: "Success" },
        { txnId: "CHARUSAT-LIB-2026-01", date: "2025-08-10", category: "Library Fine / Card", desc: "Digital Library Access & RFID Smart Card", amount: 500, method: "UPI (PhonePe)", status: "Success" }
    ],

    campusEvents: [
        { id: "EVT-01", title: "SPOURAL 2026 - Annual Tech & Cultural Fest", category: "University Mega Fest", date: "25 - 27 Oct 2026", venue: "CHARUSAT Central Grounds & CSPIT Auditorium", desc: "Flagship annual university festival featuring 40+ hackathons, robotics, coding leagues, and music nights.", registered: true },
        { id: "EVT-02", title: "CHARUSAT National 36h Hackathon", category: "Technical Hackathon", date: "10 - 12 Nov 2026", venue: "CSPIT Advanced Computing Labs", desc: "Solve real-world industry AI and Web3 problems. Total cash prize pool ₹2,50,000.", registered: false },
        { id: "EVT-03", title: "ACM / CSI Student Chapter Workshop", category: "Workshop", date: "28 Nov 2026", venue: "CSPIT Seminar Hall (Block A)", desc: "Hands-on masterclass on Cloud Native Microservices and Docker/Kubernetes architectures.", registered: false }
    ],

    facultyDirectory: [
        { name: "Prof. Vaibhavi Patel", designation: "Assistant Professor & Faculty Mentor", department: "Computer Engineering (CSPIT)", subjects: "Data Structures & Algorithms, Competitive Coding", room: "CSPIT 2nd Floor (Room 214)", email: "vaibhavipatel.ce@charusat.ac.in", phone: "+91 2697 265214" },
        { name: "Prof. Hiren Mewada", designation: "Assistant Professor", department: "Computer Engineering (CSPIT)", subjects: "Database Management Systems", room: "CSPIT 2nd Floor (Room 216)", email: "hirenmewada.ce@charusat.ac.in", phone: "+91 2697 265216" },
        { name: "Prof. Parth Goel", designation: "Assistant Professor", department: "Computer Engineering (CSPIT)", subjects: "Object-Oriented Programming (Java)", room: "CSPIT 1st Floor (Room 108)", email: "parthgoel.ce@charusat.ac.in", phone: "+91 2697 265108" },
        { name: "Dr. Dweepna Garg", designation: "Associate Professor", department: "Computer Engineering (CSPIT)", subjects: "Digital Logic & Computer Organization", room: "CSPIT 3rd Floor (Room 304)", email: "dweepnagarg.ce@charusat.ac.in", phone: "+91 2697 265304" },
        { name: "Dr. Bharat Patel", designation: "Professor", department: "Department of Mathematics", subjects: "Discrete Mathematics & Linear Algebra", room: "CSPIT Applied Science Block (Room 102)", email: "bharatpatel.maths@charusat.ac.in", phone: "+91 2697 265102" },
        { name: "Prof. Snehal Sathwara", designation: "Assistant Professor", department: "Computer Engineering (CSPIT)", subjects: "Design Engineering & Professional Ethics", room: "CSPIT Design Studio (Room 205)", email: "snehalsathwara.ce@charusat.ac.in", phone: "+91 2697 265205" }
    ],

    recentActivity: [
        { title: "Logged into Student Hub Portal", time: "Just now", icon: "🔑" },
        { title: "Viewed Attendance Telemetry (89.0% Safe)", time: "15 minutes ago", icon: "📋" },
        { title: "Submitted CS204 Digital Logic ALU Design", time: "Yesterday, 04:30 PM", icon: "📝" },
        { title: "Paid ₹40,000 Tuition Fee via CHARUSAT Gateway", time: "20 Jul 2026", icon: "💳" }
    ],

    importJobs: [
        {
            id: "IMP-2026-10-02-001",
            date: "2026-10-02",
            type: "OFFICIAL_RECORD_VERIFICATION",
            filename: "charusat_profile_25CS027.json",
            recordsCount: 18,
            status: "VERIFIED"
        }
    ]
};

// Retrieve data from localStorage or initialize with CHARUSAT defaults
function getPortalData() {
    let data = null;
    const stored = localStorage.getItem("charusatPortalData");
    if (stored) {
        try {
            const parsed = JSON.parse(stored);
            if (parsed && parsed.student && parsed.version === PORTAL_DATA_VERSION) {
                data = parsed;
            }
        } catch (e) {
            console.error("Error reading portal data, resetting to CHARUSAT defaults", e);
        }
    }
    
    if (!data) {
        data = JSON.parse(JSON.stringify(DEFAULT_PORTAL_DATA));
    }

    // Check for actively authenticated student user in session
    const authUserStr = localStorage.getItem("studentHubUser");
    if (authUserStr) {
        try {
            const authUser = JSON.parse(authUserStr);
            if (authUser && (authUser.name || authUser.id)) {
                const fullName = authUser.name || data.student.name;
                const studentId = authUser.id || data.student.id;

                // Derive initials
                const parts = fullName.trim().split(/\s+/);
                let initials = "";
                for (const p of parts) {
                    if (p.length > 0) initials += p[0].toUpperCase();
                }
                if (initials.length > 2) {
                    initials = initials[0] + initials[initials.length - 1];
                }
                if (!initials) initials = "ST";

                // Short name
                let shortName = fullName;
                if (parts.length > 2) {
                    shortName = parts[0] + " " + parts[parts.length - 1];
                }

                data.student.id = studentId;
                data.student.enrollmentNo = studentId;
                data.student.name = fullName;
                data.student.shortName = shortName;
                data.student.initials = initials;
                if (authUser.email) data.student.email = authUser.email;
                if (authUser.course) {
                    data.student.degree = authUser.course;
                    data.student.branch = authUser.course;
                }
            }
        } catch (e) {
            console.error("Error applying authenticated user to portal data", e);
        }
    }

    return data;
}

function savePortalData(data) {
    data.version = PORTAL_DATA_VERSION;
    localStorage.setItem("charusatPortalData", JSON.stringify(data));
}

/* ==========================================================================
   ACADEMIC INGESTION & CONFLICT RESOLVER ENGINE
   ========================================================================== */

/**
 * Parses raw text or file content (JSON, CSV, or structured text)
 */
function parseAcademicImport(content, format = "auto") {
    content = content.trim();
    
    // 1. Try JSON Parsing
    if (format === "json" || content.startsWith("{") || content.startsWith("[")) {
        try {
            const parsed = JSON.parse(content);
            return { success: true, format: "json", data: parsed };
        } catch (e) {
            if (format === "json") return { success: false, error: "Invalid JSON format: " + e.message };
        }
    }

    // 2. Try CSV Parsing (Comma or Tab separated)
    if (format === "csv" || content.includes(",") || content.includes("\t")) {
        const lines = content.split(/\r?\n/).filter(l => l.trim().length > 0);
        if (lines.length > 1) {
            const header = lines[0].split(/[,\t]/).map(h => h.trim().toLowerCase());
            const records = [];
            for (let i = 1; i < lines.length; i++) {
                const cols = lines[i].split(/[,\t]/).map(c => c.trim());
                const row = {};
                header.forEach((h, idx) => {
                    row[h] = cols[idx] || "";
                });
                records.push(row);
            }
            return { success: true, format: "csv", data: { records, rawHeader: header } };
        }
    }

    // 3. Fallback: Key-Value Structured Text
    const lines = content.split(/\r?\n/);
    const kv = {};
    lines.forEach(l => {
        const colonIdx = l.indexOf(":");
        if (colonIdx > 0) {
            const key = l.substring(0, colonIdx).trim();
            const val = l.substring(colonIdx + 1).trim();
            kv[key] = val;
        }
    });

    if (Object.keys(kv).length > 0) {
        return { success: true, format: "kv", data: kv };
    }

    return { success: false, error: "Unrecognized academic data format. Please provide JSON, CSV, or Key-Value records." };
}

/**
 * Compares incoming parsed data with current stored portal data and discovers differences/conflicts
 */
function detectAcademicConflicts(incoming, current) {
    const conflicts = [];
    current = current || getPortalData();

    // Check Student Attributes
    if (incoming.student) {
        const s = incoming.student;
        if (s.name && s.name !== current.student.name) {
            conflicts.push({ field: "student.name", label: "Student Full Name", currentVal: current.student.name, incomingVal: s.name, type: "text" });
        }
        if (s.cgpa !== undefined && Number(s.cgpa) !== Number(current.student.cgpa)) {
            conflicts.push({ field: "student.cgpa", label: "Cumulative CGPA", currentVal: current.student.cgpa, incomingVal: Number(s.cgpa), type: "number" });
        }
        if (s.sgpa !== undefined && Number(s.sgpa) !== Number(current.student.sgpa)) {
            conflicts.push({ field: "student.sgpa", label: "Latest SGPA", currentVal: current.student.sgpa, incomingVal: Number(s.sgpa), type: "number" });
        }
        if (s.semester && s.semester !== current.student.semester) {
            conflicts.push({ field: "student.semester", label: "Academic Semester", currentVal: current.student.semester, incomingVal: s.semester, type: "text" });
        }
    }

    // Check Attendance Rate
    if (incoming.attendanceData && incoming.attendanceData.overall !== undefined) {
        const inAtt = Number(incoming.attendanceData.overall);
        if (inAtt !== Number(current.attendanceData.overall)) {
            conflicts.push({ field: "attendanceData.overall", label: "Overall Attendance %", currentVal: `${current.attendanceData.overall}%`, incomingVal: `${inAtt}%`, type: "number" });
        }
    }

    // Check Pending Fees
    if (incoming.collegeFees && incoming.collegeFees.pendingAmount !== undefined) {
        const inPending = Number(incoming.collegeFees.pendingAmount);
        if (inPending !== Number(current.collegeFees.pendingAmount)) {
            conflicts.push({ field: "collegeFees.pendingAmount", label: "Pending College Tuition Dues", currentVal: `₹ ${current.collegeFees.pendingAmount.toLocaleString('en-IN')}`, incomingVal: `₹ ${inPending.toLocaleString('en-IN')}`, type: "currency" });
        }
    }

    return conflicts;
}

/**
 * Atomically merges incoming data with user choices
 */
function applyAcademicImport(incoming, resolutions = {}, filename = "manual_import.json") {
    const data = getPortalData();

    // Apply resolved conflicts
    Object.keys(resolutions).forEach(field => {
        const chosen = resolutions[field]; // "incoming" or "current"
        if (chosen === "incoming") {
            const parts = field.split(".");
            if (parts.length === 2) {
                if (incoming[parts[0]] && incoming[parts[0]][parts[1]] !== undefined) {
                    data[parts[0]][parts[1]] = incoming[parts[0]][parts[1]];
                }
            }
        }
    });

    // Merge Courses if provided
    if (Array.isArray(incoming.courses) && incoming.courses.length > 0) {
        data.courses = incoming.courses;
    }

    // Update Metadata
    data.metadata.lastUpdated = new Date().toISOString().replace("T", " ").substring(0, 16);
    data.metadata.lastSyncDate = new Date().toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
    data.metadata.sourceType = "user_authorized_import";

    // Record Import Job
    data.importJobs = data.importJobs || [];
    data.importJobs.unshift({
        id: `IMP-${Date.now()}`,
        date: new Date().toISOString().split("T")[0],
        type: "USER_AUTHORIZED_IMPORT",
        filename: filename,
        recordsCount: Object.keys(incoming).length,
        status: "CONFIRMED"
    });

    // Add activity log
    data.recentActivity = data.recentActivity || [];
    data.recentActivity.unshift({
        title: `Imported Academic Records (${filename})`,
        time: "Just now",
        icon: "📥"
    });

    savePortalData(data);
    return data;
}

/**
 * 1-Click Export of Student Academic OS Data
 */
function exportAcademicData() {
    const data = getPortalData();
    const jsonStr = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonStr], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `CHARUSAT_CareerOS_${data.student.id}_${new Date().toISOString().split("T")[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
