function toggle() {
    const nav = document.getElementsByClassName("nav-links").item(0);
    nav.classList.toggle("active");
  }
  function toggleSearch() {
    const searchBox = document.querySelector(".search-box");
    searchBox.classList.toggle("active");
  }
  function handleSearchClick(e) {
    const searchBox = document.querySelector(".search-box");
  
    if (window.innerWidth <= 768 && !searchBox.classList.contains("active")) {
      e.preventDefault(); 
      searchBox.classList.add("active");
      document.getElementById("search-input").focus();
      getElementById("search-results").classList.add("active")
    }
  }
  t=200;
  window.addEventListener("DOMContentLoaded", () => {
    let input = document.getElementsByClassName("search-input")[0];
    let results = document.getElementById("search-results");
  
    let observer = new ResizeObserver(() => {
      let w = parseFloat(window.getComputedStyle(input).width);
      if (w === 0) {
        results.style.display = "none";
      } else {
        results.style.display = "";
      }
    });
  
    observer.observe(input);
  });
  