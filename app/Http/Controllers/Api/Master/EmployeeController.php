<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmployeeController extends Controller
{
    private $employee;
    public function index(): JsonResponse
    {
        // $this->employee = DB::table('precise.xyz_employee as empl')
        //     ->select(
        //         'employee_nik',
        //         'employee_id',
        //         'employee_name',
        //         'employee_npwp',
        //         'empl.driving_license_type_id',
        //         'driving_license_number',
        //         'gender_desc',
        //         'religion_name',
        //         'department_name',
        //         'section_name',
        //         'unit_name',
        //         'employee_position',
        //         'employee_level',
        //         'branch_name',
        //         'employee_city_birthday',
        //         'employee_date_birthday',
        //         'marital_status_name',
        //         'employee_couple',
        //         'employee_children_total',
        //         'employee_children',
        //         'kpph_code',
        //         'salary_status_desc',
        //         'employment_status_name',
        //         'shift_name',
        //         'employee_phone',
        //         'empl.blood_type_id',
        //         'bank_name',
        //         'bank_account_number',
        //         'employee_address',
        //         'employee_address2',
        //         'degree_name',
        //         'employee_graduate_year',
        //         'employee_education',
        //         'employee_education_major',
        //         'health_insurance_desc',
        //         'employee_employment_insurance_number',
        //         'employee_insurance',
        //         'employee_image',
        //         'employee_first_date_work',
        //         'employee_experience',
        //         'empl.is_active',
        //         'empl.created_on',
        //         'empl.created_by',
        //         'empl.updated_on',
        //         'empl.updated_by'
        //     )
        //     ->join('precise.xyz_department as dept', 'empl.department_id', '=', 'dept.department_id')
        //     ->leftJoin('precise.xyz_section as sect', 'sect.department_id', '=', 'dept.department_id')
        //     ->leftJoin('precise.xyz_unit as unit', 'unit.section_id', '=', 'sect.section_id')
        //     ->leftJoin('precise.xyz_driving_license as driv', 'empl.driving_license_type_id', '=', 'driv.driving_license_type_id')
        //     ->join('precise.xyz_branch as bran', 'empl.branch_id', '=', 'bran.branch_id')
        //     ->join('precise.xyz_gender as gend', 'empl.gender_code', '=', 'gend.gender_code')
        //     ->leftJoin('precise.xyz_religion as reli', 'empl.religion_id', '=', 'reli.religion_id')
        //     ->join('precise.xyz_marital_status as mari', 'empl.marital_status_id', '=', 'mari.marital_status_id')
        //     ->join('precise.xyz_kpph as kpph', 'kpph.kpph_id', '=', 'empl.kpph_id')
        //     ->join('precise.xyz_salary_status as sala', 'sala.salary_status_id', '=', 'empl.salary_status_id')
        //     ->join('precise.xyz_employment_status as emps', 'emps.employment_status_id', '=', 'empl.employment_status_id')
        //     ->join('precise.xyz_shift as shif', 'empl.shift_id', '=', 'shif.shift_id')
        //     ->leftJoin('precise.xyz_blood_type as bloo', 'empl.blood_type_id', '=', 'bloo.blood_type_id')
        //     ->join('precise.xyz_bank as bank', 'empl.bank_id', '=', 'bank.bank_id')
        //     ->join('precise.xyz_degree as degr', 'degr.degree_id', '=', 'empl.degree_id')
        //     ->join('precise.xyz_health_insurance as heal', 'heal.health_insurance_code', '=', 'empl.health_insurance_code')
        //     ->get();
        $status = request('status');
        $this->employee = DB::table("dbhrd.newdatakar")
            ->select(
                'NIP as employee_nik',
                'Nama as employee_name',
                DB::raw(
                    "
                        case STDATA		
                        when 'A' then 1 
                        when 'N' then 0
                        end as is_active
                    "
                )
            );

        if ($status) {
            $this->employee = $this->employee->where('STDATA', $status);
        }
        return response()->json(["status" => "ok", "data" => $this->employee->get()], 200);
    }

    public function show($id): JsonResponse
    {
        $this->employee = DB::table("dbhrd.newdatakar")
            ->where("NIP", $id)
            ->select(
                "NIP as employee_nik",
                "NAMA as employee_name",
                "KETSEX as employee_sex",
                "TGLLAHIR AS birth_date",
                "TGLMASUK AS employee_entry_date",
                "NM_DEPARTE AS employee_department",
                "BAGIAN AS employee_section",
                "NM_JABATAN AS employee_position",
                "KETSTKERJA AS employee_work_status"
            )
            ->first();

        if (empty($this->employee))
            return response()->json("not found", 404);
        return response()->json($this->employee, 200);
    }

    public function showByName($name): JsonResponse
    {
        $this->employee = DB::table("dbhrd.newdatakar")
            ->where('nama', 'like', '%' . $name . '%')
            ->where('STDATA', 'A')
            ->select(
                'NIP as employee_nik',
                'NAMA as employee_name',
                'KETSEX as employee_sex',
                'TGLMASUK as employee_entry_date',
                'NM_DEPARTE as employee_department',
                'BAGIAN AS employee_section',
                'NM_JABATAN AS employee_position',
                'KETSTKERJA AS employee_work_status'
            )
            ->get();

        return response()->json(["status" => "ok", "data" => $this->employee], 200);
    }

    public function showSuperByNIP($nip)
    {
        $union = DB::table("dbhrd.newdatakar as a")
            ->where("a.NIP", $nip)
            ->whereRaw("LEFT(b.LEVEL, 1) BETWEEN 1 AND 5")
            ->where("b.STDATA", 'A')
            ->where("b.NIP", '!=', $nip)
            ->select(
                'b.NIP',
                'b.NAMA',
                'b.NM_DEPARTE',
                'b.BAGIAN'
            )
            ->leftJoin('dbhrd.newdatakar as b', 'b.NM_DEPARTE', '=', 'a.NM_DEPARTE');

        $this->employee = DB::table("dbhrd.newdatakar as a")
            ->whereRaw("LEFT(a.LEVEL, 1) BETWEEN 1 AND 4")
            ->where("a.STDATA", 'A')
            ->where("a.NIP", '!=', $nip)
            ->unionAll($union)
            ->select(
                'a.NIP',
                'a.NAMA',
                'a.NM_DEPARTE',
                'a.BAGIAN'
            )
            ->get();

        if (count($this->employee) == 0)
            return response()->json(["status" => "error", "message" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->employee], 200);
    }

    public function create(Request $request)
    {
    }

    public function check(Request $request)
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == 'nik') {
                $this->employee = DB::table('precise.xyz_employee')
                    ->where('employee_id', $value)
                    ->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->employee]);
        }
    }
}
