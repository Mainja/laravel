<?php
use App\Http\Controllers\assignmentsController;
use App\Http\Controllers\installmentsController;
use App\Http\Controllers\inventoryController;
use App\Http\Controllers\notesController;
use App\Http\Controllers\notificationsController;
use App\Http\Controllers\libraryController;
use App\Http\Controllers\paymentsController;
use App\Http\Controllers\registrationController;
use App\Http\Controllers\resultsController;
use App\Http\Controllers\studentsController;
use App\Http\Controllers\timetablesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// controllers
use App\Http\Controllers\Auth\StudentAuthController;
// controllers end

Route::post('/student/login', [StudentAuthController::class, 'login']);
Route::post('/student/send_reset_link', [studentsController::class, 'sendResetLink']);
Route::post('/student/reset_password', [studentsController::class, 'resetPasswordStudent']);

Route::middleware(['auth:sanctum', 'type.student'])->get('/student', function (Request $request) {
    if ($request->user()) {
        return $request->user();
    } else {
        return 'logged out';
    }
});

Route::middleware(['auth:sanctum', 'type.student'])->group(function () {
    Route::post('/student/logout', [StudentAuthController::class, 'logout']);
    Route::get('/dashboard_notifications', [notificationsController::class,'dashboardNotifications']);
    Route::get('/dashboard_data', [studentsController::class, 'dashboardData']);

    Route::get('/student/timetables', [timetablesController::class,'studentIndex']);
    Route::get('/student/assignments', [assignmentsController::class,'studentIndex']);
    Route::get('/student/notes', [notesController::class,'studentIndex']);
    Route::get('/student/notes/category/{category}', [notesController::class,'getNotesByCategory']);

    Route::post('/student/assignments', [assignmentsController::class,'studentSubmit']);

    Route::post('/student/exam_registration', [studentsController::class, 'registerExam']);

    Route::get('/student/tuition_payments', [paymentsController::class,'getStudentPayments']);
    Route::get('/student/tuition_payments/{payment_id}/{student_id}/payment_records', [paymentsController::class,'paymentRecords']);

    Route::get('/student/exam_registration_history', [studentsController::class, 'examRegistrationHistory']);

    Route::get('/student/student_docket/{exam_student_id}/registered_courses', [registrationController::class,'examRegisteredCourses']);

    Route::get('/student/results', [resultsController::class,'getStudentResults']);
    Route::get('/student/results/{id}/details', [resultsController::class,'getStudentResultDetails']);

    Route::get('/student/continuous-assessments', [resultsController::class,'getStudentCAByStudent']);
    Route::get('/student/continuous-assessments/{id}/details', [resultsController::class,'getStudentCADetails']);

    Route::get('/student/resit-results', [resultsController::class,'getStudentResitResults']);
    Route::get('/student/resit-results/{id}/details', [resultsController::class,'getStudentResitResultsDetails']);

    Route::post('/student/registration', [registrationController::class, 'startRegistration']);

    Route::get('/digital_library', [libraryController::class, 'getBooksByProgram']);
    Route::get('/notifications', [notificationsController::class,'index']);

    Route::put('/student/update_contact_details', [studentsController::class, 'updateContactDetails']);

    Route::put('/student/update_password', [studentsController::class, 'updatePassword']);

    Route::get('/student/installments/{payment_id}/get_by_registration', [installmentsController::class, 'getByRegistration']);
    Route::get('/student/registration_progress/{payment_id}', [registrationController::class, 'getRegistrationProgress']);

    Route::get('/student/my_inventory', [inventoryController::class, 'studentIndividualInventory']);
    
});