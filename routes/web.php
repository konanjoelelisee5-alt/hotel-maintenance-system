<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HousekeepingSupervisionController;
use App\Http\Controllers\QuickReportController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\RoomBlockController;
use App\Http\Controllers\RoomInspectionController;
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
use App\Http\Controllers\RoomController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\QualityControlController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WorkOrderConfirmationController;
use App\Http\Controllers\EscalationRuleController;
use App\Http\Controllers\SlaPolicyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkOrderPriorityController;
use App\Http\Controllers\WorkOrderTypeController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDeactivationController;
use App\Http\Controllers\WorkOrderTakeOverController;
use App\Http\Controllers\WorkOrderPilotController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\PartReservationController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\MaintenancePlanController;
use App\Http\Controllers\OnCallController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\WorkOrderAcknowledgementController;
use App\Http\Controllers\OpenAccessController;


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

    // Outils de la gouvernante (droits vérifiés dans les contrôleurs : responsable du service).
    Route::get('/housekeeping/plan', [HousekeepingSupervisionController::class, 'floorPlan'])->name('housekeeping.floor-plan');
    Route::get('/housekeeping/bilan', [HousekeepingSupervisionController::class, 'monthlyReport'])->name('housekeeping.monthly-report');
    Route::get('/inspections', [RoomInspectionController::class, 'index'])->name('inspections.index');
    Route::post('/inspections', [RoomInspectionController::class, 'store'])->name('inspections.store');
    Route::get('/inspections/{inspection}', [RoomInspectionController::class, 'show'])->name('inspections.show');
    Route::delete('/inspections/{inspection}', [RoomInspectionController::class, 'destroy'])->name('inspections.destroy');
    Route::patch('/inspections/{inspection}/points/{item}', [RoomInspectionController::class, 'updatePoint'])->name('inspections.points.update');
    Route::post('/inspections/{inspection}/points/{item}/photo', [RoomInspectionController::class, 'storePhoto'])->name('inspections.points.photo');
    Route::post('/inspections/{inspection}/terminer', [RoomInspectionController::class, 'complete'])->name('inspections.complete');
});

Route::middleware(['auth', 'role:reception'])->group(function () {
    Route::get('/reception/dashboard', [DashboardController::class, 'reception'])->name('reception.dashboard');
    // Situation du client d'une chambre en panne (relogé, sorti, arrivée prévue…).
    Route::post('/reception/chambres/{room}/situation', [ReceptionController::class, 'updateSituation'])->name('reception.situation');
    // Écran du comptoir : actualisation automatique.
    Route::get('/reception/etat', [ReceptionController::class, 'state'])->name('reception.state');
    // Responsable de réception (droit vérifié dans le contrôleur).
    Route::get('/reception/bilan', [ReceptionController::class, 'monthlyReport'])->name('reception.monthly-report');
});

// === MODULE B : GESTION DES ORDRES DE TRAVAIL (OT) ===
Route::middleware('auth')->group(function () {

    // Pas de destroy : un OT s'annule (motif tracé), il ne se supprime pas.
    Route::resource('work-orders', WorkOrderController::class)->except(['destroy']);

    Route::patch('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])
        ->name('work-orders.status.update');

    Route::post('/work-orders/{workOrder}/comments', [WorkOrderController::class, 'storeComment'])
        ->name('work-orders.comments.store');

    Route::post('/work-orders/{workOrder}/attachments', [WorkOrderController::class, 'storeAttachments'])
        ->name('work-orders.attachments.store');

    // scopeBindings : la pièce jointe doit appartenir à CET OT (sinon 404), pour
    // qu'un droit sur l'OT A ne donne pas accès aux fichiers de l'OT B.
    Route::get('/work-orders/{workOrder}/attachments/{attachment}', [FileDownloadController::class, 'workOrderAttachment'])
        ->scopeBindings()
        ->name('work-orders.attachments.show');

    Route::delete('/work-orders/{workOrder}/attachments/{attachment}', [WorkOrderController::class, 'destroyAttachment'])
        ->scopeBindings()
        ->name('work-orders.attachments.destroy');

    // Signalement rapide (pictogrammes + message vocal) pour le personnel d'étage.
    // Le service demandeur confirme la réparation, ou rouvre l'OT (droits : WorkOrderPolicy::confirmResolution).
    Route::post('/work-orders/{workOrder}/confirmation', [WorkOrderConfirmationController::class, 'confirm'])->name('work-orders.confirm');
    Route::post('/work-orders/{workOrder}/reouverture', [WorkOrderConfirmationController::class, 'reopen'])->name('work-orders.reopen');

    Route::get('/signaler', [QuickReportController::class, 'create'])->name('quick-reports.create');
    Route::post('/signaler', [QuickReportController::class, 'store'])->name('quick-reports.store');
    Route::get('/signaler/{workOrder}/envoye', [QuickReportController::class, 'sent'])->name('quick-reports.sent');
    // Housekeeping : retirer un signalement fait par erreur, ou lui ajouter une précision
    // (droits : WorkOrderPolicy::withdraw / complement).
    Route::post('/signaler/{workOrder}/retirer', [QuickReportController::class, 'withdraw'])->name('quick-reports.withdraw');
    Route::post('/signaler/{workOrder}/completer', [QuickReportController::class, 'complement'])->name('quick-reports.complement');

    // Blocage d'une chambre à la vente : demande (gouvernante / maintenance), décision
    // (réception), remise en vente (gouvernante). Droits dans RoomBlockPolicy.
    Route::get('/chambres-bloquees', [RoomBlockController::class, 'index'])->name('room-blocks.index');
    Route::post('/work-orders/{workOrder}/room-block', [RoomBlockController::class, 'store'])->name('room-blocks.store');
    Route::post('/chambres-bloquees/{roomBlock}/accepter', [RoomBlockController::class, 'approve'])->name('room-blocks.approve');
    Route::post('/chambres-bloquees/{roomBlock}/refuser', [RoomBlockController::class, 'refuse'])->name('room-blocks.refuse');
    Route::post('/chambres-bloquees/{roomBlock}/remettre-en-vente', [RoomBlockController::class, 'release'])->name('room-blocks.release');

});

// === MODULE C : PLANIFICATION & ORDONNANCEMENT ===
// Les services demandeurs (housekeeping, réception) n'y ont pas accès : le flux
// d'évènements exposerait tous les OT planifiés de l'hôtel.
Route::middleware(['auth', 'role:admin,manager,technicien'])->group(function () {
    Route::get('/planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::get('/planning/technicien/{technician}', [PlanningController::class, 'byTechnician'])->name('planning.technician');
    Route::get('/planning/events', [PlanningController::class, 'events'])->name('planning.events');
});

// Planification/replanification et gestion compétences/disponibilités : admin + manager
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::get('/work-orders/{workOrder}/schedule', [PlanningController::class, 'schedule'])->name('work-orders.schedule');
    Route::post('/work-orders/{workOrder}/schedule', [PlanningController::class, 'storeSchedule'])->name('work-orders.schedule.store');
    // « Je m'en charge » : l'admin/manager devient l'intervenant de l'OT.
    Route::post('/work-orders/{workOrder}/take-over', WorkOrderTakeOverController::class)->name('work-orders.take-over');
    // Panneau « Pilotage » : suspendre, relancer, annuler (avec motif).
    Route::post('/work-orders/{workOrder}/suspend', [WorkOrderPilotController::class, 'suspend'])->name('work-orders.suspend');
    Route::post('/work-orders/{workOrder}/resume', [WorkOrderPilotController::class, 'resume'])->name('work-orders.resume');
    Route::post('/work-orders/{workOrder}/cancel', [WorkOrderPilotController::class, 'cancel'])->name('work-orders.cancel');

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
    // Accès ouvert (APP_OPEN_ACCESS) : passer d'un rôle à l'autre. 404 sinon.
    Route::post('/voir-en-tant-que/{user}', OpenAccessController::class)->name('open-access.switch');
    // Pas d'auto-suppression : un compte porte l'historique des OT (signalés,
    // assignés, rapports). Seul un admin le retire, en le désactivant.
});

// === MODULE G : CONTRÔLE QUALITÉ & VALIDATION ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {

    // File « Validation » : OT réparés à contrôler, contrôles en cours, corrections.
    Route::get('/validation', [QualityControlController::class, 'index'])->name('quality-controls.index');

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
    // Chrono oublié : saisie après coup, ou correction d'une de ses sessions.
    Route::post('/work-orders/{workOrder}/sessions', [InterventionSessionController::class, 'store'])
        ->name('work-orders.sessions.store');
    Route::patch('/work-orders/{workOrder}/sessions/{interventionSession}', [InterventionSessionController::class, 'update'])
        ->scopeBindings()
        ->name('work-orders.sessions.update');

    Route::post('/work-orders/{workOrder}/report', [InterventionReportController::class, 'store'])
        ->name('work-orders.report.store');

});

// === MODULE H : NOTIFICATIONS, SLA & ESCALADE (réservé admin) ===
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('sla-policies', SlaPolicyController::class)->except(['show']);
    Route::resource('escalation-rules', EscalationRuleController::class)->except(['show']);
    Route::get('/on-call', [OnCallController::class, 'edit'])->name('on-call.edit');
    Route::put('/on-call', [OnCallController::class, 'update'])->name('on-call.update');
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
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Comptes et accès : mot de passe de l'admin redemandé (15 min de validité), sur
    // les écrans ET sur les envois — une session restée ouverte ne suffit pas à
    // créer un administrateur, changer un e-mail ou réinitialiser un mot de passe.
    Route::middleware('password.confirm')->group(function () {
        Route::resource('users', UserController::class)->except(['index', 'show']);
        Route::post('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password.reset');
        // Départ d'un employé : réaffectation de ses OT puis désactivation.
        Route::get('/users/{user}/deactivate', [UserDeactivationController::class, 'create'])->name('users.deactivate');
        Route::post('/users/{user}/deactivate', [UserDeactivationController::class, 'store'])->name('users.deactivate.store');
    });
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Espace « Paramètres » : accueil des écrans de configuration (comptes, règles, référentiels).
    Route::get('/parametres', SettingsController::class)->name('settings.index');
});

// === MODULE ACHATS & FOURNISSEURS (admin + manager) ===
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
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
    Route::patch('/purchase-orders/{purchaseOrder}/invoices/{invoice}/paid', [InvoiceController::class, 'markAsPaid'])->scopeBindings()->name('purchase-orders.invoices.paid');
    Route::get('/purchase-orders/{purchaseOrder}/invoices/{invoice}/fichier', [FileDownloadController::class, 'invoice'])->scopeBindings()->name('purchase-orders.invoices.file');

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

// === LIEUX & ÉQUIPEMENTS (admin + manager) ===
// Pas de suppression réelle : destroy() met hors service et garde l'historique.
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('rooms', RoomController::class);
    Route::resource('equipment', EquipmentController::class);
});

// Réservation accessible à tous les rôles opérant sur les OT
Route::middleware('auth')->group(function () {
    Route::post('/work-orders/{workOrder}/reservations', [PartReservationController::class, 'store'])->name('work-orders.reservations.store');
    Route::post('/work-orders/{workOrder}/reservations/{reservation}/withdraw', [PartReservationController::class, 'withdraw'])->name('work-orders.reservations.withdraw');
    Route::delete('/work-orders/{workOrder}/reservations/{reservation}', [PartReservationController::class, 'cancel'])->name('work-orders.reservations.cancel');

    // Technicien : prise en charge (« J'ai vu ») et pièce absente du magasin.
    Route::post('/work-orders/{workOrder}/vu', WorkOrderAcknowledgementController::class)->name('work-orders.acknowledge');
    Route::post('/work-orders/{workOrder}/demandes-pieces', [PartRequestController::class, 'store'])->name('work-orders.part-requests.store');
    // Le contrôleur vérifie le rôle (admin, manager).
    Route::post('/demandes-pieces/{partRequest}/traiter', [PartRequestController::class, 'handle'])->name('part-requests.handle');
});

require __DIR__.'/auth.php';