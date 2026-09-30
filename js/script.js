/* ===== StudentHub Portal - script.js  ===== */

document.addEventListener("DOMContentLoaded", function () {

    // 1. Theme Switcher (Practical 4: DOM Event Handling)
    var themeBtn = document.getElementById("themeBtn");
    var body = document.body;

    if (themeBtn) {
        themeBtn.addEventListener("click", function () {
            body.classList.toggle("dark");
            var isDark = body.classList.contains("dark");
            themeBtn.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";
        });
    }

    // 2. Hamburger Menu for Mobile (Practical 4)
    var hamburgerBtn = document.getElementById("hamburger-btn");
    var navList = document.getElementById("nav-list");

    if (hamburgerBtn && navList) {
        hamburgerBtn.addEventListener("click", function () {
            navList.classList.toggle("show");
        });
    }

    // 3. Notification Banner (Practical 4)
    var banner = document.getElementById("notification-banner");
    var closeBanner = document.getElementById("closeBanner");

    if (banner && closeBanner) {
        closeBanner.addEventListener("click", function () {
            banner.style.display = "none";
        });
    }

    // 4. Modal Popup System (Practical 4)
    var modalOpenBtns = document.querySelectorAll("[data-modal-target]");
    var modalCloseBtns = document.querySelectorAll(".modal-close");

    modalOpenBtns.forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            var targetId = this.getAttribute("data-modal-target");
            var modal = document.getElementById(targetId);
            if (modal) modal.classList.add("open");
        });
    });

    modalCloseBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
            var modal = this.closest(".modal-overlay");
            if (modal) modal.classList.remove("open");
        });
    });

    // 5. Simple Image / Content Slider (Practical 4)
    var slides = document.querySelectorAll(".slide");
    var prevBtn = document.querySelector(".slider-btn.prev");
    var nextBtn = document.querySelector(".slider-btn.next");
    var currentSlide = 0;

    function showSlide(index) {
        if (slides.length === 0) return;
        slides.forEach(function (s) { s.classList.remove("active"); });
        if (index >= slides.length) currentSlide = 0;
        else if (index < 0) currentSlide = slides.length - 1;
        else currentSlide = index;
        slides[currentSlide].classList.add("active");
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            showSlide(currentSlide + 1);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", function () {
            showSlide(currentSlide - 1);
        });
    }

    if (slides.length > 0) {
        setInterval(function () {
            showSlide(currentSlide + 1);
        }, 4000);
    }

    // 6. Registration Form Validation (Practical 5)
    var regForm = document.getElementById("registerForm");
    var passwordInput = document.getElementById("password");
    var strengthFill = document.getElementById("strengthFill");
    var strengthLabel = document.getElementById("strengthLabel");

    if (passwordInput && strengthFill) {
        passwordInput.addEventListener("input", function () {
            var val = passwordInput.value;
            var score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[@$!%*?&]/.test(val)) score++;

            if (val.length === 0) {
                strengthFill.style.width = "0%";
                if (strengthLabel) strengthLabel.textContent = "";
            } else if (score <= 2) {
                strengthFill.style.width = "33%";
                strengthFill.style.backgroundColor = "#e74c3c";
                if (strengthLabel) strengthLabel.textContent = "Weak Password";
            } else if (score === 3) {
                strengthFill.style.width = "66%";
                strengthFill.style.backgroundColor = "#f39c12";
                if (strengthLabel) strengthLabel.textContent = "Medium Password";
            } else {
                strengthFill.style.width = "100%";
                strengthFill.style.backgroundColor = "#27ae60";
                if (strengthLabel) strengthLabel.textContent = "Strong Password";
            }
        });
    }

    function setError(inputId, errorId, message) {
        var input = document.getElementById(inputId);
        var err = document.getElementById(errorId);
        if (!input || !err) return true;

        if (message) {
            input.classList.add("invalid");
            input.classList.remove("valid");
            err.textContent = message;
            return false;
        } else {
            input.classList.remove("invalid");
            input.classList.add("valid");
            err.textContent = "";
            return true;
        }
    }

    if (regForm) {
        regForm.addEventListener("submit", function (e) {
            e.preventDefault();

            var nameVal = document.getElementById("fullName").value.trim();
            var emailVal = document.getElementById("email").value.trim();
            var mobileVal = document.getElementById("mobile").value.trim();
            var passVal = document.getElementById("password").value;
            var confirmVal = document.getElementById("confirmPassword").value;
            var courseVal = document.getElementById("course").value;
            var termsVal = document.getElementById("terms").checked;

            var namePattern = /^[A-Za-z ]{3,50}$/;
            var emailPattern = /^\S+@\S+\.\S+$/;
            var mobilePattern = /^[6-9]\d{9}$/;
            var passPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

            var v1 = namePattern.test(nameVal) ? setError("fullName", "fullNameError", "") : setError("fullName", "fullNameError", "Enter valid name (min 3 letters).");
            var v2 = emailPattern.test(emailVal) ? setError("email", "emailError", "") : setError("email", "emailError", "Enter valid email address.");
            var v3 = mobilePattern.test(mobileVal) ? setError("mobile", "mobileError", "") : setError("mobile", "mobileError", "Enter 10-digit mobile starting with 6-9.");
            var v4 = passPattern.test(passVal) ? setError("password", "passwordError", "") : setError("password", "passwordError", "Needs 8+ chars, upper, lower, number, special char.");
            var v5 = (confirmVal === passVal && confirmVal !== "") ? setError("confirmPassword", "confirmPasswordError", "") : setError("confirmPassword", "confirmPasswordError", "Passwords do not match.");
            var v6 = courseVal !== "" ? setError("course", "courseError", "") : setError("course", "courseError", "Please select a course.");
            
            var errTerms = document.getElementById("termsError");
            if (!termsVal) {
                if (errTerms) errTerms.textContent = "Please agree to terms.";
            } else {
                if (errTerms) errTerms.textContent = "";
            }

            if (v1 && v2 && v3 && v4 && v5 && v6 && termsVal) {
                alert("Registration Successful! Welcome to StudentHub, " + nameVal);
                regForm.reset();
                if (strengthFill) strengthFill.style.width = "0%";
                if (strengthLabel) strengthLabel.textContent = "";
                document.querySelectorAll(".valid, .invalid").forEach(function(el) {
                    el.classList.remove("valid", "invalid");
                });
            }
        });
    }

    // 7. Login Handler (login.html)
    var loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var role = document.getElementById("loginRole").value;
            var user = document.getElementById("username").value.trim();
            var pass = document.getElementById("loginPassword").value.trim();

            if (!user || !pass) {
                alert("Please fill all required fields!");
                return;
            }

            alert("Logged in successfully as " + role.toUpperCase());
            window.location.href = role === "student" ? "dashboard.html" : "course.html";
        });
    }

    /* ==========================================================================
       8. Fetch API: Dynamic Events with Search, Filter, Sort & Pagination
       ========================================================================== */
    var eventsGrid = document.getElementById("eventsGrid");
    var eventsLoading = document.getElementById("eventsLoading");
    var eventsError = document.getElementById("eventsError");
    var eventSearch = document.getElementById("eventSearch");
    var eventCategoryFilter = document.getElementById("eventCategoryFilter");
    var eventSort = document.getElementById("eventSort");
    var eventsPagination = document.getElementById("eventsPagination");

    if (eventsGrid) {
        var allEvents = [];
        var currentPage = 1;
        var itemsPerPage = 6;

        // Fetch external JSON file
        function fetchEvents() {
            var dataUrl = "data/events.json";

            fetch(dataUrl)
                .then(function (response) {
                    if (!response.ok) throw new Error("Network response was not ok");
                    return response.json();
                })
                .then(function (data) {
                    allEvents = data;
                    if (eventsLoading) eventsLoading.style.display = "none";
                    applyFiltersAndRender();
                })
                .catch(function (error) {
                    // Try fallback relative path or display friendly error
                    fetch("../data/events.json")
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            allEvents = data;
                            if (eventsLoading) eventsLoading.style.display = "none";
                            applyFiltersAndRender();
                        })
                        .catch(function () {
                            if (eventsLoading) eventsLoading.style.display = "none";
                            if (eventsError) {
                                eventsError.style.display = "block";
                                eventsError.textContent = "⚠️ Error loading events from events.json: " + error.message;
                            }
                        });
                });
        }

        // Apply Search, Filter, Sort and Pagination
        function applyFiltersAndRender() {
            var searchQuery = eventSearch ? eventSearch.value.toLowerCase().trim() : "";
            var selectedCategory = eventCategoryFilter ? eventCategoryFilter.value : "All";
            var sortOption = eventSort ? eventSort.value : "date-asc";

            // 1. Search Filter (Array.filter)
            var filtered = allEvents.filter(function (event) {
                var matchesSearch = event.title.toLowerCase().includes(searchQuery) ||
                                    event.venue.toLowerCase().includes(searchQuery) ||
                                    event.organizer.toLowerCase().includes(searchQuery);
                var matchesCategory = (selectedCategory === "All" || event.category === selectedCategory);
                return matchesSearch && matchesCategory;
            });

            // 2. Sorting (Array.sort)
            filtered.sort(function (a, b) {
                if (sortOption === "date-asc") return new Date(a.date) - new Date(b.date);
                if (sortOption === "date-desc") return new Date(b.date) - new Date(a.date);
                if (sortOption === "title-asc") return a.title.localeCompare(b.title);
                if (sortOption === "title-desc") return b.title.localeCompare(a.title);
                return 0;
            });

            // 3. Pagination (Array.slice)
            var totalPages = Math.ceil(filtered.length / itemsPerPage) || 1;
            if (currentPage > totalPages) currentPage = 1;

            var startIndex = (currentPage - 1) * itemsPerPage;
            var paginatedItems = filtered.slice(startIndex, startIndex + itemsPerPage);

            // 4. Dynamic HTML Rendering
            renderEvents(paginatedItems);
            renderPagination(totalPages, filtered.length);
        }

        function getCategoryBadgeClass(category) {
            if (category === "Technical") return "badge-green";
            if (category === "Workshop") return "badge-blue";
            if (category === "Cultural") return "badge-orange";
            return "badge-blue";
        }

        function renderEvents(items) {
            if (items.length === 0) {
                eventsGrid.innerHTML = "<p style='grid-column: 1/-1; text-align: center; color: var(--text-muted);'>No events found matching your filter criteria.</p>";
                return;
            }

            var html = items.map(function (event) {
                var badgeClass = getCategoryBadgeClass(event.category);
                return `
                    <article class="card">
                        <span class="badge ${badgeClass}">${event.category}</span>
                        <h3 style="margin-top: 8px;">${event.title}</h3>
                        <p style="font-size: 0.9rem;"><strong>Date:</strong> ${event.date} &bull; <strong>Venue:</strong> ${event.venue}</p>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">${event.description}</p>
                        <p style="font-size: 0.8rem;"><strong>Organizer:</strong> ${event.organizer} | <strong>Seats:</strong> ${event.seats}</p>
                        <button class="btn open-event-reg" data-title="${event.title}" data-date="${event.date}" data-venue="${event.venue}" style="margin-top: 10px;">Register for Event</button>
                    </article>
                `;
            }).join("");

            eventsGrid.innerHTML = html;

            // Attach click listeners to open registration modal
            document.querySelectorAll(".open-event-reg").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    var title = this.getAttribute("data-title");
                    var date = this.getAttribute("data-date");
                    var venue = this.getAttribute("data-venue");
                    var modal = document.getElementById("eventModal");
                    var modalTitle = document.getElementById("modalEventTitle");
                    var modalDetails = document.getElementById("modalEventDetails");

                    if (modalTitle) modalTitle.textContent = "🎉 Register: " + title;
                    if (modalDetails) modalDetails.textContent = "Date: " + date + " | Venue: " + venue;
                    if (modal) modal.classList.add("open");
                });
            });
        }

        function renderPagination(totalPages, totalItems) {
            if (!eventsPagination) return;
            if (totalItems === 0) {
                eventsPagination.innerHTML = "";
                return;
            }

            var buttonsHtml = "";

            // Previous Button
            buttonsHtml += `<button class="page-btn" ${currentPage === 1 ? "disabled" : ""} id="prevPageBtn">&lt; Prev</button>`;

            // Page Number Buttons
            for (var i = 1; i <= totalPages; i++) {
                buttonsHtml += `<button class="page-btn ${i === currentPage ? "active" : ""}" data-page="${i}">${i}</button>`;
            }

            // Next Button
            buttonsHtml += `<button class="page-btn" ${currentPage === totalPages ? "disabled" : ""} id="nextPageBtn">Next &gt;</button>`;

            eventsPagination.innerHTML = buttonsHtml;

            // Pagination Click Listeners
            var prevBtn = document.getElementById("prevPageBtn");
            var nextBtn = document.getElementById("nextPageBtn");

            if (prevBtn) {
                prevBtn.addEventListener("click", function () {
                    if (currentPage > 1) {
                        currentPage--;
                        applyFiltersAndRender();
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener("click", function () {
                    if (currentPage < totalPages) {
                        currentPage++;
                        applyFiltersAndRender();
                    }
                });
            }

            eventsPagination.querySelectorAll("[data-page]").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    currentPage = parseInt(this.getAttribute("data-page"));
                    applyFiltersAndRender();
                });
            });
        }

        // Event Listeners for Filters
        if (eventSearch) eventSearch.addEventListener("input", function () { currentPage = 1; applyFiltersAndRender(); });
        if (eventCategoryFilter) eventCategoryFilter.addEventListener("change", function () { currentPage = 1; applyFiltersAndRender(); });
        if (eventSort) eventSort.addEventListener("change", applyFiltersAndRender);

        // Load JSON data on startup
        fetchEvents();
    }

    /* ==========================================================================
       9. Fetch API: Dynamic FAQ Accordion with Search (faqs.json)
       ========================================================================== */
    var faqAccordionContainer = document.getElementById("faqAccordionContainer");
    var faqLoading = document.getElementById("faqLoading");
    var faqSearch = document.getElementById("faqSearch");
    var faqCategoryFilter = document.getElementById("faqCategoryFilter");

    if (faqAccordionContainer) {
        var allFaqs = [];

        function fetchFaqs() {
            var dataUrl = "data/faqs.json";

            fetch(dataUrl)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    allFaqs = data;
                    if (faqLoading) faqLoading.style.display = "none";
                    renderFaqs();
                })
                .catch(function () {
                    fetch("../data/faqs.json")
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            allFaqs = data;
                            if (faqLoading) faqLoading.style.display = "none";
                            renderFaqs();
                        })
                        .catch(function () {
                            if (faqLoading) faqLoading.style.display = "none";
                        });
                });
        }

        function renderFaqs() {
            var query = faqSearch ? faqSearch.value.toLowerCase().trim() : "";
            var cat = faqCategoryFilter ? faqCategoryFilter.value : "All";

            var filtered = allFaqs.filter(function (faq) {
                var matchesQuery = faq.question.toLowerCase().includes(query) || faq.answer.toLowerCase().includes(query);
                var matchesCategory = (cat === "All" || faq.category === cat);
                return matchesQuery && matchesCategory;
            });

            if (filtered.length === 0) {
                faqAccordionContainer.innerHTML = "<p style='color: var(--text-muted); text-align: center;'>No FAQs found matching search.</p>";
                return;
            }

            var html = filtered.map(function (faq) {
                return `
                    <div class="accordion-item">
                        <button class="accordion-btn">
                            <span>[${faq.category}] ${faq.question}</span>
                            <span>▼</span>
                        </button>
                        <div class="accordion-body">
                            <p>${faq.answer}</p>
                        </div>
                    </div>
                `;
            }).join("");

            faqAccordionContainer.innerHTML = html;

            // Re-attach accordion toggle listeners
            faqAccordionContainer.querySelectorAll(".accordion-btn").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    var body = this.nextElementSibling;
                    body.classList.toggle("open");
                });
            });
        }

        if (faqSearch) faqSearch.addEventListener("input", renderFaqs);
        if (faqCategoryFilter) faqCategoryFilter.addEventListener("change", renderFaqs);

        fetchFaqs();
    }

});
