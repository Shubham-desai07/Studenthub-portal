<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 13: PHP JSON API & AJAX/Fetch CRUD - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <style>
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header>
        <h1><a href="index.html">StudentHub</a></h1>
        <button id="hamburger-btn" class="hamburger-btn" aria-label="Menu">☰</button>
        <nav>
            <ul id="nav-list">
                <li><a href="index.html">Home</a></li>
                <li><a href="practical8.php">Practical 8</a></li>
                <li><a href="practical9.php">Practical 9</a></li>
                <li><a href="practical10.php">Practical 10</a></li>
                <li><a href="practical11.php">Practical 11</a></li>
                <li><a href="practical12.php">Practical 12</a></li>
                <li><a href="practical13.php" class="active">Practical 13</a></li>
                <li><a href="practical14.php">Practical 14</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <main id="main-content" class="container">

        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Practical 13: PHP JSON API &amp; AJAX Fetch CRUD</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Create simple PHP JSON API endpoints for events and consume them using Fetch API. Perform list, add, and delete operations asynchronously without full page reload.
                    </p>
                </div>
            </div>

            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.88rem;">
                <div><strong>👨‍🎓 Student:</strong> Shubham Desai (25DCE020)</div>
                <div><strong>🏛️ Branch:</strong> 3rd Semester &bull; Computer Engineering</div>
                <div><strong>📖 Course:</strong> Web Development Frameworks (ITUE203)</div>
                <div><strong>🎯 CO Mapping:</strong> CO3, CO4, CO5</div>
            </div>
        </section>

        <!-- Live Status / Toast Alert Box -->
        <div id="apiAlert" class="alert" style="display: none; margin-bottom: 20px;"></div>

        <!-- SECTION 1: Asynchronous Create Event Form -->
        <section class="card" style="margin-bottom: 20px;">
            <h3 style="color: var(--accent-color); margin-bottom: 12px;">⚡ 1. Asynchronous Event Insert (No Page Reload)</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
                When you click "Create Event Asynchronously", a background <code>fetch('api.php?action=add_event', { method: 'POST', body: ... })</code> request sends JSON data to PHP, and updates the table immediately without a browser refresh.
            </p>

            <form id="addEventForm" onsubmit="handleAjaxSubmit(event)" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ajax_title">Event Title:</label>
                    <input type="text" id="ajax_title" placeholder="e.g. Docker &amp; Kubernetes Workshop" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ajax_category">Category:</label>
                    <select id="ajax_category">
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Hackathon">Hackathon</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ajax_date">Event Date:</label>
                    <input type="date" id="ajax_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ajax_venue">Venue:</label>
                    <input type="text" id="ajax_venue" placeholder="e.g. Lab 202" value="Lab 202" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ajax_fee">Fee (INR):</label>
                    <input type="number" id="ajax_fee" value="0.00" step="0.01">
                </div>

                <div>
                    <button type="submit" id="submitBtn" class="btn" style="width: 100%;">
                        <span id="btnText">⚡ Add via Fetch API</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- SECTION 2: Dynamic Live Table (Updated via AJAX Fetch) -->
        <section class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3>📡 2. Real-Time Asynchronous Data Grid</h3>
                    <small id="eventCountLabel" style="color: var(--text-muted);">Fetching events from api.php...</small>
                </div>
                <button type="button" class="btn btn-secondary" onclick="loadEvents()" style="font-size: 0.85rem;">
                    🔄 Refresh via Fetch API
                </button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th style="text-align: center;">Action (AJAX Delete)</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTableBody">
                        <tr><td colspan="8" style="text-align: center; padding: 25px;">Loading events asynchronously...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Live API JSON Response Inspector -->
            <div style="margin-top: 20px;">
                <details>
                    <summary style="font-weight: bold; cursor: pointer; color: var(--accent-color);">
                        🔍 Inspect Latest JSON Response from <code>api.php</code>
                    </summary>
                    <pre id="jsonPreview" style="margin-top: 10px; background: #2c3e50; color: #2ecc71; padding: 12px; border-radius: 6px; font-size: 0.8rem; overflow-x: auto;">// JSON response will appear here after fetch()...</pre>
                </details>
            </div>
        </section>

        <!-- SECTION 3: Key Questions & Viva Voce Q&A -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🎓 3. Key Questions &amp; Viva Voce Q&amp;A (From Syllabus)</h3>
            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q1: Do PHP endpoints return valid JSON responses?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> The PHP script sets <code>header('Content-Type: application/json; charset=utf-8')</code> and outputs associative data using <code>json_encode(['success' => true, 'data' => $rows])</code>.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q2: Are GET and POST requests handled properly?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> The endpoint checks <code>$_SERVER['REQUEST_METHOD']</code>: GET returns the list of events, and POST reads payload parameters to execute INSERT or DELETE prepared statements.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q3: Are loading, success, and error states shown on the frontend?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> A loading indicator appears while awaiting the network promise, green dismissible banners report successful additions/deletions, and network or database errors display friendly red alerts.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q4: Is input validation performed before database operations?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Inputs are checked for non-empty values both in the JavaScript form before making the request and inside the PHP script before executing SQL.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 13 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="practical12.php">Practical 12</a> | <a href="practical13.php">Practical 13</a> | <a href="practical14.php">Practical 14</a></p>
    </footer>

    <!-- JavaScript AJAX / Fetch API Logic -->
    <script>
        // Load events on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadEvents();
        });

        function showAlert(msg, isSuccess) {
            var box = document.getElementById('apiAlert');
            box.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
            box.innerHTML = (isSuccess ? '<strong>Success:</strong> ' : '<strong>Notice:</strong> ') + msg;
            box.style.display = 'block';
            window.scrollTo({ top: box.offsetTop - 80, behavior: 'smooth' });
        }

        // 1. Asynchronously Fetch and Render Events
        function loadEvents() {
            var tbody = document.getElementById('eventsTableBody');
            var jsonBox = document.getElementById('jsonPreview');

            fetch('api.php?action=list_events')
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    jsonBox.textContent = JSON.stringify(json, null, 2);

                    if (!json.success || !json.data) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:red;">' + (json.message || 'Error loading data') + '</td></tr>';
                        return;
                    }

                    document.getElementById('eventCountLabel').textContent = 'Total Events in Database: ' + json.data.length;

                    if (json.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No events found in database.</td></tr>';
                        return;
                    }

                    var html = '';
                    json.data.forEach(function(ev) {
                        html += '<tr>' +
                            '<td>#' + ev.event_id + '</td>' +
                            '<td><strong>' + escapeHtml(ev.title) + '</strong></td>' +
                            '<td><span class="badge badge-blue">' + escapeHtml(ev.category) + '</span></td>' +
                            '<td>' + escapeHtml(ev.event_date) + '</td>' +
                            '<td>' + escapeHtml(ev.venue) + '</td>' +
                            '<td>' + (parseFloat(ev.fee) > 0 ? '₹' + parseFloat(ev.fee).toFixed(2) : 'Free') + '</td>' +
                            '<td><span class="badge badge-green">' + escapeHtml(ev.status || 'Upcoming') + '</span></td>' +
                            '<td style="text-align:center;">' +
                                '<button type="button" class="btn" style="background-color:#e74c3c; font-size:0.75rem; padding:4px 8px;" onclick="deleteEvent(' + ev.event_id + ', \'' + escapeHtml(ev.title) + '\')">' +
                                    '🗑️ Delete' +
                                '</button>' +
                            '</td>' +
                        '</tr>';
                    });
                    tbody.innerHTML = html;
                })
                .catch(function(err) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:red;">Failed to connect to API endpoint: ' + err.message + '</td></tr>';
                });
        }

        // 2. Asynchronously Add Event via POST
        function handleAjaxSubmit(e) {
            e.preventDefault();
            var btn = document.getElementById('submitBtn');
            var btnText = document.getElementById('btnText');
            btnText.textContent = 'Saving...';
            btn.disabled = true;

            var payload = {
                title: document.getElementById('ajax_title').value,
                category: document.getElementById('ajax_category').value,
                event_date: document.getElementById('ajax_date').value,
                venue: document.getElementById('ajax_venue').value,
                fee: document.getElementById('ajax_fee').value
            };

            fetch('api.php?action=add_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.disabled = false;
                btnText.textContent = '⚡ Add via Fetch API';

                if (data.success) {
                    showAlert(data.message, true);
                    document.getElementById('ajax_title').value = '';
                    loadEvents(); // Reload table without browser reload
                } else {
                    showAlert(data.message, false);
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btnText.textContent = '⚡ Add via Fetch API';
                showAlert('Network error: ' + err.message, false);
            });
        }

        // 3. Asynchronously Delete Event
        function deleteEvent(id, title) {
            if (!confirm('Are you sure you want to delete event #' + id + ' (' + title + ') asynchronously?')) {
                return;
            }

            fetch('api.php?action=delete_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showAlert(data.message, true);
                    loadEvents(); // Instantly update view
                } else {
                    showAlert(data.message, false);
                }
            })
            .catch(function(err) {
                showAlert('Error deleting event: ' + err.message, false);
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>

</body>
</html>
