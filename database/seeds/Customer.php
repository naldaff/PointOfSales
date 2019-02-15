<?php

use Illuminate\Database\Seeder;
use Faker\Factory as faker;

class Customer extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('customers')->truncate();
        DB::table('customers')->delete();

        $faker = Faker::create('id_ID');
        foreach (range(0,10) as $i) {
        	DB::table('customers')->insert([
        		'email' => $faker->freeEmail,
        		'name' => $faker->name,
        		'address' => $faker->address,
        		'phone' => $faker->phoneNumber,
        		'created_at' => $faker->dateTime($max = 'now', $timezone = null),
        		'updated_at' => $faker->dateTime($max = 'now', $timezone = null)
        	]);
        }
    }
}
