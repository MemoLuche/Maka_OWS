/**
 * Custom Modals - Sistema de modales personalizados para reemplazar alert() y confirm()
 * Requiere Bootstrap 5
 */

// Función personalizada para Alert
function customAlert(message, type = 'info', title = null) {
    // Verificar si el modal ya existe, si no, crearlo
    let modalElement = document.getElementById('customAlertModal');
    
    if (!modalElement) {
        createAlertModal();
        modalElement = document.getElementById('customAlertModal');
    }
    
    const modal = new bootstrap.Modal(modalElement);
    const alertIcon = document.getElementById('alertIcon');
    const alertTitle = document.getElementById('alertTitle');
    const alertMessage = document.getElementById('alertMessage');
    const okBtn = document.getElementById('alertOkBtn');
    
    // Configurar icono y título según el tipo
    let iconClass = '';
    let defaultTitle = '';
    let btnClass = 'btn-primary';
    
    switch(type) {
        case 'success':
            iconClass = 'bi-check-circle-fill text-success';
            defaultTitle = 'Éxito';
            btnClass = 'btn-success';
            break;
        case 'error':
            iconClass = 'bi-exclamation-triangle-fill text-danger';
            defaultTitle = 'Error';
            btnClass = 'btn-danger';
            break;
        case 'warning':
            iconClass = 'bi-exclamation-circle-fill text-warning';
            defaultTitle = 'Advertencia';
            btnClass = 'btn-warning';
            break;
        default:
            iconClass = 'bi-info-circle-fill text-info';
            defaultTitle = 'Información';
            btnClass = 'btn-primary';
    }
    
    alertIcon.className = 'bi me-2 ' + iconClass;
    alertTitle.textContent = title || defaultTitle;
    alertMessage.innerHTML = message;
    okBtn.className = 'btn ' + btnClass;
    
    modal.show();
}

// Función personalizada para Confirm
function customConfirm(message, callback, title = 'Confirmación') {
    // Verificar si el modal ya existe, si no, crearlo
    let modalElement = document.getElementById('customConfirmModal');
    
    if (!modalElement) {
        createConfirmModal();
        modalElement = document.getElementById('customConfirmModal');
    }
    
    const modal = new bootstrap.Modal(modalElement);
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmOkBtn');
    
    confirmTitle.textContent = title;
    confirmMessage.innerHTML = message;
    
    // Remover listeners anteriores clonando el botón
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Agregar nuevo listener
    newConfirmBtn.addEventListener('click', function() {
        modal.hide();
        if (callback) callback();
    });
    
    modal.show();
}

// Crear el modal de Alert dinámicamente
function createAlertModal() {
    const modalHTML = `
        <div class="modal fade" id="customAlertModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="bi me-2" id="alertIcon"></i>
                            <span id="alertTitle"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="mb-0" id="alertMessage"></p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="alertOkBtn">
                            <i class="bi bi-check-lg me-1"></i>Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Crear el modal de Confirm dinámicamente
function createConfirmModal() {
    const modalHTML = `
        <div class="modal fade" id="customConfirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="bi bi-question-circle-fill text-warning me-2"></i>
                            <span id="confirmTitle">Confirmación</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="mb-0" id="confirmMessage"></p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmOkBtn">
                            <i class="bi bi-check-lg me-1"></i>Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Inicializar los modales cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        createAlertModal();
        createConfirmModal();
    });
} else {
    createAlertModal();
    createConfirmModal();
}
