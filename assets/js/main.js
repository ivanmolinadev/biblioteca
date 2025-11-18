// JavaScript principal para el Sistema de Biblioteca y Préstamos

document.addEventListener("DOMContentLoaded", function () {
  // Inicialización de componentes
  initializeComponents();

  // Configurar validaciones
  setupFormValidation();

  // Configurar búsquedas en tiempo real
  setupLiveSearch();

  // Configurar tooltips
  setupTooltips();

  // Configurar confirmaciones
  setupConfirmations();
});

/**
 * Inicializar componentes de Bootstrap
 */
function initializeComponents() {
  // Inicializar tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Inicializar popovers
  const popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
}

/**
 * Configurar validación de formularios
 */
function setupFormValidation() {
  const forms = document.querySelectorAll(".needs-validation");

  Array.from(forms).forEach((form) => {
    form.addEventListener("submit", function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();

        // Enfocar el primer campo inválido
        const firstInvalid = form.querySelector(":invalid");
        if (firstInvalid) {
          firstInvalid.focus();
        }
      }
      form.classList.add("was-validated");
    });

    // Validación en tiempo real para campos específicos
    const inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach((input) => {
      input.addEventListener("blur", function () {
        validateField(this);
      });
    });
  });
}

/**
 * Validar campo individual
 */
function validateField(field) {
  const isValid = field.checkValidity();
  const feedback = field.parentNode.querySelector(".invalid-feedback");

  if (!isValid && feedback) {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
  } else if (isValid && field.value.trim() !== "") {
    field.classList.add("is-valid");
    field.classList.remove("is-invalid");
  } else {
    field.classList.remove("is-valid", "is-invalid");
  }
}

/**
 * Configurar búsqueda en tiempo real
 */
function setupLiveSearch() {
  const searchInputs = document.querySelectorAll(".live-search");

  searchInputs.forEach((input) => {
    let timeout;
    input.addEventListener("input", function () {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        performLiveSearch(this);
      }, 300);
    });
  });
}

/**
 * Realizar búsqueda en tiempo real
 */
function performLiveSearch(input) {
  const searchTerm = input.value.trim();
  const targetTable = input.getAttribute("data-target");

  if (!targetTable) return;

  const table = document.querySelector(targetTable);
  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    const match = text.includes(searchTerm.toLowerCase());
    row.style.display = match ? "" : "none";
  });

  // Actualizar mensaje de "no hay resultados"
  updateNoResultsMessage(table, searchTerm);
}

/**
 * Actualizar mensaje de no hay resultados
 */
function updateNoResultsMessage(table, searchTerm) {
  const tbody = table.querySelector("tbody");
  const visibleRows = tbody.querySelectorAll(
    'tr:not([style*="display: none"])'
  );

  // Remover mensaje anterior
  const existingMessage = tbody.querySelector(".no-results-row");
  if (existingMessage) {
    existingMessage.remove();
  }

  // Agregar mensaje si no hay resultados
  if (visibleRows.length === 0 && searchTerm.length > 0) {
    const colSpan = table.querySelectorAll("thead th").length;
    const noResultsRow = document.createElement("tr");
    noResultsRow.className = "no-results-row";
    noResultsRow.innerHTML = `
            <td colspan="${colSpan}" class="text-center text-muted py-4">
                <i class="bi bi-search"></i><br>
                No se encontraron resultados para "${searchTerm}"
            </td>
        `;
    tbody.appendChild(noResultsRow);
  }
}

/**
 * Configurar tooltips
 */
function setupTooltips() {
  // Tooltips para botones de acción
  const actionButtons = document.querySelectorAll(".btn[title], .action-btn");
  actionButtons.forEach((btn) => {
    if (!btn.getAttribute("data-bs-toggle")) {
      btn.setAttribute("data-bs-toggle", "tooltip");
      new bootstrap.Tooltip(btn);
    }
  });
}

/**
 * Configurar confirmaciones
 */
function setupConfirmations() {
  // Confirmación para eliminaciones
  const deleteButtons = document.querySelectorAll(
    '.btn-delete, .delete-btn, [data-action="delete"]'
  );
  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      const message =
        this.getAttribute("data-confirm") ||
        "¿Está seguro de que desea eliminar este registro?";
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // Confirmación para acciones importantes
  const confirmButtons = document.querySelectorAll("[data-confirm]");
  confirmButtons.forEach((btn) => {
    if (
      !btn.classList.contains("btn-delete") &&
      !btn.classList.contains("delete-btn")
    ) {
      btn.addEventListener("click", function (e) {
        const message = this.getAttribute("data-confirm");
        if (message && !confirm(message)) {
          e.preventDefault();
        }
      });
    }
  });
}

/**
 * Mostrar modal de carga
 */
function showLoadingModal(message = "Procesando...") {
  const modal = document.createElement("div");
  modal.id = "loadingModal";
  modal.className = "modal fade";
  modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mb-0">${message}</p>
                </div>
            </div>
        </div>
    `;
  document.body.appendChild(modal);

  const bootstrapModal = new bootstrap.Modal(modal);
  bootstrapModal.show();

  return bootstrapModal;
}

/**
 * Ocultar modal de carga
 */
function hideLoadingModal() {
  const modal = document.getElementById("loadingModal");
  if (modal) {
    const bootstrapModal = bootstrap.Modal.getInstance(modal);
    if (bootstrapModal) {
      bootstrapModal.hide();
      modal.addEventListener("hidden.bs.modal", function () {
        modal.remove();
      });
    }
  }
}

/**
 * Mostrar notificación toast
 */
function showToast(message, type = "info") {
  const toastContainer = getOrCreateToastContainer();

  const toast = document.createElement("div");
  toast.className = `toast align-items-center text-white bg-${type} border-0`;
  toast.setAttribute("role", "alert");
  toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

  toastContainer.appendChild(toast);

  const bootstrapToast = new bootstrap.Toast(toast, {
    autohide: true,
    delay: 5000,
  });
  bootstrapToast.show();

  // Limpiar después de ocultar
  toast.addEventListener("hidden.bs.toast", function () {
    toast.remove();
  });
}

/**
 * Obtener o crear contenedor de toasts
 */
function getOrCreateToastContainer() {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container position-fixed bottom-0 end-0 p-3";
    container.style.zIndex = "1055";
    document.body.appendChild(container);
  }
  return container;
}

/**
 * Formatear fecha
 */
function formatDate(date, options = {}) {
  const defaultOptions = {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  };

  const formatOptions = { ...defaultOptions, ...options };
  return new Date(date).toLocaleDateString("es-ES", formatOptions);
}

/**
 * Formatear moneda
 */
function formatCurrency(amount) {
  return new Intl.NumberFormat("es-MX", {
    style: "currency",
    currency: "MXN",
  }).format(amount);
}

/**
 * Debounce para funciones
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Validar ISBN
 */
function validateISBN(isbn) {
  isbn = isbn.replace(/[-\s]/g, "");

  if (isbn.length === 10) {
    return validateISBN10(isbn);
  } else if (isbn.length === 13) {
    return validateISBN13(isbn);
  }

  return false;
}

function validateISBN10(isbn) {
  let sum = 0;
  for (let i = 0; i < 9; i++) {
    sum += parseInt(isbn[i]) * (10 - i);
  }

  const checkDigit = isbn[9].toUpperCase();
  const calculatedCheck = (11 - (sum % 11)) % 11;

  return (
    checkDigit === (calculatedCheck === 10 ? "X" : calculatedCheck.toString())
  );
}

function validateISBN13(isbn) {
  let sum = 0;
  for (let i = 0; i < 12; i++) {
    sum += parseInt(isbn[i]) * (i % 2 === 0 ? 1 : 3);
  }

  const checkDigit = parseInt(isbn[12]);
  const calculatedCheck = (10 - (sum % 10)) % 10;

  return checkDigit === calculatedCheck;
}

/**
 * Validar email
 */
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * Capitalize primera letra
 */
function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

/**
 * Función para AJAX con fetch
 */
async function makeRequest(url, options = {}) {
  const defaultOptions = {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
    },
  };

  const requestOptions = { ...defaultOptions, ...options };

  try {
    const response = await fetch(url, requestOptions);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const contentType = response.headers.get("content-type");
    if (contentType && contentType.includes("application/json")) {
      return await response.json();
    } else {
      return await response.text();
    }
  } catch (error) {
    console.error("Request failed:", error);
    throw error;
  }
}

// Exportar funciones para uso global
window.BibliotecaJS = {
  showLoadingModal,
  hideLoadingModal,
  showToast,
  formatDate,
  formatCurrency,
  debounce,
  validateISBN,
  validateEmail,
  capitalize,
  makeRequest,
};
