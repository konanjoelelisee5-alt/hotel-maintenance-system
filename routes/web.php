<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TechnicianAvailabilityController;
use App\Http\Controllers\TechnicianSkillController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InterventionReportController;
use App\Http\Controllers\InterventionSessionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrderReceptionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\QualityControlController;
use App\Http\Controllers\EscalationRuleController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkOrderPriorityController;
use App\Http\Controllers\WorkOrderTypeController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\PartReservationController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\MaintenancePlanController;


// === PAGE D'ACCUEIL ===
Route::get('/', function () {
    return redirect()->route('login');
});

// === DASHBOARDS PAR RÔLE ===
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
});

Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/manager/dashboard', [DashboardController::class, 'manager'])->name('manager.dashboard');
});

Route::middleware(['auth', 'role:technicien'])->group(function () {
    Route::get('/technicien/dashboard', [DashboardController::class, 'technicien'])->name('technicien.dashboard');
});

Route::middleware(['auth', 'role:housekeeping'])->group(function () {
    Route::get('/housekeeping/dashboard', [DashboardController::class, 'housekeeping'])->name('housekeeping.dashboard');
});

Route::middleware(['auth', 'role:reception'])->group(function () {
    Route::get('/reception/dashboard', [DashboardController::class, 'reception'])->name('reception.dashboard');
});

// === MODULE B : GESTION DES ORDRES DE TRAVAIL (OT) ===
Route::middleware('auth')->group(function () {

    Route::resource('work-orders', WorkOrderController::class);

    Route::patch('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])
        ->name('work-orders.status.update');

    Route::post('/work-orders/{workOrder}/comments', [WorkOrderController::class, 'storeComment'])
        ->name('work-orders.comments.store');

    Route::post('/work-orders/{workOrder}/attachments', [WorkOrderController::class, 'storeAttachments'])
        ->name('work-orders.attachments.store');

    Route::delete('/work-orders/{workOrder}/attachments/{attachment}', [WorkOrderController::class, 'destroyAttachment'])
        ->name('work-orders.attachments.destroy');

});

// === MODULE C : PLANIFICATION & ORDONNANCEMENT ===
Route::middleware('auth')->group(function () {
    Route::get('/planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::get('/planning/technicien/{technician}', [PlanningController::class, 'byTechnician'])->name('planning.technician');
    Route::get('/planning/events', [PlanningController::class, 'events'])->name('planning.events');
});

// Planification/replanification et gestion compétences/disponibilités : admin + manager
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::get('/work-orders/{workOrder}/schedule', [PlanningController::class, 'schedule'])->name('work-orders.schedule');
    Route::post('/work-orders/{workOrder}/schedule', [PlanningController::class, 'storeSchedule'])->name('work-orders.schedule.store');

    Route::get('/planning/technicien/{technician}/disponibilites', [TechnicianAvailabilityController::class, 'index'])->name('planning.availabilities.index');
    Route::post('/planning/technicien/{technician}/disponibilites', [TechnicianAvailabilityController::class, 'store'])->name('planning.availabilities.store');
    Route::delete('/planning/technicien/{technician}/disponibilites/{availability}', [TechnicianAvailabilityController::class, 'destroy'])->name('planning.availabilities.destroy');

    Route::get('/planning/technicien/{technician}/competences', [TechnicianSkillController::class, 'edit'])->name('planning.skills.edit');
    Route::put('/planning/technicien/{technician}/competences', [TechnicianSkillController::class, 'update'])->name('planning.skills.update');
});

// === ROUTES COMMUNES (tous utilisateurs connectés) ===
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// === MODULE G : CONTRÔLE QUALITÉ & VALIDATION ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {

    // Démarrer un contrôle qualité (imbriqué sous l'OT concerné)
    Route::get('/work-orders/{workOrder}/quality-controls/create', [QualityControlController::class, 'create'])
        ->name('quality-controls.create');
    Route::post('/work-orders/{workOrder}/quality-controls', [QualityControlController::class, 'store'])
        ->name('quality-controls.store');

    // Consulter et évaluer un contrôle qualité (autonome, via son propre ID)
    Route::get('/quality-controls/{qualityControl}', [QualityControlController::class, 'show'])
        ->name('quality-controls.show');
    Route::post('/quality-controls/{qualityControl}/review', [QualityControlController::class, 'review'])
        ->name('quality-controls.review');

    // Demandes de correction
    Route::patch('/correction-requests/{correctionRequest}/resolve', [CorrectionRequestController::class, 'markAsResolved'])
        ->name('correction-requests.resolve');

});

// === NOTIFICATIONS ===
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// === MODULE D : GESTION INTERVENTIONS & EXÉCUTION ===
Route::middleware('auth')->group(function () {

    Route::post('/work-orders/{workOrder}/sessions/start', [InterventionSessionController::class, 'start'])
        ->name('work-orders.sessions.start');

    Route::post('/work-orders/{workOrder}/sessions/stop', [InterventionSessionController::class, 'stop'])
        ->name('work-orders.sessions.stop');

    Route::post('/work-orders/{workOrder}/report', [InterventionReportController::class, 'store'])
        ->name('work-orders.report.store');

});

// === MODULE H : NOTIFICATIONS, SLA & ESCALADE (réservé admin) ===
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('sla-policies', SlaPolicyController::class)->except(['show']);
    Route::resource('escalation-rules', EscalationRuleController::class)->except(['show']);
});

// === MODULE I : REPORTING & DASHBOARD ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
});

// === MODULE J : ADMINISTRATION & PARAMÉTRAGE (réservé admin) ===
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('work-order-types', WorkOrderTypeController::class)->except(['show']);
    Route::resource('work-order-priorities', WorkOrderPriorityController::class)->except(['show']);
    Route::resource('skills', SkillController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});

// === MODULE ACHATS & FOURNISSEURS (admin + manager) ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('suppliers', SupplierController::class);
    // Fournisseurs (CRUD classique)
    Route::resource('suppliers', SupplierController::class);

    // Bons de commande
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::patch('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.status.update');

    // Réception
    Route::post('/purchase-orders/{purchaseOrder}/reception', [PurchaseOrderReceptionController::class, 'store'])->name('purchase-orders.reception.store');

    // Factures
    Route::post('/purchase-orders/{purchaseOrder}/invoices', [InvoiceController::class, 'store'])->name('purchase-orders.invoices.store');
    Route::patch('/purchase-orders/{purchaseOrder}/invoices/{invoice}/paid', [InvoiceController::class, 'markAsPaid'])->name('purchase-orders.invoices.paid');

});

// === MODULE F : MAINTENANCE PRÉVENTIVE (AUTOMATISATION) ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('maintenance-plans', MaintenancePlanController::class);
    Route::post('/maintenance-plans/{maintenancePlan}/generate', [MaintenancePlanController::class, 'generateNow'])
        ->name('maintenance-plans.generate');
});

// === MODULE E : GESTION PIÈCES & STOCK ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('parts', PartController::class)->except(['destroy'])->parameters(['parts' => 'part']);
    Route::delete('/parts/{part}', [PartController::class, 'destroy'])->name('parts.destroy');
    Route::post('/parts/{part}/movements', [StockMovementController::class, 'store'])->name('parts.movements.store');
});

// Réservation accessible à tous les rôles opérant sur les OT
Route::middleware('auth')->group(function () {
    Route::post('/work-orders/{workOrder}/reservations', [PartReservationController::class, 'store'])->name('work-orders.reservations.store');
    Route::post('/work-orders/{workOrder}/reservations/{reservation}/withdraw', [PartReservationController::class, 'withdraw'])->name('work-orders.reservations.withdraw');
    Route::delete('/work-orders/{workOrder}/reservations/{reservation}', [PartReservationController::class, 'cancel'])->name('work-orders.reservations.cancel');
});

Route::resource('skills', SkillController::class)->except(['show']);
Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

require __DIR__.'/auth.php';