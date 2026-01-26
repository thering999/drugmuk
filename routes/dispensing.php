<?php

/**
 * Dispensing Routes
 * Routes for patient dispensing management
 */

// List all dispensing records
$router->get('/dispensing', 'DispensingController@index');

// Create new dispensing
$router->get('/dispensing/create', 'DispensingController@create');
$router->post('/dispensing/store', 'DispensingController@store');

// View dispensing details
$router->get('/dispensing/show/{id}', 'DispensingController@show');

// Print dispensing receipt
$router->get('/dispensing/print/{id}', 'DispensingController@print');

// AJAX endpoints
$router->get('/dispensing/search-patient', 'DispensingController@searchPatient');
$router->get('/dispensing/patient-history/{hn}', 'DispensingController@patientHistory');

// Statistics and reports
$router->get('/dispensing/statistics', 'DispensingController@statistics');

// Delete (admin only)
$router->post('/dispensing/delete/{id}', 'DispensingController@delete');

// Legacy route (redirect to create)
$router->get('/dispensing/manual', 'DispensingController@manual');
