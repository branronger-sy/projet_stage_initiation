document.addEventListener("DOMContentLoaded", function() {
    const searchBox = document.querySelector(".search-box");
    const searchBtn = document.querySelector(".search-btn");
    const searchInput = document.getElementById("search-input");
    const searchResults = document.getElementById("search-results");
    const searchForm = document.getElementById("search-form");

    let debounceTimer = null;
    let controller = null;
    let highlightedIndex = -1;
    let items = [];

    const MIN_LENGTH = 2;
    const DEBOUNCE = 300;
    if (searchBox && searchBtn && searchInput) {
        searchBtn.addEventListener("click", function(e) {
            if (window.innerWidth <= 896) {
                if (!searchBox.classList.contains("active")) {
                    e.preventDefault(); // أول ضغطة: لا تبحث
                    searchBox.classList.add("active");
                    searchInput.focus();
                } else if (searchInput.value.trim() === "") {
                    e.preventDefault(); // منع بحث فارغ
                    searchInput.focus();
                }
            }
        });
    }
    searchResults.setAttribute("role", "listbox");
    searchResults.setAttribute("aria-label", "Search suggestions");

    function clearResults() {
        searchResults.innerHTML = "";
        searchResults.style.display = "none";
        highlightedIndex = -1;
        items = [];
    }

    function renderResults(data) {
        searchResults.innerHTML = "";
        items = data || [];

        if (!items.length) {
            const no = document.createElement("div");
            no.className = "search-item no-results";
            no.textContent = "No results";
            searchResults.appendChild(no);
            searchResults.style.display = "block";
            highlightedIndex = -1;
            return;
        }

        items.forEach((item, idx) => {
            const div = document.createElement("div");
            div.className = "search-item";
            div.setAttribute("role", "option");
            div.dataset.index = idx;
            div.dataset.id = item.id;
            div.textContent = item.name;

            div.addEventListener("click", () => {
                window.location.href = `index.php?page=product&id=${encodeURIComponent(item.id)}`;
            });

            div.addEventListener("mouseenter", () => setHighlight(idx));

            searchResults.appendChild(div);
        });

        searchResults.style.display = "block";
        highlightedIndex = -1;
    }

    function setHighlight(index) {
        const children = Array.from(searchResults.querySelectorAll(".search-item"));
        children.forEach((ch, i) => {
            if (i === index) {
                ch.classList.add("highlight");
                ch.setAttribute("aria-selected", "true");
                highlightedIndex = index;
            } else {
                ch.classList.remove("highlight");
                ch.setAttribute("aria-selected", "false");
            }
        });
    }

    function goToHighlight() {
        if (highlightedIndex >= 0 && items[highlightedIndex]) {
            const id = items[highlightedIndex].id;
            window.location.href = `index.php?page=product&id=${encodeURIComponent(id)}`;
        }
    }

    searchInput.addEventListener("input", function() {
        const term = this.value.trim();
        clearTimeout(debounceTimer);

        if (term.length < MIN_LENGTH) {
            clearResults();
            return;
        }

        debounceTimer = setTimeout(() => {
            if (controller) controller.abort();
            controller = new AbortController();

            fetch(`../includes/ajax_search.php?term=${encodeURIComponent(term)}`, { signal: controller.signal })
                .then(res => {
                    if (!res.ok) throw new Error("Network response was not ok");
                    return res.json();
                })
                .then(data => renderResults(data))
                .catch(err => {
                    if (err.name === "AbortError") return;
                    console.error("Search error:", err);
                    clearResults();
                });
        }, DEBOUNCE);
    });

    searchInput.addEventListener("keydown", function(e) {
        const children = Array.from(searchResults.querySelectorAll(".search-item"));
        if (e.key === "ArrowDown") {
            e.preventDefault();
            if (!children.length) return;
            const next = (highlightedIndex + 1) % children.length;
            setHighlight(next);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            if (!children.length) return;
            const prev = (highlightedIndex - 1 + children.length) % children.length;
            setHighlight(prev);
        } else if (e.key === "Enter") {
            if (highlightedIndex >= 0) {
                e.preventDefault();
                goToHighlight();
            }
        } else if (e.key === "Escape") {
            clearResults();
        }
    });

    document.addEventListener("click", function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.style.display = "none";
        }
    });
});
