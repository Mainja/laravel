<?php
use App\Http\Controllers\academicSetupController;
use App\Http\Controllers\adminsController;
use App\Http\Controllers\Api\settingsController;
use App\Http\Controllers\applicationController;
use App\Http\Controllers\assignmentsController;
use App\Http\Controllers\countryStateController;
use App\Http\Controllers\installmentsController;
use App\Http\Controllers\inventoryController;
use App\Http\Controllers\libraryController;
use App\Http\Controllers\notesController;
use App\Http\Controllers\notificationsController;
use App\Http\Controllers\paymentsController;
use App\Http\Controllers\penaltyController;
use App\Http\Controllers\programFeesController;
use App\Http\Controllers\registrationController;
use App\Http\Controllers\registrationOpeningsController;
use App\Http\Controllers\resultsController;
use App\Http\Controllers\studentsController;
use App\Http\Controllers\timetablesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// controllers
use App\Http\Controllers\Auth\AdminAuthController;
// controllers end


Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/send_reset_link', [adminsController::class, 'sendResetLink']);
Route::post('/admin/reset_password', [adminsController::class, 'resetPasswordAdmin']);

Route::middleware(['auth:sanctum', 'type.admin'])->get('/admin', function (Request $request) {
    // return $request->user();
    $roles = DB::table('admin_roles')
    ->join('roles', 'admin_roles.role_id', '=', 'roles.id')
    ->select('roles.role')
    ->where('admin_roles.admin_id', $request->user()->id)
    ->pluck('role');

    return response()->json([
        'status' => 200,
        'data' => $request->user(),
        'roles' => $roles,
    ], 200);
});

Route::middleware(['auth:sanctum', 'type.admin'])->group(function () {
    Route::post('/admins', [adminsController::class, 'store']);
    Route::get('/admins', [adminsController::class, 'index']);

    Route::post('/student-registration-setup', [academicSetupController::class,'studentRegistration']);
    Route::post('/penalty-clearnace-procedure', [academicSetupController::class, 'penaltyClearanceSteps']);
    Route::get('/penalty-clearance-steps', [academicSetupController::class, 'penaltyClearanceIndex']);
    
    Route::post('/students', [studentsController::class,'store']);
    Route::post('/students_csv', [studentsController::class,'storeCSV']);
    Route::put('/students', [studentsController::class,'update']);
    Route::get('/students', [studentsController::class,'index']);
    Route::get('/students/{student_id}', [studentsController::class,'getStudentById']);
    Route::get('/students/program/{program_id}', [studentsController::class,'getStudentByProgram']);
    Route::get('/students/program_intake/{program_id}/{intake_id}', [studentsController::class,'getStudentByProgramAndIntake']);
    Route::get('/students/intake/{intake_id}', [studentsController::class,'getStudentByIntake']);
    Route::get('/students/get_last_position/{intake_id}', [studentsController::class,'getLastPosition']);

    Route::get('/notes', [notesController::class,'index']);
    Route::get('/notes/category/{category}', [notesController::class,'getNotesByCategory']);
    Route::post('/notes', [notesController::class,'store']);
    Route::post('/notes/{id}/delete', [notesController::class,'delete']);
    Route::put('/notes', [notesController::class,'update']);
    Route::post('/notes/delete', [notesController::class,'delete']);

    Route::get('/assignments', [assignmentsController::class,'index']);
    Route::get('/assignments/{assignment_id}', [assignmentsController::class,'getAssignmentDetails']);
    Route::post('/assignments', [assignmentsController::class,'store']);
    Route::put('/assignments', [assignmentsController::class,'update']);
    Route::post('/assignments/delete', [assignmentsController::class,'delete']);

    Route::get('/admin/notifications', [notificationsController::class,'index']);
    Route::post('/admin/notifications', [notificationsController::class,'store']);
    Route::put('/admin/notifications', [notificationsController::class,'update']);
    Route::post('/admin/notifications/{id}/delete', [notificationsController::class,'delete']);

    Route::get('/timetables', [timetablesController::class,'index']);
    Route::post('/timetables', [timetablesController::class,'store']);
    Route::put('/timetables', [timetablesController::class,'update']);
    Route::post('/timetables/{id}/delete', [timetablesController::class,'delete']);

    Route::post('/program_fees', [programFeesController::class,'store']);
    Route::put('/program_fees', [programFeesController::class,'update']);
    Route::get('/program-intake-list-fees', [programFeesController::class,'index']);
    Route::get('/program-fees/fee-details/{program_fee_id}', [programFeesController::class,'feeDetails']);

    Route::get('/results', [resultsController::class,'index']);
    Route::get('/results/{id}/details', [resultsController::class,'getResultDetails']);
    Route::post('/results', [resultsController::class,'store']);
    Route::post('/exam_result_details', [resultsController::class, 'getExamResultDetails']);

    Route::post('/tuition_payments', [paymentsController::class,'recordPayment']);
    Route::put('/tuition_payments', [paymentsController::class,'updateBalance']);
    Route::put('/tuition_payments/payment_records', [paymentsController::class,'updateAmountPaid']);
    Route::get('/tuition_payments', [paymentsController::class,'paymentHistory']);
    Route::get('/tuition_payments/{payment_id}/{student_id}/payment_records', [paymentsController::class,'paymentRecords']);

    Route::get('/registration/office_requests', [registrationController::class,'officeRequests']);

    Route::get('/get_office_requirements', [academicSetupController::class,'getOfficeRequirements']);
    Route::get('/get_office_requirements_by_step/{step_id}/{request_id}', [academicSetupController::class,'getOfficeRequirementsByStep']);

    Route::get('/inventory_items', [inventoryController::class,'index']);
    Route::post('/inventory_items', [inventoryController::class,'store']);
    Route::post('/office_inventory', [inventoryController::class,'storeOfficeInventory']);
    Route::put('/office_inventory', [inventoryController::class,'updateOfficeInventory']);
    Route::put('/inventory_items', [inventoryController::class,'update']);
    Route::post('/inventory_items/{id}/delete', [inventoryController::class,'destroy']);

    Route::get('/offices/office_inventory/{office_id}', [inventoryController::class,'getOfficeInventory']);

    Route::get('/inventory_items/student_submitted', [inventoryController::class,'studentSubmitted']);
    Route::get('/inventory_items/school_acquired', [inventoryController::class,'schoolAcquired']);
    Route::get('/inventory_items/summary/{item_id}', [inventoryController::class,'itemSummary']);

    Route::get('/inventory_transfers', [inventoryController::class,'inventoryTransferIndex']);
    Route::post('/inventory_transfers', [inventoryController::class,'inventoryTransferStore']);
    Route::put('/inventory_transfers', [inventoryController::class,'inventoryTransferUpdate']);

    Route::post('/exam_registration', [registrationController::class, 'registerExam']);
    Route::get('/exam_registration_index', [registrationController::class, 'examIndex']);
    Route::get('/student_docket/{exam_student_id}/registered_courses', [registrationController::class,'examRegisteredCourses']);
    Route::get('/exam_candidates/{exam_id}', [registrationController::class, 'getExamCandidates']);

    Route::get('/installments/{payment_id}/get_by_registration', [installmentsController::class, 'getByRegistration']);

    Route::get('/installments_pending_today', [installmentsController::class,'installmentsPendingToday']);
    Route::get('/installments_past_payment_date', [installmentsController::class,'installmentsPastPaymentDate']);

    Route::get('/count_installments_pending_today', [installmentsController::class,'installmentsPendingTodayCount']);
    Route::get('/count_installments_past_payment_date', [installmentsController::class,'installmentsPastPaymentDateCount']);

    Route::get('/registration_openings', [registrationOpeningsController::class,'index']);
    Route::post('/registration_openings', [registrationOpeningsController::class,'store']);
    Route::put('/registration_openings', [registrationOpeningsController::class,'update']);

    Route::get('/late_registration', [registrationController::class, 'lateRegistration']);
    Route::get('/count_late_registration', [registrationController::class, 'lateRegistrationCount']);

    Route::post('/confirm_registration_stage', [registrationController::class, 'confirmRegistrationStage']);
    Route::post('/confirm_registration_stage_with_items', [registrationController::class, 'confirmRegistrationStageWithItems']);

    Route::get('/registration_progress/{payment_id}', [registrationController::class, 'getRegistrationProgress']);

    Route::get('/penalty-charges', [registrationController::class, 'penaltyCharges']);
    Route::get('/get_first_step_office', [registrationController::class, 'firstStepOffice']);

    Route::get('/registered-students', [studentsController::class, 'registeredStudents']);

    Route::put('/admin/update_contact_details', [adminsController::class, 'updateContactDetails']);

    Route::put('/admin/update_password', [adminsController::class, 'updatePassword']);

    Route::get('/admin/digital_library', [libraryController::class, 'index']);
    Route::post('/admin/digital_library', [libraryController::class, 'store']);
    Route::put('/admin/digital_library', [libraryController::class, 'update']);
    Route::post('/admin/digital_library/delete/{id}', [libraryController::class, 'delete']);

    Route::post('/penalty_waiver_start', [penaltyController::class, 'startWaiver']);
    Route::get('/get_penalty_clearance_requests', [penaltyController::class, 'getClearanceRequests']);
    Route::get('/penalty_clearance_details/{clearance_id}', [penaltyController::class, 'penaltyClearanceDetails']);
    Route::post('/penalty_clearance_feedback', [penaltyController::class, 'penaltyClearanceFeedback']);

    Route::get('/admin/get_admin_courses', [adminsController::class, 'getAdminCourses']);
    Route::post('/admin/choose_course/{course_id}', [adminsController::class, 'chooseCourse']);
    Route::post('/admin/remove_course/{id}', [adminsController::class, 'removeCourse']);

    Route::get('/admin/get_applicants', [applicationController::class, 'getApplicants']);
    Route::get('/admin/count_applicants', [applicationController::class, 'countApplicants']);

    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
});