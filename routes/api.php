<?php

use App\Http\Controllers\applicationController;
use App\Http\Controllers\countryStateController;
use App\Http\Controllers\intakesController;
use App\Http\Controllers\meetingController;
use App\Http\Controllers\messageController;
use App\Http\Controllers\officesController;
use App\Http\Controllers\trainingLevelsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\programsController;
use App\Http\Controllers\coursesController;

// route for aplications
Route::post('/apply', [applicationController::class, 'Apply']);

Route::get('/programs', [programsController::class,'index'])->name('programs');
Route::post('/programs', [programsController::class,'store']);
Route::put('/programs', [programsController::class,'update']);
Route::get('/programs/program_type/{program_type}', [programsController::class, 'getProgramsByType']);

Route::get('/courses', [coursesController::class,'index'])->name('courses');
Route::post('/courses', [coursesController::class,'store']);
Route::put('/courses', [coursesController::class,'update']);
Route::get('/courses/program_id/{program_id}', [coursesController::class,'getCoursesByProgram']);

Route::get('/intakes', [intakesController::class,'index']);
Route::get('/intakes/{intake_id}', [intakesController::class,'getIntakeById']);
Route::post('/intakes', [intakesController::class,'store']);
Route::put('/intakes', [intakesController::class,'update']);
Route::get('/intakes/intake_by_program_type/{program_type}', [intakesController::class, 'intakeByProgramType']);
Route::get('/intakes/open_intake_by_program_type/{program_type}', [intakesController::class, 'OpenintakeByProgramType']);

Route::get('/offices', [officesController::class,'index']);
Route::post('/offices', [officesController::class,'store']);
Route::put('/offices', [officesController::class,'update']);
Route::post('/offices/{id}/delete', [officesController::class,'destroy']);

Route::get('/semesters', [trainingLevelsController::class,'getSemesters']);
Route::get('/years', [trainingLevelsController::class,'getYears']);

Route::post('/send_mail_message', [messageController::class, 'sendMessage']);
Route::post('/send_bulk_mail', [messageController::class, 'sendBukMail']);

Route::post('/meeting/create', [meetingController::class, 'createMeeting']);
Route::get('/meeting/all_meetings', [meetingController::class, 'getAllMeetings']);
Route::post('/meeting/get_meeting/{meeting_id}', [meetingController::class, 'getMeeting']);
Route::post('/meeting/end_meeting/{meeting_id}', [meetingController::class, 'endMeeting']);
Route::post('/meeting/delete_meeting/{meeting_id}', [meetingController::class, 'deleteMeeting']);

Route::get('/countries', [countryStateController::class,'getAllCountries']);
Route::get('/states/{country_id}', [countryStateController::class,'getCountryStates']);

Route::get('/roles', function() {
    return DB::table('roles')->get();
});

require __DIR__.'/admin_api.php';
require __DIR__.'/student_api.php';
