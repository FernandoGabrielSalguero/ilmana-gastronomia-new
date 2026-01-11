// Inject spinner HTML
document.body.insertAdjacentHTML("beforeend", `
    <div id="globalSpinner" class="spinner-overlay hidden">
      <div class="spinner-box">
        <div class="spinner"></div>
        <p class="spinner-text">Estamos procesando tu solicitud</p>
      </div>
    </div>
  `);

// Inject spinner CSS
const style = document.createElement("style");
style.innerHTML = `
  .spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }
  .spinner-box {
    text-align: center;
  }
  .spinner {
    border: 6px solid #ccc;
    border-top: 6px solid #8a2be2;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    margin: 0 auto 10px;
    animation: spin 0.8s linear infinite;
  }
  .spinner-text {
    font-weight: bold;
    color: #444;
    font-size: 1.1rem;
  }
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  .hidden {
    display: none !important;
  }
  `;
document.head.appendChild(style);

// Spinner logic
let spinnerStart = null;
let spinnerTimeout = null;

window.showSpinner = function () {
    spinnerStart = Date.now();
    document.getElementById("globalSpinner").classList.remove("hidden");
};

window.hideSpinner = function () {
    const elapsed = Date.now() - spinnerStart;
    const remaining = 2000 - elapsed;
    clearTimeout(spinnerTimeout);
    if (remaining > 0) {
        spinnerTimeout = setTimeout(() => {
            document.getElementById("globalSpinner").classList.add("hidden");
        }, remaining);
    } else {
        document.getElementById("globalSpinner").classList.add("hidden");
    }
};

// Auto-wrap fetch
const originalFetch = window.fetch;
window.fetch = function (...args) {
    showSpinner();
    return originalFetch(...args)
        .then(res => res)
        .catch(err => { throw err; })
        .finally(() => hideSpinner());
};