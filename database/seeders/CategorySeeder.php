<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        DB::table('categories')->insert([
            'name' => 'Comedy',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('categories')->insert([
            'name' => 'Romance',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Horror',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Adventure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Fiction',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Fantasy',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Drama',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Heartfelt',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('categories')->insert([
            'name' => 'Mystery',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
