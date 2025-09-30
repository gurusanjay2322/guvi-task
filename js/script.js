const API_BASE = "../php/"; // adjust path if needed

// ---- REGISTER ----
$("#registerForm").on("submit", function(e) {
  e.preventDefault();
  $.ajax({
    url: API_BASE + "register.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      username: $("#username").val(),
      email: $("#email").val(),
      password: $("#password").val(),
      age: $("#age").val(),
      dob: $("#dob").val(),
      contact: $("#contact").val()
    }),
    success: function(res) {
      if (res.success) {
        alert("Registration successful. Please login.");
        window.location.href = "login.html";
      } else {
        alert(res.message);
      }
    }
  });
});

// ---- LOGIN ----
$("#loginForm").on("submit", function(e) {
  e.preventDefault();
  $.ajax({
    url: API_BASE + "login.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      identifier: $("#identifier").val(),
      password: $("#password").val()
    }),
    success: function(res) {
      if (res.success) {
        localStorage.setItem("token", res.token);
        window.location.href = "profile.html";
      } else {
        alert(res.message);
      }
    }
  });
});

// ---- GET PROFILE ----
if (window.location.pathname.includes("profile.html")) {
  const token = localStorage.getItem("token");
  if (!token) {
    window.location.href = "login.html";
  } else {
    $.ajax({
      url: API_BASE + "get_profile.php",
      method: "POST",
      headers: { "Authorization": "Bearer " + token },
      success: function(res) {
        if (res.success) {
          $("#profileInfo").html(`
            <p><strong>Username:</strong> ${res.user.username}</p>
            <p><strong>Email:</strong> ${res.user.email}</p>
            <p><strong>Age:</strong> ${res.user.age || ""}</p>
            <p><strong>DOB:</strong> ${res.user.dob || ""}</p>
            <p><strong>Contact:</strong> ${res.user.contact || ""}</p>
          `);
        } else {
          alert(res.message);
          window.location.href = "login.html";
        }
      }
    });
  }
}

// ---- UPDATE PROFILE ----
$("#updateForm").on("submit", function(e) {
  e.preventDefault();
  const token = localStorage.getItem("token");
  $.ajax({
    url: API_BASE + "update_profile.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      token: token,
      age: $("#age").val(),
      dob: $("#dob").val(),
      contact: $("#contact").val()
    }),
    success: function(res) {
      alert(res.message);
      if (res.success) location.reload();
    }
  });
});

// ---- LOGOUT ----
$("#logoutBtn").on("click", function() {
  const token = localStorage.getItem("token");
  $.ajax({
    url: API_BASE + "logout.php",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify({ token: token }),
    success: function(res) {
      localStorage.removeItem("token");
      window.location.href = "login.html";
    }
  });
});
