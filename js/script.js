// enforce 10+ years for dob inputs on load
$(function() {
  const dobInputs = $("#dob");
  if (dobInputs.length) {
    const now = new Date();
    now.setFullYear(now.getFullYear() - 10);
    const maxDob = now.toISOString().slice(0,10);
    dobInputs.attr("max", maxDob);
  }
  // auto-calc age when dob changes (register and profile modal)
  $(document).on('change', '#dob', function() {
    const val = $(this).val();
    if (!val) return;
    const age = calcAge(val);
    const ageInput = $('#age');
    if (ageInput.length && !isNaN(age)) {
      ageInput.val(age);
    }
  });
});

// compute age in years from yyyy-mm-dd (handles leap years)
function calcAge(isoDate) {
  const parts = isoDate.split('-');
  if (parts.length !== 3) return NaN;
  const y = parseInt(parts[0], 10);
  const m = parseInt(parts[1], 10) - 1;
  const d = parseInt(parts[2], 10);
  const dob = new Date(Date.UTC(y, m, d));
  const today = new Date();
  let age = today.getUTCFullYear() - dob.getUTCFullYear();
  const mo = today.getUTCMonth() - dob.getUTCMonth();
  if (mo < 0 || (mo === 0 && today.getUTCDate() < dob.getUTCDate())) {
    age--;
  }
  return age;
}
const API_BASE = "php/";

// quick toast/snackbar
function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
  }
  const toastEl = document.createElement('div');
  const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning text-dark' : 'bg-secondary';
  toastEl.className = `toast align-items-center text-white ${bgClass}`;
  toastEl.setAttribute('role', 'status');
  toastEl.setAttribute('aria-live', 'polite');
  toastEl.setAttribute('aria-atomic', 'true');
  toastEl.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type=\"button\" class=\"btn-close btn-close-white me-2 m-auto\" data-bs-dismiss=\"toast\" aria-label=\"Close\"></button></div>`;
  container.appendChild(toastEl);
  const bsToast = new bootstrap.Toast(toastEl, { delay: 3000, autohide: true });
  bsToast.show();
}

// handle register
$("#registerForm").on("submit", function(e) {
  e.preventDefault();
  const username = $("#username").val().trim();
  const email = $("#email").val().trim();
  const password = $("#password").val();
  const confirmPassword = $("#confirmPassword").val();
  const age = $("#age").val();
  const dob = $("#dob").val();
  const contact = $("#contact").val().trim();

  const usernameOk = /^(?=.*[A-Za-z])[A-Za-z0-9_.]{3,30}$/.test(username);
  const emailOk = /^\S+@\S+\.\S+$/.test(email) && email.length <= 254;
  const pwdOk = typeof password === "string" && password.length >= 8 && password.length <= 128;
  const confirmOk = password === confirmPassword;
  const ageOk = !age || (Number(age) >= 1 && Number(age) <= 120);
  const contactOk = !contact || /^[0-9+()\-\s]{7,20}$/.test(contact);
  const tenYearsAgo = (() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 10);
    return d.toISOString().slice(0,10);
  })();
  const dobOk = !dob || (dob <= tenYearsAgo);

  if (!usernameOk) return showToast("Username must be 3-30 chars and include a letter.", 'warning');
  if (!emailOk) return showToast("Please enter a valid email address.", 'warning');
  if (!pwdOk) return showToast("Password must be 8-128 characters.", 'warning');
  if (!confirmOk) return showToast("Passwords do not match.", 'warning');
  if (!ageOk) return showToast("Age must be between 1 and 120.", 'warning');
  if (!contactOk) return showToast("Contact looks invalid.", 'warning');
  if (!dobOk) return showToast("Date of birth must be at least 10 years ago.", 'warning');
  $.ajax({
    url: API_BASE + "register.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      username,
      email,
      password,
      age,
      dob,
      contact
    }),
    success: function(res) {
      if (res.success) {
        showToast("Registration successful. Please login.", 'success');
        window.location.href = "login.html";
      } else {
        showToast(res.message || 'Registration failed.', 'error');
      }
    }
  });
});

// handle login
$("#loginForm").on("submit", function(e) {
  e.preventDefault();
  const identifier = $("#identifier").val().trim();
  const password = $("#password").val();
  const idOk = identifier.length >= 3 && identifier.length <= 254;
  const pwdOk = typeof password === "string" && password.length >= 8 && password.length <= 128;
  if (!idOk) return showToast("Enter your username or email.", 'warning');
  if (!pwdOk) return showToast("Password must be 8-128 characters.", 'warning');
  $.ajax({
    url: API_BASE + "login.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      identifier,
      password
    }),
    success: function(res) {
      if (res.success) {
        localStorage.setItem("token", res.token);
        window.location.href = "profile.html";
      } else {
        showToast(res.message || 'Login failed.', 'error');
      }
    }
  });
});

// load profile; if no token, send to login
function loadProfile() {
  const token = localStorage.getItem("token");
  if (!token) {
    window.location.href = "login.html";
    return;
  }
  $.ajax({
    url: API_BASE + "get_profile.php",
    method: "POST",
    headers: { "Authorization": "Bearer " + token },
    success: function(res) {
      if (res.success) {
        $("#profileInfo").html(`
          <p class="mb-1"><strong>Username:</strong> ${res.user.username}</p>
          <p class="mb-1"><strong>Email:</strong> ${res.user.email}</p>
          <p class="mb-1"><strong>Age:</strong> ${res.user.age || ""}</p>
          <p class="mb-1"><strong>DOB:</strong> ${res.user.dob || ""}</p>
          <p class="mb-0"><strong>Contact:</strong> ${res.user.contact || ""}</p>
        `);
      } else {
        showToast(res.message || 'Failed to load profile.', 'error');
        window.location.href = "login.html";
      }
    }
  });
}

if (window.location.pathname.includes("profile.html")) {
  loadProfile();
}

// save profile from modal, then close it and refresh
$("#updateForm").on("submit", function(e) {
  e.preventDefault();
  const token = localStorage.getItem("token");
  const age = $("#age").val();
  const dob = $("#dob").val();
  const contact = $("#contact").val().trim();
  const ageOk = !age || (Number(age) >= 1 && Number(age) <= 120);
  const contactOk = !contact || /^[0-9+()\-\s]{7,20}$/.test(contact);
  const tenYearsAgo = (() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 10);
    return d.toISOString().slice(0,10);
  })();
  const dobOk = !dob || (dob <= tenYearsAgo);
  if (!ageOk) return showToast("Age must be between 1 and 120.", 'warning');
  if (!contactOk) return showToast("Contact looks invalid.", 'warning');
  if (!dobOk) return showToast("Date of birth must be at least 10 years ago.", 'warning');
  $.ajax({
    url: API_BASE + "update_profile.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      token: token,
      age,
      dob,
      contact
    }),
    success: function(res) {
      showToast(res.message || 'Update completed.', res.success ? 'success' : 'error');
      if (res.success) {
        if (typeof bootstrap !== "undefined") {
          const modalEl = document.getElementById('updateModal');
          if (modalEl) {
            const instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            instance.hide();
          }
        }
        loadProfile();
      }
    }
  });
});

// logout
$("#logoutBtn").on("click", function() {
  const token = localStorage.getItem("token");
$.ajax({
    url: API_BASE + "logout.php", // backend filename is currently 'logut.php'
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({ token: token }),
    success: function(res) {
      localStorage.removeItem("token");
      window.location.href = "login.html";
    }
  });
});
