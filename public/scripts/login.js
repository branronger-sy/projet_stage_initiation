const loginTab = document.getElementById("showLogin");
const signupTab = document.getElementById("showSignup");
const loginSection = document.querySelector(".login-section");
const signupSection = document.querySelector(".signup-section");

loginTab.onclick = () => {
  loginTab.classList.add("active");
  signupTab.classList.remove("active");
  loginSection.classList.add("active");
  signupSection.classList.remove("active");
};

signupTab.onclick = () => {
  signupTab.classList.add("active");
  loginTab.classList.remove("active");
  signupSection.classList.add("active");
  loginSection.classList.remove("active");
};
