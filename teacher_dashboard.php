<?php
session_start();
require './db.php'; // Include the database connection file

// Fetch attendance records from the 'attendance' table, joining with 'students' table to get the student name
$sql = "
    SELECT 
        students.id,
        students.name AS student_name,
        students.student_uid,
        students.section_id,
        TIME_FORMAT(attendance.time_in, '%h:%i %p') as time_in,
        TIME_FORMAT(attendance.time_out, '%h:%i %p') as time_out,
        attendance.remarks,
        CASE 
            WHEN attendance.time_in IS NOT NULL AND attendance.time_out IS NULL THEN 'Pending Time-out'
            WHEN attendance.time_in IS NOT NULL AND attendance.time_out IS NOT NULL THEN 'Complete'
            ELSE 'Not Yet Time-in'
        END as status
    FROM students
    LEFT JOIN attendance ON students.student_uid = attendance.student_uid 
        AND DATE(attendance.date) = CURRENT_DATE()
        AND attendance.class_id = :class_id
    WHERE students.section_id = (
        SELECT section_id FROM classes WHERE id = :class_id
    )
    ORDER BY students.name ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['class_id' => $_GET['class_id'] ?? 0]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $attendanceRecords = [];
}

// Fetch sections from the 'sections' table to populate the 'Add Class' modal form
$sectionSql = "SELECT id, section_name FROM sections";
$sectionStmt = $pdo->query($sectionSql);
$sections = $sectionStmt->fetchAll();

// Fetch all classes from the 'classes' table to populate the 'Select Class' dropdown
$classSql = "
    SELECT classes.id, sections.section_name 
    FROM classes
    JOIN sections ON classes.section_id = sections.id
";
$classStmt = $pdo->query($classSql);
$classes = $classStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="./css/tdashh.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-content select[multiple] {
            height: auto;
            min-height: 120px;
            padding: 8px;
        }

        .modal-content small {
            display: block;
            color: #666;
            margin-top: 4px;
            margin-bottom: 16px;
        }

        .modal-content label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-content button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #45a049;
        }

        .schedule-checkboxes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }

        /* Custom checkbox styling */
        .checkbox-group input[type="checkbox"] {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #4CAF50;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }

        .checkbox-group input[type="checkbox"]:checked {
            background-color: #4CAF50;
        }

        .checkbox-group input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .checkbox-group input[type="checkbox"]:hover {
            background-color: #f0f0f0;
        }

        .checkbox-group input[type="checkbox"]:checked:hover {
            background-color: #45a049;
        }

        .class-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-btn {
            background: #4CAF50;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .nav-btn:hover {
            background: #45a049;
        }

        .nav-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .current-class {
            min-width: 200px;
            text-align: center;
        }

        .current-class h2 {
            margin: 0;
            color: #333;
            font-size: 1.2em;
        }

        /* Animation for class change */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .slide-animation {
            animation: slideIn 0.3s ease-out;
        }

        .class-tiles-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            margin-top: 20px;
        }

        .class-tile {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .class-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .class-tile.active {
            border-color: #4CAF50;
            background-color: #f8fff8;
        }

        .tile-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
        }

        .tile-content h3 {
            margin: 0;
            color: #333;
            font-size: 1.1em;
        }

        .tile-content .material-icons {
            font-size: 2em;
            color: #4CAF50;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .class-tiles-container {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                padding: 15px;
            }
        }

        .logout-form {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: calc(250px - 40px); /* Adjust based on your sidebar width */
        }

        .logout-form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .logout-form input[type="submit"]:hover {
            background-color: #cc0000;
        }

        .scanning-controls {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .scan-button {
            background: #4CAF50;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .scan-button:hover {
            background-color: #45a049;
        }

        .scan-button.scanning {
            background-color: #f44336;
        }

        .scanning-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            color: #333;
        }

        .scanning-indicator.active {
            display: flex;
        }

        .scanning-indicator .pulse {
            width: 10px;
            height: 10px;
            background-color: #4CAF50;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
            }
            
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
            }
            
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

        .notification.success {
            background-color: #4CAF50;
        }

        .notification.error {
            background-color: #f44336;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="name">
            <img src="./assets/images/logo.png" alt="Logo" class="logo">
            <text> UNIVERSITY OF NEGROS OCCIDENTAL – RECOLETOS, INCORPORATED</text>
            <hr>
        </div>
        <ul>
            <li>
                <a href="teacher_dashboard.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/teacher_dashboard.php') ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span> Dashboard
                </a>
            </li>
            <li>
                <a href="record.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/teacher/record.php') ? 'active' : ''; ?>">
                    <span class="material-icons">library_books</span> Records
                </a>
            </li>
            <li>
                <a href="account.php" class="<?php echo ($_SERVER['SCRIPT_NAME'] == '/attendance_system/teacher/account.php') ? 'active' : ''; ?>">
                    <span class="material-icons">account_circle</span> Account
                </a>
            </li>
        </ul>
        <form action="logout.php" method="post" class="logout-form">
            <input type="submit" value="Logout">
        </form>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="welcome">
                <h1>WELCOME MR. CRUZ</h1>
                <button class="select-section" onclick="openModal()">ADD CLASS</button>
                
                <!-- Class Selection Dropdown -->
                <div class="class-tiles-container">
                    <?php foreach ($classes as $class): ?>
                    <div class="class-tile" data-class-id="<?php echo $class['id']; ?>">
                        <div class="tile-content">
                            <h3><?php echo htmlspecialchars($class['section_name']); ?></h3>
                            <span class="material-icons">school</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- End of Class Selection Dropdown -->
            </div>

            <!-- Class Info Section -->
            <div class="class-info">
                <p>Section: <span id="class-section">N/A</span></p>
                <p>Period Covered: <span id="class-period-covered">N/A</span></p>
                <p>Class Schedule: <span id="class-schedule">N/A</span> | Time: <span id="class-time">N/A</span> | Room No.: <span id="class-room-number">N/A</span></p>
            </div>

            <!-- Add this after the class-info div -->
            <div class="scanning-controls">
                <button id="startScanBtn" class="scan-button">
                    <span class="material-icons">nfc</span>
                    Start Scanning
                </button>
                <div class="scanning-indicator">
                    <div class="pulse"></div>
                    <span>Scanning...</span>
                </div>
            </div>
        </div>
        
        <!-- STUDENT attendance -->
        <div class="table-container">
            <div class="table-header">
                <input type="text" placeholder="Search..." class="search-bar">
            </div>
            <table>
                <tr>
                    <th>Student’s Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
                <?php foreach ($attendanceRecords as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['time_in']); ?></td>
                    <td><?php echo htmlspecialchars($record['time_out']); ?></td>
                    <td><?php echo htmlspecialchars($record['status']); ?></td>
                    <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <button class="print-button">PRINT</button>
        </div>

        <!-- Modal for Adding Class -->
        <div id="addClassModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Add Class</h2>
                <form action="./teacher/add_class.php" method="post">
                    <label for="section">Section:</label>
                    <select name="section_id" id="section" required>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['section_name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="period_start">Period Start:</label>
                    <input type="date" name="period_start" required>

                    <label for="period_end">Period End:</label>
                    <input type="date" name="period_end" required>

                    <label for="time_start">Time Start:</label>
                    <input type="time" name="time_start" required>

                    <label for="time_end">Time End:</label>
                    <input type="time" name="time_end" required>

                    <label>Class Schedule:</label>
                    <div class="schedule-checkboxes">
                        <div class="checkbox-group">
                            <input type="checkbox" id="monday" name="schedule[]" value="M">
                            <label for="monday">Monday</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="tuesday" name="schedule[]" value="T">
                            <label for="tuesday">Tuesday</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="wednesday" name="schedule[]" value="W">
                            <label for="wednesday">Wednesday</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="thursday" name="schedule[]" value="TH">
                            <label for="thursday">Thursday</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="friday" name="schedule[]" value="F">
                            <label for="friday">Friday</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="saturday" name="schedule[]" value="S">
                            <label for="saturday">Saturday</label>
                        </div>
                    </div>

                    <label for="room_number">Room No.:</label>
                    <input type="text" name="room_number" required>

                    <button type="submit">Add Class</button>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const classTiles = document.querySelectorAll('.class-tile');
                
                classTiles.forEach(tile => {
                    tile.addEventListener('click', function() {
                        // Remove active class from all tiles
                        classTiles.forEach(t => t.classList.remove('active'));
                        
                        // Add active class to clicked tile
                        this.classList.add('active');
                        
                        // Fetch class info
                        const classId = this.dataset.classId;
                        
                        // Update URL without reloading
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.set('class_id', classId);
                        window.history.pushState({}, '', newUrl);
                        
                        fetchClassInfo(classId);
                    });
                });

                // Handle initial load if class_id is in URL
                const urlParams = new URLSearchParams(window.location.search);
                const classId = urlParams.get('class_id');
                if (classId) {
                    const activeTile = document.querySelector(`.class-tile[data-class-id="${classId}"]`);
                    if (activeTile) {
                        activeTile.classList.add('active');
                        fetchClassInfo(classId);
                    }
                }
            });

            function fetchClassInfo(classId) {
                fetch(`./teacher/fetch_class_info.php?class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        // Update class info display
                        document.getElementById("class-section").innerText = data.section_name || 'N/A';
                        document.getElementById("class-period-covered").innerText = 
                            `${data.period_start || 'N/A'} - ${data.period_end || 'N/A'}`;
                        document.getElementById("class-schedule").innerText = data.schedule || 'N/A';
                        document.getElementById("class-time").innerText = 
                            `${data.time_start || 'N/A'} - ${data.time_end || 'N/A'}`;
                        document.getElementById("class-room-number").innerText = data.room_number || 'N/A';

                        // Update student table
                        updateStudentTable(data.students);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error fetching class information: ' + error.message, 'error');
                    });
            }

            function updateStudentTable(students) {
                const tableBody = document.querySelector('table tbody') || document.querySelector('table');
                
                let tableContent = `
                    <tr>
                        <th>Student's Name</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                `;
                
                if (students && students.length > 0) {
                    students.forEach(student => {
                        tableContent += `
                            <tr>
                                <td>${student.student_name || ''}</td>
                                <td>${student.time_in || '-'}</td>
                                <td>${student.time_out || '-'}</td>
                                <td>${student.status || '-'}</td>
                                <td>${student.remarks || '-'}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableContent += `
                        <tr>
                            <td colspan="5" class="text-center">No students found</td>
                        </tr>
                    `;
                }
                
                tableBody.innerHTML = tableContent;
            }

            function getStatus(timeIn, timeOut) {
                if (timeIn && timeOut) return 'Complete';
                if (timeIn) return 'Pending Time-out';
                return 'Not Yet Time-in';
            }

            // Modal functionality
            function openModal() {
                document.getElementById("addClassModal").style.display = "block";
            }

            function closeModal() {
                document.getElementById("addClassModal").style.display = "none";
            }

            window.onclick = function(event) {
                const modal = document.getElementById("addClassModal");
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }

            // Replace the existing form validation script with this:
            document.querySelector('form[action="./teacher/add_class.php"]').addEventListener('submit', function(e) {
                const scheduleCheckboxes = document.querySelectorAll('input[name="schedule[]"]');
                let checked = false;
                
                scheduleCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        checked = true;
                    }
                });
                
                if (!checked) {
                    e.preventDefault();
                    alert('Please select at least one class schedule day.');
                }
            });

            // Update the scanning functions
            let isScanning = false;
            let scanCheckInterval = null;
            let lastScannedTime = 0;
            const SCAN_COOLDOWN = 3000; // 3 seconds cooldown to match ESP8266

            async function startScanning() {
                try {
                    const nodeMCUIP = 'http://192.168.254.133';
                    
                    // Start continuous scanning
                    scanCheckInterval = setInterval(async () => {
                        if (!isScanning) {
                            clearInterval(scanCheckInterval);
                            return;
                        }
                        
                        try {
                            // Only check for new cards if enough time has passed since last scan
                            const currentTime = Date.now();
                            if (currentTime - lastScannedTime < SCAN_COOLDOWN) {
                                return; // Skip this check if we're still in cooldown
                            }

                            // Check for card
                            const checkResponse = await fetch(`${nodeMCUIP}/check_card`);
                            const data = await checkResponse.json();

                            if (data.success && data.rfid_tag && data.rfid_tag !== 'none') {
                                // Update last scan time
                                lastScannedTime = currentTime;
                                
                                // Process the card
                                await processCardScan(data.rfid_tag);
                                
                                // Wait for the display duration before allowing next scan
                                await new Promise(resolve => setTimeout(resolve, SCAN_COOLDOWN));
                            }
                        } catch (error) {
                            console.error('Error checking for card:', error);
                        }
                    }, 200); // Check frequently but respect the cooldown

                } catch (error) {
                    console.error('Error starting scan:', error);
                    showNotification('Error starting scanner', 'error');
                    stopScanning();
                }
            }

            // Update processCardScan to handle the timing
            async function processCardScan(rfidTag) {
                const classId = document.querySelector('.class-tile.active')?.dataset.classId;
                if (!classId) {
                    showNotification('Please select a class first', 'error');
                    return;
                }

                try {
                    const response = await fetch('./teacher/scan_tag.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_uid: rfidTag,
                            class_id: classId
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        playSound('success');
                        fetchClassInfo(classId);
                        showNotification(`${data.message} for ${data.student_name}`, 'success');
                    } else {
                        playSound('error');
                        showNotification(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Error processing attendance', 'error');
                }
            }

            // Update the scan button event listener
            document.getElementById('startScanBtn').addEventListener('click', async function() {
                const scanButton = this;
                const scanningIndicator = document.querySelector('.scanning-indicator');
                const nodeMCUIP = 'http://192.168.254.133';
                
                if (!isScanning) {
                    // Check if a class is selected
                    const activeClass = document.querySelector('.class-tile.active');
                    if (!activeClass) {
                        alert('Please select a class first');
                        return;
                    }

                    try {
                        // Start scanning
                        isScanning = true;
                        scanButton.classList.add('scanning');
                        scanButton.innerHTML = '<span class="material-icons">stop_circle</span>Stop Scanning';
                        scanningIndicator.classList.add('active');
                        
                        // Send initial "Tap to Scan" message to LCD
                        const response = await fetch(`${nodeMCUIP}/start_scan`);
                        const data = await response.json();
                        
                        if (data.status === "scanning") {
                            showNotification('Scanner activated - Ready to scan', 'success');
                            // Start the continuous scanning process
                            await startScanning();
                        } else {
                            throw new Error('Failed to initialize scanner');
                        }
                    } catch (error) {
                        console.error('Error starting scanner:', error);
                        showNotification('Error communicating with scanner', 'error');
                        stopScanning();
                    }
                } else {
                    try {
                        await fetch(`${nodeMCUIP}/stop_scan`);
                        stopScanning();
                        showNotification('Scanner stopped', 'success');
                    } catch (error) {
                        console.error('Error stopping scanner:', error);
                        showNotification('Error stopping scanner', 'error');
                    }
                }
            });

            function stopScanning() {
                const scanButton = document.getElementById('startScanBtn');
                const scanningIndicator = document.querySelector('.scanning-indicator');
                
                isScanning = false;
                scanButton.classList.remove('scanning');
                scanButton.innerHTML = '<span class="material-icons">nfc</span>Start Scanning';
                scanningIndicator.classList.remove('active');
                
                // Clear the check interval
                if (scanCheckInterval) {
                    clearInterval(scanCheckInterval);
                    scanCheckInterval = null;
                }
            }

            // Add these utility functions for notifications and sounds
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            function playSound(type) {
                const audio = new Audio();
                audio.src = type === 'success' ? './assets/sounds/success.mp3' : './assets/sounds/error.mp3';
                audio.play().catch(e => console.log('Sound play failed:', e));
            }

            // Add this function after the existing script functions
            function exportToExcel() {
                const classId = document.querySelector('.class-tile.active')?.dataset.classId;
                if (!classId) {
                    showNotification('Please select a class first', 'error');
                    return;
                }

                // Fetch the data and generate Excel
                fetch(`./teacher/export_attendance.php?class_id=${classId}`)
                    .then(response => response.blob())
                    .then(blob => {
                        // Create a download link
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `attendance_report_${new Date().toISOString().split('T')[0]}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        a.remove();
                    })
                    .catch(error => {
                        console.error('Error exporting to Excel:', error);
                        showNotification('Error exporting to Excel', 'error');
                    });
            }

            // Update the print function
            function printAttendance() {
                const classId = document.querySelector('.class-tile.active')?.dataset.classId;
                if (!classId) {
                    showNotification('Please select a class first', 'error');
                    return;
                }

                // Open the print page in a new window
                window.open(`./teacher/print_attendance.php?class_id=${classId}`, '_blank');
            }

            // Update the print button event listener
            document.querySelector('.print-button').addEventListener('click', printAttendance);
        </script>
    </div>
</body>
</html>
