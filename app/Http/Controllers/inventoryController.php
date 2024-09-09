<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class inventoryController extends Controller
{
    public function index() {
        try {
            $items = DB::table("inventory_items")->get();

            return response()->json([
                'status' => 200,
                'data' => $items,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'item_name' => 'required|unique:inventory_items,item_name', 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('inventory_items')->insert([
                'item_name' => $request->item_name,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Item has been created successfully',
            ], 200);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function updateOfficeInventory(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|numeric|min:1', 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::beginTransaction();

            $get_record = DB::table('student_submitted_inventory')
            ->where('id', $request->id)
            ->first();

            if (($get_record->submitted + $request->quantity) > $get_record->expected_quantity) {
                return $this->responseMessage(403, "The expected quantity is ".$get_record->expected_quantity." you cannot add on ".$request->quantity." more!");
            } else {
                DB::table('student_submitted_inventory')
                ->where('id', $request->id)
                ->increment('submitted', $request->quantity);

                DB::table('student_submitted_inventory')
                ->where('id', $request->id)
                ->decrement('balance', $request->quantity);

                DB::table('inventory')
                ->where([
                    'item_id' => $get_record->item_id,
                    'office_id' => $get_record->office_id,
                ])->increment('quantity', $request->quantity);

                DB::commit();

                return response()->json([
                'status'=> 200,
                'message'=> 'Inventory has been updated successfully',
            ], 200);
            }

            
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function storeOfficeInventory(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'office' => 'required', 
                'source' => 'required',
                'date_submitted' => 'required',
                'intake' => 'required_if:source,student',
                'student' => 'required_if:source,student',
                'year_of_study' => 'required_if:source,student',
                'semester' => 'required_if:source,student',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            foreach ($request->item_array as $value) {
                if ($value['item'] == "" || $value['quantity'] == '') {

                    DB::rollBack();

                    return response()->json([
                        'status'=> 403,
                        'message'=> 'Some fields from your form are empty! Make sure all fields are filled in before submitting',
                    ], 403);
                } if ($request->source == "student") {
                    if (DB::table('student_submitted_inventory')->where([
                        'item_id' => $value['item'],
                        'office_id' => $request->office,
                        'student_id' => $request->student,
                        'year_id' => $request->year_of_study,
                        'semester_id' => $request->semester,
                        'date_submitted' => $request->date_submitted
                    ])->count() > 0) {
                        DB::rollBack();

                        return $this->responseMessage(403, "You are trying to add ".$this->getItemName($value['item']). " multiple times on the same date");
                    } else {
                        DB::table('student_submitted_inventory')->insert([
                            'author' => $request->user()->id,
                            'item_id' => $value['item'],
                            'office_id' => $request->office,
                            'student_id' => $request->student,
                            'year_id' => $request->year_of_study,
                            'semester_id' => $request->semester,
                            'date_submitted' => $request->date_submitted,
                            'quantity' => $value['quantity']
                        ]);

                        $this->recordInventory($value['item'], $request->office, $value['quantity']);
                    }
                } elseif ($request->source == "acquisition" || $request->source == "donations") {
                    DB::table('school_acquired_inventory')->insert([
                        'author' => $request->user()->id,
                        'item_id' => $value['item'],
                        'office_id' => $request->office,
                        'date_submitted' => $request->date_submitted,
                        'quantity' => $value['quantity'],
                        'source' => $request->source
                    ]);

                    $this->recordInventory($value['item'], $request->office, $value['quantity']);
                    // if (DB::table('school_acquired_inventory')->where([
                    //     'item_id' => $value['item'],
                    //     'office_id' => $request->office,
                    //     'date_submitted' => $request->date_submitted,
                    //     'source' => $request->source,
                    // ])->count() > 0) {
                    //     DB::rollBack();

                    //     return $this->responseMessage(403, "You are trying to add ".$this->getItemName($value['item']). " multiple times on the same date from the same source");
                    // } else {
                    //     DB::table('school_acquired_inventory')->insert([
                    //         'author' => $request->user()->id,
                    //         'item_id' => $value['item'],
                    //         'office_id' => $request->office,
                    //         'date_submitted' => $request->date_submitted,
                    //         'quantity' => $value['quantity'],
                    //         'source' => $request->source
                    //     ]);

                    //     $this->recordInventory($value['item'], $request->office, $value['quantity']);
                    // }
                }
            }  
            
            DB::commit();

            return $this->responseMessage(201, "Inventory has been recorded successfully");
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function studentSubmitted(Request $request) {
        try {
            $items = DB::table("student_submitted_inventory as ssi")
            ->join('student_payments as sp', 'ssi.registration_id', '=', 'sp.id')
            ->join('admins', 'ssi.author', '=', 'admins.id')
            ->join('inventory_items', 'ssi.item_id', '=', 'inventory_items.id')
            ->join('offices', 'ssi.office_id', '=', 'offices.id')
            ->join('students', 'sp.student_id', '=', 'students.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->select('ssi.*', 'admins.name as author_name', 'inventory_items.item_name', 'offices.office_name', 'students.name', 'students.computer_number', 'training_years.year_label', 'training_semesters.semester_label')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $items,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function studentIndividualInventory(Request $request) {
        try {
            $items = DB::table("student_submitted_inventory as ssi")
            ->join('student_payments as sp', 'ssi.registration_id', '=', 'sp.id')
            ->join('admins', 'ssi.author', '=', 'admins.id')
            ->join('inventory_items', 'ssi.item_id', '=', 'inventory_items.id')
            ->join('offices', 'ssi.office_id', '=', 'offices.id')
            ->join('training_years', 'sp.year_id', '=', 'training_years.id')
            ->join('training_semesters', 'sp.semester_id', '=', 'training_semesters.id')
            ->select('ssi.*', 'admins.name as author_name', 'inventory_items.item_name', 'offices.office_name', 'training_years.year_label', 'training_semesters.semester_label')
            ->where('sp.student_id', $request->user()->id)
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $items,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function schoolAcquired(Request $request) {
        try {
            $items = DB::table("school_acquired_inventory as ssi")
            ->join('admins', 'ssi.author', '=', 'admins.id')
            ->join('inventory_items', 'ssi.item_id', '=', 'inventory_items.id')
            ->join('offices', 'ssi.office_id', '=', 'offices.id')
            ->select('ssi.*', 'admins.name as author_name', 'inventory_items.item_name', 'offices.office_name')
            ->get();

            return response()->json([
                'status' => 200,
                'data' => $items,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getOfficeInventory(Request $request) {
        try {
            $items = DB::table("inventory as ssi")
            ->join('inventory_items', 'ssi.item_id', '=', 'inventory_items.id')
            ->select('ssi.*', 'inventory_items.item_name')
            ->where('ssi.office_id', $request->office_id)
            ->get();

            $office_name = DB::table('offices')->where('id', $request->office_id)->value('office_name');

            return response()->json([
                'status' => 200,
                'data' => $items,
                'office_name' => $office_name
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function itemSummary(Request $request) {
        try {
            $summary = DB::table("inventory as ssi")
            ->join('offices', 'ssi.office_id', '=', 'offices.id')
            ->select('ssi.*', 'offices.office_name')
            ->where('ssi.item_id', $request->item_id)
            ->get();

            $item_name = DB::table('inventory_items')->where('id', $request->item_id)->value('item_name');

            $total_quantity = DB::table('inventory')->where('item_id', $request->item_id)->sum('quantity');

            return response()->json([
                'status' => 200,
                'data' => $summary,
                'item_name' => $item_name,
                'total_quantity' => $total_quantity
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
 
    public function update(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'item_name' => 'required|unique:inventory_items,item_name,'.$request->id, 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            DB::table('inventory_items')->where('id', $request->id)->update([
                'item_name' => $request->item_name,
            ]);

            return response()->json([
                'status'=> 200,
                'message'=> 'Item has been updated successfully',
            ], 200);
        }
        catch (\Throwable $th) {
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request) {
        try {
            $office = DB::table('inventory_items')->where('id', $request->id)->first();

            if ($office) {
                DB::table('inventory_items')->where('id', $request->id)->delete();

                return response()->json([
                    'status'=> 200,
                    'message'=> 'Item has been deleted',
                ], 200);
            } else {
                return response()->json([
                    'status'=> 404,
                    'message'=> 'The item you are trying to delete does not exist on this database',
                ], 404);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    public function inventoryTransferStore(Request $request) {
        date_default_timezone_set('Africa/Lusaka');
        $today = Carbon::today()->toDateString();
        try {
            $validator = Validator::make($request->all(), [
                // 'from_office' => 'required', 
                'to_office' => 'required',
                'item' => 'required',
                'quantity' => 'required|min:1|numeric',
                'date_transferred' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=> 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->date_transferred > $today) {
                return $this->responseMessage(403, 'The date transferred cannot be a future date!');
            }

            if ($request->user()->office_id == $request->to_office) {
                return $this->responseMessage(403, 'The from office cannot be the same as the to office!');
            }

            $inv = DB::table('inventory')->where([
                'office_id' => $request->user()->office_id,
                'item_id' => $request->item,
            ])->first();

            if ($request->quantity > $inv->quantity) {
                return $this->responseMessage(403, 'The quantity you want to transfer is more than what is in the from office!');
            } else {
                DB::beginTransaction();

                // insert into transfers
                DB::table('inventory_transfers')->insert([
                    'author' => $request->user()->id,
                    'item_id' => $request->item,
                    'from_office' => $request->user()->office_id,
                    'to_office' => $request->to_office,
                    'quantity'=> $request->quantity,
                    'date_transferred' => $request->date_transferred,
                ]);

                // decrement on from office
                $this->decrementInventory($request->item, $request->user()->office_id, $request->quantity);
                $this->recordInventory($request->item, $request->to_office, $request->quantity);

                DB::commit();

                return $this->responseMessage(200, "Inventory transfer completed successfully");
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    private function getItemName($item_id) {
        return DB::table('inventory_items')->where('id', $item_id)->value('item_name');
    }

    private function responseMessage($errorCode, $message) {
        return response()->json([
            'status' => $errorCode,
            'message'=> $message
        ], $errorCode);
    }

    public function inventoryTransferIndex() {
        try {
            $transfers = DB::table('inventory_transfers as ssi')
            ->join('admins', 'ssi.author', '=', 'admins.id')
            ->join('inventory_items', 'ssi.item_id', '=', 'inventory_items.id')
            // ->join('offices', 'ssi.from_office', '=', 'offices.id')
            ->join('offices', 'ssi.to_office', '=', 'offices.id')
            ->select('ssi.*', 'admins.name as author_name', 'inventory_items.item_name', 'offices.office_name')
            ->paginate(50);

            return response()->json([
                'status'=> 200,
                'data' => $transfers,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    private function recordInventory($item, $office, $quantity) {
        try {
            if (DB::table('inventory')->where([
                'item_id' => $item,
                'office_id' => $office
            ])->count() > 0) {
                $this->incrementInventory($item, $office, $quantity);
            } else {
                DB::table('inventory')->insert([
                    'item_id' => $item,
                    'office_id' => $office,
                    'quantity' => $quantity
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status'=> 500,
                'message'=> $th->getMessage(),
            ], 500);
        }
    }

    private function incrementInventory($item, $office, $quantity) {
        DB::table('inventory')->where([
            'item_id' => $item,
            'office_id' => $office
        ])->increment('quantity', $quantity);
    }

    private function decrementInventory($item, $office, $quantity) {
        DB::table('inventory')->where([
            'item_id' => $item,
            'office_id' => $office
        ])->decrement('quantity', $quantity);
    }

    
}
