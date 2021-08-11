<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker=Factory::create();
        for ($i=0; $i<10; $i++){
            $user=new User();
            $user->name=$faker->name();
            $user->email=$faker->email();
            $user->email_verified_at=Carbon::now();
            $user->password=Hash::make('12345678');
            $user->save();
        }
    }
}
