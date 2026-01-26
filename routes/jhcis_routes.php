<?php

/**
 * JHCIS Integration Routes
 * 
 * Add these routes to your main routes/web.php file
 * 
 * Created: 2026-01-21
 * Version: 3.2.0
 */

// ===================================
// Drug Allergy Routes
// ===================================

// Check single drug allergy
$router->post('/api/allergy/check', 'DrugAllergyController@check');

// Check multiple drugs
$router->post('/api/allergy/check-multiple', 'DrugAllergyController@checkMultiple');

// Get patient allergies
$router->get('/api/allergy/patient/{hn}', 'DrugAllergyController@getPatientAllergies');

// Sync allergies from JHCIS
$router->post('/api/allergy/sync', 'DrugAllergyController@sync');

// Get allergy statistics
$router->get('/api/allergy/statistics', 'DrugAllergyController@statistics');

// Allergy management page
$router->get('/allergy/manage', 'DrugAllergyController@manage');

// Allergy statistics page
$router->get('/allergy/stats', 'DrugAllergyController@stats');

// ===================================
// Patient Profile Routes
// ===================================

// Search patients
$router->get('/api/patient/search', 'PatientController@search');

// Get patient profile
$router->get('/api/patient/{hn}', 'PatientController@getProfile');

// Get chronic diseases
$router->get('/api/patient/{hn}/chronic', 'PatientController@getChronicDiseases');

// Get recent visits
$router->get('/api/patient/{hn}/visits', 'PatientController@getRecentVisits');

// Get current medications
$router->get('/api/patient/{hn}/medications', 'PatientController@getCurrentMedications');

// Get vital signs
$router->get('/api/patient/{hn}/vitals', 'PatientController@getVitalSigns');

// Get vaccines
$router->get('/api/patient/{hn}/vaccines', 'PatientController@getVaccines');

// Get screening history
$router->get('/api/patient/{hn}/screening', 'PatientController@getScreening');

// Sync patient profile
$router->post('/api/patient/{hn}/sync', 'PatientController@syncProfile');

// Patient search page
$router->get('/patient/search', 'PatientController@searchPage');

// Patient dashboard
$router->get('/patient/{hn}', 'PatientController@dashboard');

// ===================================
// Chronic Disease Management Routes
// (To be added when controller is created)
// ===================================

/*
$router->get('/api/chronic/patients', 'ChronicDiseaseController@getPatients');
$router->get('/api/chronic/{hn}/refills', 'ChronicDiseaseController@getRefillSchedule');
$router->get('/api/chronic/due-refills', 'ChronicDiseaseController@getDueRefills');
$router->get('/api/chronic/overdue', 'ChronicDiseaseController@getOverdue');
$router->post('/api/chronic/send-reminder', 'ChronicDiseaseController@sendReminder');
$router->get('/api/chronic/statistics', 'ChronicDiseaseController@getStatistics');
*/
