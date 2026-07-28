// ---- Password show/hide toggle ----
document.querySelectorAll('.toggle-password').forEach((btn) => {
  btn.addEventListener('click', () => {
    const targetId = btn.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (!input) return;

    const nowVisible = input.type === 'password';
    input.type = nowVisible ? 'text' : 'password';
    btn.classList.toggle('is-visible', nowVisible);
    btn.setAttribute('aria-label', nowVisible ? 'Hide password' : 'Show password');
  });
});

// ---- Shared client-side validation (mirrors includes/functions.php) ----
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PASSWORD_REGEX = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

function liveValidate(inputEl, errorEl, validatorFn) {
  if (!inputEl || !errorEl) return;
  const run = () => {
    const msg = validatorFn(inputEl.value);
    errorEl.textContent = msg;
    inputEl.classList.toggle('invalid', !!msg);
    return !msg;
  };
  inputEl.addEventListener('blur', run);
  inputEl.addEventListener('input', () => {
    if (inputEl.classList.contains('invalid')) run();
  });
}

function validateFullName(v) {
  if (!v || !v.trim()) return 'Full name is required.';
  if (v.trim().length < 2) return 'Full name is too short.';
  return '';
}
function validateEmail(v) {
  if (!v || !v.trim()) return 'Email is required.';
  if (!EMAIL_REGEX.test(v.trim())) return 'Enter a valid email address.';
  return '';
}
function validatePassword(v) {
  if (!v) return 'Password is required.';
  if (!PASSWORD_REGEX.test(v)) return 'Min 8 chars with uppercase, lowercase, number & special character.';
  return '';
}
function validateConfirm(pw, confirm) {
  if (!confirm) return 'Please confirm your password.';
  if (pw !== confirm) return 'Passwords do not match.';
  return '';
}

// Wire up whichever fields exist on the current page.
document.addEventListener('DOMContentLoaded', () => {
  const fullName = document.getElementById('fullName');
  const email = document.getElementById('email');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirmPassword');

  if (fullName) liveValidate(fullName, document.getElementById('fullNameError'), validateFullName);
  if (email) liveValidate(email, document.getElementById('emailError'), validateEmail);
  if (password) liveValidate(password, document.getElementById('passwordError'), validatePassword);
  if (confirmPassword) {
    liveValidate(confirmPassword, document.getElementById('confirmPasswordError'), (v) =>
      validateConfirm(password ? password.value : '', v)
    );
    if (password) {
      password.addEventListener('input', () => {
        if (confirmPassword.value) {
          const msg = validateConfirm(password.value, confirmPassword.value);
          document.getElementById('confirmPasswordError').textContent = msg;
          confirmPassword.classList.toggle('invalid', !!msg);
        }
      });
    }
  }
});
