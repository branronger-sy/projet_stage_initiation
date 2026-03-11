document.addEventListener("DOMContentLoaded", () => {
  const editBtn = document.getElementById("editBtn");
  const saveBtn = document.getElementById("saveBtn");
  const cancelBtn = document.getElementById("cancelBtn");
  const passwordBtn = document.getElementById("passwordBtn");
  const message = document.getElementById("message");
  const form = document.getElementById("infoForm");

  function toggleEditMode(editMode) {
    document.querySelectorAll(".view").forEach(el => el.classList.toggle("hidden", editMode));
    document.querySelectorAll(".edit").forEach(el => el.classList.toggle("hidden", !editMode));
    editBtn.classList.toggle("hidden", editMode);
    saveBtn.classList.toggle("hidden", !editMode);
    cancelBtn.classList.toggle("hidden", !editMode);
    passwordBtn.classList.toggle("hidden", !editMode);

  }

  editBtn.addEventListener("click", () => toggleEditMode(true));
  cancelBtn.addEventListener("click", () => {
    toggleEditMode(false);
    form.reset();
  });
  passwordBtn.addEventListener("click", () => {
    document.querySelector(".password-section").classList.toggle("hidden");
  });

  form.addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(form);
    formData.append("action", "update_info");

      const res = await fetch("../includes/update_infos.php", {
        method: "POST",
        body: formData
      });
      const data = await res.json();
      message.textContent = data.message;
      message.style.color = data.status === "success" ? "green" : "red";

      if (data.status === "success") {
        setTimeout(() => location.reload(), 1200);
      }
  });
});
