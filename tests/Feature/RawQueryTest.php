<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RawQueryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::delete("delete from precise.country where country_code = ?", ["LBY"]);
    }

    public function testCrud()
    {
        DB::insert("insert into precise.country(country_code,country_name,created_by) values(?,?,?)", [
            "LBY", "LIBYA", "paldi"
        ]);

        $results = DB::select('select country_code,country_name,created_by from precise.country where country_code = ?', ['LBY']);

        self::assertCount(1, $results);
        self::assertEquals('LBY', $results[0]->country_code);
        self::assertEquals('LIBYA', $results[0]->country_name);
        self::assertEquals('paldi', $results[0]->created_by);
    }
}
