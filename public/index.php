<?php
/**
 * Drugmuk - Pharmaceutical Inventory Management System
 * Main Entry Point with Security Integration
 */

// ========================================
// AUTOLOADER (Must load first)
// ========================================

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name); $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value; $_SERVER[$name] = $value;
        }
    }
}

// ========================================
// SECURITY INITIALIZATION
// ========================================

use App\Core\ErrorHandler;
use App\Core\SessionSecurity;
use App\Core\Router;

ErrorHandler::register();
SessionSecurity::start();

// ========================================
// SECURITY HEADERS
// ========================================

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(self), payment=(), usb=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net cdnjs.cloudflare.com unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net cdnjs.cloudflare.com; font-src 'self' fonts.gstatic.com cdn.jsdelivr.net cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self';");

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

if (isset($_SESSION['user_id'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// ========================================
// ROUTING
// ========================================

$router = new Router();

// AUTH ROUTES
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@authenticate');
$router->post('/authenticate', 'AuthController@authenticate'); // Alias for old forms
$router->get('/authenticate', 'AuthController@authenticate'); // Redirects to login
$router->get('/logout', 'AuthController@logout');

// HOME / DASHBOARD
$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// INVENTORY ROUTES
$router->get('/inventory', 'InventoryController@index');
$router->get('/inventory/expiring', 'InventoryController@expiring');
$router->get('/inventory/low-stock', 'InventoryController@lowStock');
$router->post('/inventory/adjust/{id}', 'InventoryController@adjust');

// DISPENSING ROUTES
$router->get('/dispensing', 'DispensingController@index');
$router->get('/dispensing/create', 'DispensingController@create');
$router->post('/dispensing/store', 'DispensingController@store');
$router->get('/dispensing/show/{id}', 'DispensingController@show');
$router->get('/dispensing/print/{id}', 'DispensingController@print');
$router->get('/dispensing/statistics', 'DispensingController@statistics');
$router->post('/dispensing/delete/{id}', 'DispensingController@delete');
$router->get('/api/dispensing/search-patient', 'DispensingController@searchPatient');
$router->get('/api/dispensing/patient-history/{hn}', 'DispensingController@patientHistory');
$router->get('/dispensing/history/{hn}', 'DispensingController@history');

// SUBWAREHOUSE ROUTES
$router->get('/subwarehouse', 'SubWarehouseController@index');
$router->get('/subwarehouse/create', 'SubWarehouseController@create');
$router->post('/subwarehouse/store', 'SubWarehouseController@store');
$router->get('/subwarehouse/dashboard/{id}', 'SubWarehouseController@dashboard');
$router->get('/subwarehouse/requisition/{id}', 'SubWarehouseController@requisition');
$router->get('/subwarehouse/dispense/{id}', 'SubWarehouseController@dispense');
$router->get('/subwarehouse/configure-formula/{id}', 'SubWarehouseController@configureFormula');

// API Subwarehouse
$router->post('/api/subwarehouse/{code}/dispense', 'SubWarehouseController@apiDispense');
$router->post('/api/subwarehouse/{code}/requisitions/auto', 'SubWarehouseController@apiAutoRequisition');
$router->post('/api/subwarehouse/formula/save', 'SubWarehouseController@apiSaveFormula');
$router->get('/api/subwarehouse/{code}/inventory', 'SubWarehouseController@apiGetInventory');
$router->post('/api/subwarehouse/{code}/requisition', 'SubWarehouseController@apiCreateRequisition');
$router->post('/api/subwarehouse/{code}/auto-requisition', 'SubWarehouseController@apiAutoRequisition');

// ORDER ROUTES
$router->get('/orders', 'OrderController@index');
$router->get('/orders/create', 'OrderController@create');
$router->get('/orders/what-to-buy', 'OrderController@whatToBuy');
$router->get('/orders/show/{id}', 'OrderController@show');
$router->post('/orders/store', 'OrderController@store');
$router->post('/orders/approve/{id}', 'OrderController@updateStatus');
$router->get('/orders/receive/{id}', 'OrderController@receive');
$router->post('/orders/receive', 'OrderController@storeReceive');
$router->get('/orders/receive/print/{id}', 'OrderController@printReceive');

// PURCHASING PLAN ROUTES
$router->get('/purchasing', 'PurchasingPlanController@index');
$router->get('/purchasing/calculate', 'PurchasingPlanController@calculate');
$router->post('/purchasing/calculate', 'PurchasingPlanController@processCalculation');
$router->get('/purchasing/analysis', 'PurchasingPlanController@analysis');
$router->get('/purchasing/import', 'PurchasingPlanController@import');
$router->post('/purchasing/import', 'PurchasingPlanController@processImport');
$router->get('/purchasing/export', 'PurchasingPlanController@export');
$router->get('/purchasing/adjust', 'PurchasingPlanController@adjust');
$router->post('/purchasing/adjust', 'PurchasingPlanController@saveAdjustment');
$router->get('/purchasing-plan', 'PurchasingPlanController@index'); // Alias

// CONTRACTS ROUTES
$router->get('/contracts', 'ContractsController@index');
$router->get('/contracts/create', 'ContractsController@create');
$router->post('/contracts/store', 'ContractsController@store');
$router->get('/contracts/expiring', 'ContractsController@expiring');
$router->get('/contracts/show/{id}', 'ContractsController@show');
$router->get('/contracts/edit/{id}', 'ContractsController@edit');
$router->post('/contracts/update/{id}', 'ContractsController@update');
$router->post('/contracts/delete/{id}', 'ContractsController@delete');

// WAREHOUSE MANAGEMENT ROUTES
$router->get('/warehouse', 'WarehouseController@index');
$router->get('/warehouse/receive', 'WarehouseController@receive');
$router->post('/warehouse/store-receive', 'WarehouseController@storeReceive');
$router->get('/warehouse/approve-disbursement', 'WarehouseController@approveDisbursement');
$router->post('/warehouse/process-disbursement', 'WarehouseController@processDisbursement');
$router->get('/warehouse/stock-card', 'WarehouseController@stockCard');
$router->get('/warehouse/stock-card/{id}', 'WarehouseController@stockCard');
$router->get('/warehouse/adjust', 'WarehouseController@adjust');
$router->post('/warehouse/store-adjustment', 'WarehouseController@storeAdjustment');
$router->get('/warehouse/transfer', 'WarehouseController@transfer');
$router->post('/warehouse/process-transfer', 'WarehouseController@processTransfer');
$router->get('/warehouse/create', 'WarehouseController@create');
$router->post('/warehouse/store', 'WarehouseController@store');
$router->get('/warehouse/edit/{id}', 'WarehouseController@edit');

// DMSIC ROUTES
$router->get('/dmsic', 'DMSICController@index');
$router->post('/dmsic/send', 'DMSICController@processExport');
$router->get('/dmsic/history', 'DMSICController@history');
$router->get('/dmsic/config', 'DMSICController@config');
$router->post('/dmsic/config', 'DMSICController@config');
$router->get('/dmsic/download/{id}', 'DMSICController@download');
$router->post('/dmsic/api/send/{id}', 'DMSICController@send');

// JHCIS DRUG LIST ROUTES
$router->get('/jhcis-drugs', 'JHCISDrugListController@index');
$router->get('/jhcis-drugs/export', 'JHCISDrugListController@export');

// REPORT ROUTES
$router->get('/reports', 'ReportController@index');
$router->get('/reports/builder', 'ReportController@builder');
$router->post('/reports/create', 'ReportController@create');
$router->get('/reports/generate/{id}', 'ReportController@generate');
$router->get('/reports/predefined/{id}', 'ReportController@predefined');
$router->get('/reports/view/{id}', 'ReportController@generate');
$router->get('/reports/export/{type}', 'ReportController@export');
$router->post('/reports/export', 'ReportController@export');
$router->post('/reports/delete/{id}', 'ReportController@delete');

// DATA QUALITY & MONITORING (ADVANCED)
$router->get('/scan', 'ScanningController@index');
$router->post('/scan/lookup', 'ScanningController@lookup');
$router->post('/api/scan/batch', 'ScanningController@processBatch');

$router->get('/data-cleansing', 'DataCleansingController@index');
$router->get('/admin/data-cleansing', 'DataCleansingController@index'); // Alias
$router->post('/api/data-cleansing/run-full-check', 'DataCleansingController@runFullCheck');
$router->get('/admin/data-cleansing/duplicates', 'DataCleansingController@duplicates');
$router->get('/admin/data-cleansing/orphaned', 'DataCleansingController@orphanedRecords');
$router->post('/api/data-cleansing/detect-duplicates', 'DataCleansingController@detectDuplicates');
$router->post('/api/data-cleansing/detect-orphaned', 'DataCleansingController@detectOrphaned');
$router->post('/api/data-cleansing/merge-duplicates', 'DataCleansingController@mergeDuplicates');
$router->post('/api/data-cleansing/mark-false-positive', 'DataCleansingController@markFalsePositive');
$router->post('/api/data-cleansing/delete-orphaned', 'DataCleansingController@deleteOrphaned');

$router->get('/realtime-sync', 'RealtimeSyncController@index');
$router->get('/realtime-sync/stream', 'RealtimeSyncController@stream');
$router->get('/api/realtime-sync/settings', 'RealtimeSyncController@getSettings');
$router->post('/realtime-sync/toggle', 'RealtimeSyncController@toggle');
$router->post('/api/realtime-sync/log', 'RealtimeSyncController@logChange');

$router->get('/updates', 'UpdateController@index');
$router->post('/updates/install', 'UpdateController@installUpdate');
$router->post('/updates/check', 'UpdateController@checkUpdate');
$router->post('/api/updates/check', 'UpdateController@checkUpdate');

// SYSTEM TOOLS
$router->get('/import-history', 'ImportHistoryController@index');
$router->get('/import-history/view/{id}', 'ImportHistoryController@view');
$router->get('/sample-data', 'SampleDataController@index');
$router->post('/api/insert-sample-data', 'SampleDataController@insert');
$router->get('/mock-data', 'MockDataController@index');
$router->post('/mock-data/generate', 'MockDataController@generate');

// SYSTEM SETTINGS
$router->get('/settings/database', 'SystemSettingsController@index');

// HOSPITAL MANAGEMENT (Multi-JHCIS)
$router->get('/admin/jhcis/hospitals', 'MultiJHCISController@index');
$router->post('/admin/jhcis/hospitals/add', 'MultiJHCISController@addHospital');
$router->post('/admin/jhcis/hospitals/update', 'MultiJHCISController@updateHospital');
$router->post('/admin/jhcis/hospitals/delete', 'MultiJHCISController@deleteHospital');
$router->post('/admin/jhcis/hospitals/sync-all', 'MultiJHCISController@syncAll');

// ENHANCED JHCIS (VIEWS)
$router->get('/admin/jhcis/dashboard', 'JHCISEnhancedController@dashboard');
$router->get('/admin/jhcis/auto-mapping', 'JHCISEnhancedController@autoMappingPage');
$router->get('/admin/jhcis/mapping', 'JHCISEnhancedController@autoMappingPage');
$router->get('/admin/jhcis/reconciliation', 'JHCISEnhancedController@reconciliationPage');
$router->get('/admin/jhcis/sync-settings', 'JHCISEnhancedController@syncSettingsPage');
$router->get('/admin/jhcis/reports', 'JHCISEnhancedController@reportsPage');

// JHCIS BASIC (DRUGS & IMPORT)
$router->get('/jhcis-drugs', 'JHCISDrugListController@index');
$router->get('/jhcis-drugs/list', 'JHCISDrugListController@list');
$router->post('/jhcis-drugs/import', 'JHCISDrugListController@import');
$router->get('/jhcis-import', 'JHCISDataImportController@index');
$router->post('/jhcis-import/process', 'JHCISDataImportController@process');
$router->get('/jhcis-import/test-connection', 'JHCISDataImportController@testConnection');
$router->post('/jhcis-import/import-drugs', 'JHCISDataImportController@importDrugs');
$router->post('/jhcis-import/import-dispensing', 'JHCISDataImportController@importDispensing');

// ENHANCED JHCIS API
$router->get('/api/jhcis/test-connection/{id}', 'JHCISEnhancedController@testConnectionById');
$router->post('/api/jhcis/connection/test', 'JHCISEnhancedController@testConnection');
$router->post('/api/jhcis/sync-now/{id}', 'JHCISEnhancedController@syncNow');
$router->post('/api/jhcis/reconciliation/run', 'JHCISEnhancedController@runReconciliation');
$router->post('/api/jhcis/reconciliation/adjust', 'JHCISEnhancedController@applyAdjustments');
$router->post('/api/jhcis/auto-mapping/suggest', 'JHCISEnhancedController@suggestMappings');
$router->post('/api/jhcis/auto-mapping/apply', 'JHCISEnhancedController@applyMappings');
$router->post('/api/jhcis/reports/generate', 'JHCISEnhancedController@generateReport');
$router->post('/api/jhcis/sync/settings', 'JHCISEnhancedController@updateSyncSettings');
$router->get('/admin/jhcis/export', 'JHCISEnhancedController@exportData');

// DMSIC INTEGRATION
$router->get('/dmsic', 'DMSICController@index');
$router->get('/dmsic/config', 'DMSICController@config');
$router->post('/dmsic/config', 'DMSICController@config');
$router->post('/dmsic/send', 'DMSICController@processExport');
$router->post('/dmsic/api/send/{id}', 'DMSICController@send');
$router->get('/dmsic/download/{id}', 'DMSICController@download');

// JHCIS PATIENT INTELLIGENCE ROUTES
// 1. Drug Allergy
$router->post('/api/allergy/check', 'DrugAllergyController@check');
$router->post('/api/allergy/check-multiple', 'DrugAllergyController@checkMultiple');
$router->get('/api/allergy/patient/{hn}', 'DrugAllergyController@getPatientAllergies');
$router->post('/api/allergy/sync', 'DrugAllergyController@sync');
$router->get('/api/allergy/statistics', 'DrugAllergyController@statistics');
$router->get('/allergy/manage', 'DrugAllergyController@manage');
$router->get('/allergy/stats', 'DrugAllergyController@stats');

// 2. Patient Profile
$router->get('/api/patient/search', 'PatientController@search');
$router->get('/api/patient/{hn}', 'PatientController@getProfile');
$router->get('/api/patient/{hn}/chronic', 'PatientController@getChronicDiseases');
$router->get('/api/patient/{hn}/visits', 'PatientController@getRecentVisits');
$router->get('/api/patient/{hn}/medications', 'PatientController@getCurrentMedications');
$router->get('/api/patient/{hn}/vitals', 'PatientController@getVitalSigns');
$router->get('/api/patient/{hn}/vaccines', 'PatientController@getVaccines');
$router->get('/api/patient/{hn}/screening', 'PatientController@getScreening');
$router->post('/api/patient/{hn}/sync', 'PatientController@syncProfile');
$router->get('/patient/search', 'PatientController@searchPage');
$router->get('/patient/{hn}', 'PatientController@dashboard');

// 3. Chronic Disease Management
$router->get('/api/chronic/patients', 'ChronicDiseaseController@getPatients');
$router->get('/api/chronic/{hn}/refills', 'ChronicDiseaseController@getRefillSchedule');
$router->get('/api/chronic/due-refills', 'ChronicDiseaseController@getDueRefills');
$router->get('/api/chronic/overdue', 'ChronicDiseaseController@getOverdue');
$router->post('/api/chronic/send-reminder', 'ChronicDiseaseController@sendReminder');
$router->get('/api/chronic/statistics', 'ChronicDiseaseController@getStatistics');
$router->get('/chronic/dashboard', 'ChronicDiseaseController@dashboard');

// PHASE 2: PREDICTIVE ANALYTICS ROUTES
$router->get('/admin/intelligence', 'IntelligenceController@dashboard');
$router->get('/api/intelligence/forecast/{id}', 'IntelligenceController@getForecast');
$router->get('/api/intelligence/high-risk-patients', 'IntelligenceController@getHighRiskPatients');
$router->post('/api/intelligence/recalculate-risk', 'IntelligenceController@recalculateRisk');
$router->get('/api/intelligence/dashboard-stats', 'IntelligenceController@getDashboardStats');
$router->get('/api/intelligence/rdu-analysis', 'IntelligenceController@getRDUAnalysis');
$router->get('/api/intelligence/high-cost-analysis', 'IntelligenceController@getHighCostAnalysis');
$router->get('/api/intelligence/polypharmacy', 'IntelligenceController@getPolypharmacy');
$router->post('/api/intelligence/auto-adjust-inventory', 'IntelligenceController@autoAdjustInventory');

// PHASE 3: PATIENT ENGAGEMENT ROUTES
$router->post('/api/engagement/send-reminder', 'EngagementController@sendReminder');
$router->post('/api/engagement/generate-instruction', 'EngagementController@generateInstruction');
$router->post('/api/engagement/save-instruction', 'EngagementController@saveInstruction');
$router->get('/api/engagement/adherence/{hn}', 'EngagementController@getAdherence');
$router->post('/api/engagement/record-adherence', 'EngagementController@recordAdherence');
$router->get('/patient/v/{token}', 'EngagementController@patientPortal'); // Patient-facing link

// PHASE 4: CLINICAL SAFETY ROUTES
$router->post('/api/safety/check-ddi', 'SafetyController@checkDDI');
$router->get('/api/safety/check-patient', 'SafetyController@checkPatient');
$router->get('/api/safety/labs/{hn}', 'SafetyController@getLabs');

// PHASE 4: LABEL ROUTES
$router->get('/label/print/{dispense_id}/{item_id}', 'LabelController@printLabel');

// RUN ROUTER
$router->run();
