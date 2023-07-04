<?php

namespace App\Http\Controllers\Api\Helpers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DBController extends Controller
{
    public static function reason(Request $request, $type, $data = "")
    {
        //for form
        if ($data == "" || empty($data)) {
            if ($type == "update") {
                $reason = DB::statement(
                    "SET @userName=:user , @reason=:reason",
                    array(':user' => $request->input('updated_by'), ':reason' => $request->input('reason'))
                );
            } else if ($type == "delete") {
                $reason = DB::statement(
                    "SET @userName=:user , @reason=:reason",
                    array(':user' => $request->input('deleted_by'), ':reason' => $request->input('reason'))
                );
            }
        } else {
            //for json
            if ($type == "update") {
                $reason = DB::statement(
                    "SET @userName=:user , @reason=:reason",
                    array(':user' => $data['updated_by'], ':reason' => $data['reason'])
                );
            } else if ($type == "delete") {
                $reason = DB::statement(
                    "SET @userName=:user , @reason=:reason",
                    array(':user' => $request->input('deleted_by'), ':reason' => $request->input('reason'))
                );
            }
        }
        return $reason;
    }

    public function insertOrUpdate(array $rows, $table, $deletedString)
    {
        //$table = \DB::getTablePrefix() . with(new self)->getTable();
        $first = reset($rows);

        $columns = implode(
            ',',
            array_map(function ($value) {
                return "$value";
            }, array_keys($first))
        );

        $values = implode(
            ',',
            array_map(function ($row) {
                return '(' . implode(
                    ',',
                    array_map(function ($value) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }, $row)
                ) . ')';
            }, $rows)
        );

        $updates = implode(
            ',',
            array_map(function ($value) {
                return "$value = VALUES($value)";
            }, array_keys($first))
        );
        if ($deletedString != '') {
            $updates = str_replace($deletedString, '', $updates);
        }
        $sql = "INSERT INTO {$table}({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$updates}";

        //return $sql;
        return DB::insert($sql);
    }
}
